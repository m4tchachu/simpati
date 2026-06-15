<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmDebtRecordRequest;
use App\Http\Requests\RejectDebtRecordRequest;
use App\Http\Requests\SettleDebtRecordRequest;
use App\Http\Requests\StoreDebtRecordRequest;
use App\Http\Requests\UpdateDebtRecordRequest;
use App\Http\Resources\DebtRecordResource;
use App\Models\DebtRecord;
use App\Services\DebtRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtRecordController extends Controller
{
    public function __construct(
        private DebtRecordService $debtRecordService,
    ) {}

    /**
     * Get user's debt records with pagination and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DebtRecord::class);

        $filters = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|string|in:debt,receivable',
            'status' => 'nullable|string|in:pending,active,rejected,settled',
            'sort' => 'nullable|string|in:created_at,due_date,amount',
            'order' => 'nullable|string|in:asc,desc',
        ]);

        $debts = $this->debtRecordService->getUserDebts($request->user(), $filters);

        return response()->json([
            'message' => 'Debt records retrieved successfully',
            'data' => DebtRecordResource::collection($debts),
            'pagination' => [
                'total' => $debts->total(),
                'per_page' => $debts->perPage(),
                'current_page' => $debts->currentPage(),
                'last_page' => $debts->lastPage(),
                'from' => $debts->firstItem(),
                'to' => $debts->lastItem(),
            ],
        ], 200);
    }

    /**
     * Show single debt record
     *
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function show(DebtRecord $debtRecord): JsonResponse
    {
        $this->authorize('view', $debtRecord);

        $debt = $this->debtRecordService->getDebtRecord($debtRecord->id);

        return response()->json([
            'message' => 'Debt record retrieved successfully',
            'data' => new DebtRecordResource($debt),
        ], 200);
    }

    /**
     * Create new debt record
     *
     * @param StoreDebtRecordRequest $request
     * @return JsonResponse
     */
    public function store(StoreDebtRecordRequest $request): JsonResponse
    {
        try {
            $debt = $this->debtRecordService->createDebtRecord(
                array_merge(
                    $request->validated(),
                    ['creator_id' => $request->user()->id]
                )
            );

            return response()->json([
                'message' => 'Debt record created successfully',
                'data' => new DebtRecordResource($debt),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create debt record',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update debt record (only if pending)
     *
     * @param UpdateDebtRecordRequest $request
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function update(UpdateDebtRecordRequest $request, DebtRecord $debtRecord): JsonResponse
    {
        try {
            $debt = $this->debtRecordService->updateDebtRecord(
                $debtRecord->id,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'message' => 'Debt record updated successfully',
                'data' => new DebtRecordResource($debt),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update debt record',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete debt record (only if pending)
     *
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function destroy(DebtRecord $debtRecord, Request $request): JsonResponse
    {
        $this->authorize('delete', $debtRecord);

        try {
            $this->debtRecordService->deleteDebtRecord($debtRecord->id, $request->user());

            return response()->json([
                'message' => 'Debt record deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete debt record',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm debt record (pending -> active)
     *
     * @param ConfirmDebtRecordRequest $request
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function confirm(ConfirmDebtRecordRequest $request, DebtRecord $debtRecord): JsonResponse
    {
        $this->authorize('confirm', $debtRecord);

        try {
            $debt = $this->debtRecordService->confirmDebtRecord(
                $debtRecord->id,
                $request->user()
            );

            return response()->json([
                'message' => 'Debt record confirmed successfully',
                'data' => new DebtRecordResource($debt),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to confirm debt record',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject debt record (pending -> rejected)
     *
     * @param RejectDebtRecordRequest $request
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function reject(RejectDebtRecordRequest $request, DebtRecord $debtRecord): JsonResponse
    {
        $this->authorize('reject', $debtRecord);

        try {
            $debt = $this->debtRecordService->rejectDebtRecord(
                $debtRecord->id,
                $request->getRejectionReason(),
                $request->user()
            );

            return response()->json([
                'message' => 'Debt record rejected successfully',
                'data' => new DebtRecordResource($debt),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject debt record',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Settle debt record (active -> settled)
     *
     * @param SettleDebtRecordRequest $request
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function settle(SettleDebtRecordRequest $request, DebtRecord $debtRecord): JsonResponse
    {
        $this->authorize('settle', $debtRecord);

        try {
            $debt = $this->debtRecordService->settleDebtRecord(
                $debtRecord->id,
                $request->user()
            );

            return response()->json([
                'message' => 'Debt record settled successfully',
                'data' => new DebtRecordResource($debt),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to settle debt record',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get overdue debt records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overdue(Request $request): JsonResponse
    {
        $overdues = $this->debtRecordService->getOverdueDebts($request->user());

        return response()->json([
            'message' => 'Overdue debts retrieved successfully',
            'data' => DebtRecordResource::collection($overdues),
            'count' => $overdues->count(),
        ], 200);
    }

    /**
     * Get upcoming due dates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $upcoming = $this->debtRecordService->getUpcomingDebts(
            $request->user(),
            $request->input('days', 7)
        );

        return response()->json([
            'message' => 'Upcoming debts retrieved successfully',
            'data' => DebtRecordResource::collection($upcoming),
            'count' => $upcoming->count(),
        ], 200);
    }

    /**
     * Search debt records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('search', DebtRecord::class);

        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $results = $this->debtRecordService->searchDebtRecords(
            $request->user(),
            $request->input('q'),
            $request->input('limit', 10)
        );

        return response()->json([
            'message' => 'Debt records search results',
            'data' => $results,
        ], 200);
    }

    /**
     * Get debt statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->debtRecordService->getDebtStats($request->user());

        return response()->json([
            'message' => 'Debt statistics retrieved',
            'data' => $stats,
        ], 200);
    }

    /**
     * Get debt record history
     *
     * @param DebtRecord $debtRecord
     * @return JsonResponse
     */
    public function history(DebtRecord $debtRecord): JsonResponse
    {
        $this->authorize('view', $debtRecord);

        $history = $this->debtRecordService->getDebtHistory($debtRecord->id);

        return response()->json([
            'message' => 'Debt history retrieved successfully',
            'data' => $history->map(function ($change) {
                return [
                    'id' => $change->id,
                    'old_status' => $change->old_status->value,
                    'old_status_label' => $change->old_status->label(),
                    'new_status' => $change->new_status->value,
                    'new_status_label' => $change->new_status->label(),
                    'reason' => $change->reason,
                    'changed_by' => [
                        'id' => $change->changedByUser->id,
                        'name' => $change->changedByUser->name,
                    ],
                    'created_at' => $change->created_at,
                ];
            }),
        ], 200);
    }
}
