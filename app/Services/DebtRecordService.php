<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Events\DebtRecordCreated;
use App\Events\DebtRecordConfirmed;
use App\Events\DebtRecordRejected;
use App\Events\DebtRecordSettled;
use App\Models\DebtRecord;
use App\Models\DebtStatusChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtRecordService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * Create new debt record
     *
     * @param array{
     *     creator_id: int,
     *     counterpart_id: int,
     *     type: string,
     *     amount: float,
     *     description: string,
     *     transaction_date: string,
     *     due_date: string
     * } $data
     * @return DebtRecord
     */
    public function createDebtRecord(array $data): DebtRecord
    {
        $debtRecord = DebtRecord::create([
            'creator_id' => $data['creator_id'],
            'counterpart_id' => $data['counterpart_id'],
            'type' => DebtType::from($data['type']),
            'amount' => $data['amount'],
            'description' => $data['description'],
            'transaction_date' => $data['transaction_date'],
            'due_date' => $data['due_date'],
            'status' => DebtStatus::PENDING,
        ]);

        // Log action
        $this->logAudit($data['creator_id'], $debtRecord->id, 'create', null, $debtRecord->getAttributes());

        // Dispatch event to trigger notifications
        $creator = User::find($data['creator_id']);
        $counterpart = User::find($data['counterpart_id']);
        DebtRecordCreated::dispatch($debtRecord, $creator, $counterpart);

        return $debtRecord->load('creator', 'counterpart');
    }

    /**
     * Update debt record (only if pending)
     *
     * @param int $debtRecordId
     * @param array{
     *     amount?: float,
     *     description?: string,
     *     due_date?: string
     * } $data
     * @param User $user
     * @return DebtRecord
     */
    public function updateDebtRecord(int $debtRecordId, array $data, User $user): DebtRecord
    {
        $debtRecord = $this->getDebtRecord($debtRecordId);

        if ($debtRecord->status !== DebtStatus::PENDING) {
            throw new \Exception('Hanya transaksi pending yang dapat diedit.');
        }

        $oldValues = $debtRecord->getAttributes();

        $debtRecord->update($data);

        // Log action
        $this->logAudit($user->id, $debtRecord->id, 'update', $oldValues, $debtRecord->fresh()->getAttributes());

        // Send notification to counterpart about update
        $this->notificationService->notifyDebtUpdated($debtRecord);

        return $debtRecord->load('creator', 'counterpart');
    }

    /**
     * Get debt record by ID
     *
     * @param int $id
     * @return DebtRecord
     */
    public function getDebtRecord(int $id): DebtRecord
    {
        return DebtRecord::with('creator', 'counterpart', 'statusChanges')
            ->findOrFail($id);
    }

    /**
     * Get user's debt records with filters
     *
     * @param User $user
     * @param array{
     *     type?: string,
     *     status?: string,
     *     page?: int,
     *     per_page?: int,
     *     sort?: string,
     *     order?: string
     * } $filters
     * @return LengthAwarePaginator
     */
    public function getUserDebts(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;
        $type = $filters['type'] ?? null;
        $status = $filters['status'] ?? null;
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';

        $query = DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        })->with('creator', 'counterpart');

        if ($type && in_array($type, ['debt', 'receivable'])) {
            $query->where('type', $type);
        }

        if ($status && in_array($status, ['pending', 'active', 'rejected', 'settled'])) {
            $query->where('status', $status);
        }

        $query->orderBy($sort, $order);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get overdue debt records for user
     *
     * @param User $user
     * @return Collection
     */
    public function getOverdueDebts(User $user): Collection
    {
        return DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        })->where('status', DebtStatus::ACTIVE)
            ->where('due_date', '<', now())
            ->with('creator', 'counterpart')
            ->get();
    }

    /**
     * Get upcoming debt records (due soon)
     *
     * @param User $user
     * @param int $days
     * @return Collection
     */
    public function getUpcomingDebts(User $user, int $days = 7): Collection
    {
        return DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        })->where('status', DebtStatus::ACTIVE)
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->with('creator', 'counterpart')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Confirm debt record (pending -> active)
     *
     * @param int $debtRecordId
     * @param User $user
     * @return DebtRecord
     */
    public function confirmDebtRecord(int $debtRecordId, User $user): DebtRecord
    {
        $debtRecord = $this->getDebtRecord($debtRecordId);

        if ($debtRecord->status !== DebtStatus::PENDING) {
            throw new \Exception('Hanya transaksi pending yang dapat dikonfirmasi.');
        }

        $oldStatus = $debtRecord->status;
        $debtRecord->update([
            'status' => DebtStatus::ACTIVE,
            'confirmed_at' => now(),
        ]);

        // Log status change
        DebtStatusChange::create([
            'debt_record_id' => $debtRecord->id,
            'changed_by_user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => DebtStatus::ACTIVE,
        ]);

        // Log action
        $this->logAudit($user->id, $debtRecord->id, 'confirm');

        // Dispatch event to trigger notifications
        $debtRecord = $debtRecord->fresh()->load('creator', 'counterpart');
        DebtRecordConfirmed::dispatch($debtRecord, $debtRecord->creator, $debtRecord->counterpart);

        return $debtRecord;
    }

    /**
     * Reject debt record (pending -> rejected)
     *
     * @param int $debtRecordId
     * @param string $reason
     * @param User $user
     * @return DebtRecord
     */
    public function rejectDebtRecord(int $debtRecordId, string $reason, User $user): DebtRecord
    {
        $debtRecord = $this->getDebtRecord($debtRecordId);

        if ($debtRecord->status !== DebtStatus::PENDING) {
            throw new \Exception('Hanya transaksi pending yang dapat ditolak.');
        }

        $oldStatus = $debtRecord->status;
        $debtRecord->update([
            'status' => DebtStatus::REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Log status change
        DebtStatusChange::create([
            'debt_record_id' => $debtRecord->id,
            'changed_by_user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => DebtStatus::REJECTED,
            'reason' => $reason,
        ]);

        // Log action
        $this->logAudit($user->id, $debtRecord->id, 'reject');

        // Dispatch event to trigger notifications
        $debtRecord = $debtRecord->fresh()->load('creator', 'counterpart');
        DebtRecordRejected::dispatch($debtRecord, $debtRecord->creator, $debtRecord->counterpart, $reason);

        return $debtRecord;
    }

    /**
     * Settle debt record (active -> settled)
     *
     * @param int $debtRecordId
     * @param User $user
     * @return DebtRecord
     */
    public function settleDebtRecord(int $debtRecordId, User $user): DebtRecord
    {
        $debtRecord = $this->getDebtRecord($debtRecordId);

        if ($debtRecord->status !== DebtStatus::ACTIVE) {
            throw new \Exception('Hanya transaksi active yang dapat diselesaikan.');
        }

        $oldStatus = $debtRecord->status;
        $debtRecord->update([
            'status' => DebtStatus::SETTLED,
            'settled_at' => now(),
        ]);

        // Log status change
        DebtStatusChange::create([
            'debt_record_id' => $debtRecord->id,
            'changed_by_user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => DebtStatus::SETTLED,
        ]);

        // Log action
        $this->logAudit($user->id, $debtRecord->id, 'settle');

        // Dispatch event to trigger notifications
        $debtRecord = $debtRecord->fresh()->load('creator', 'counterpart');
        DebtRecordSettled::dispatch($debtRecord, $debtRecord->creator, $debtRecord->counterpart);

        return $debtRecord;
    }

    /**
     * Delete debt record (only if pending)
     *
     * @param int $debtRecordId
     * @param User $user
     * @return bool
     */
    public function deleteDebtRecord(int $debtRecordId, User $user): bool
    {
        $debtRecord = $this->getDebtRecord($debtRecordId);

        if ($debtRecord->status !== DebtStatus::PENDING) {
            throw new \Exception('Hanya transaksi pending yang dapat dihapus.');
        }

        // Log action
        $this->logAudit($user->id, $debtRecord->id, 'delete');

        return $debtRecord->delete();
    }

    /**
     * Get debt statistics for user
     *
     * @param User $user
     * @return array
     */
    public function getDebtStats(User $user): array
    {
        $allDebts = $user->getAllDebts();

        return [
            'total_debt' => (float) $allDebts->where('type', DebtType::DEBT)->sum('amount'),
            'total_receivable' => (float) $allDebts->where('type', DebtType::RECEIVABLE)->sum('amount'),
            'active_debt_count' => $allDebts->where('type', DebtType::DEBT)
                ->where('status', DebtStatus::ACTIVE)->count(),
            'active_receivable_count' => $allDebts->where('type', DebtType::RECEIVABLE)
                ->where('status', DebtStatus::ACTIVE)->count(),
            'pending_count' => $allDebts->where('status', DebtStatus::PENDING)->count(),
            'rejected_count' => $allDebts->where('status', DebtStatus::REJECTED)->count(),
            'settled_count' => $allDebts->where('status', DebtStatus::SETTLED)->count(),
            'overdue_count' => $allDebts->where('status', DebtStatus::ACTIVE)
                ->where('due_date', '<', now())->count(),
        ];
    }

    /**
     * Search debt records
     *
     * @param User $user
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function searchDebtRecords(User $user, string $query, int $limit = 10): array
    {
        return DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        })->where(function ($q) use ($query) {
            $q->where('description', 'like', "%{$query}%")
                ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                ->orWhereHas('counterpart', fn ($q) => $q->where('name', 'like', "%{$query}%"));
        })->with('creator', 'counterpart')
            ->limit($limit)
            ->get()
            ->map(function ($debt) {
                return [
                    'id' => $debt->id,
                    'type' => $debt->type->label(),
                    'amount' => $debt->amount,
                    'description' => $debt->description,
                    'status' => $debt->status->label(),
                    'due_date' => $debt->due_date,
                    'creator' => $debt->creator->name,
                    'counterpart' => $debt->counterpart->name,
                ];
            })
            ->toArray();
    }

    /**
     * Get debt record history
     *
     * @param int $debtRecordId
     * @return Collection
     */
    public function getDebtHistory(int $debtRecordId): Collection
    {
        return DebtStatusChange::where('debt_record_id', $debtRecordId)
            ->with('changedByUser')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Log audit action
     *
     * @param int $userId
     * @param int $debtRecordId
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    private function logAudit(int $userId, int $debtRecordId, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        $user = User::findOrFail($userId);

        $user->auditLogs()->create([
            'action' => $action,
            'table_name' => 'debt_records',
            'record_id' => $debtRecordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
