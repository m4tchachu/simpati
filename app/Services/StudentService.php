<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Enums\UserRole;
use App\Models\DebtRecord;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class StudentService
{
    /**
     * Get all students with pagination
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     study_program_id?: int,
     *     sort?: string,
     *     order?: string
     * } $filters
     * @return LengthAwarePaginator
     */
    public function getAllStudents(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;
        $search = $filters['search'] ?? null;
        $studyProgramId = $filters['study_program_id'] ?? null;
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';

        $query = User::where('role', UserRole::MAHASISWA)
            ->with('studyProgram');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($studyProgramId) {
            $query->where('study_program_id', $studyProgramId);
        }

        $query->orderBy($sort, $order);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get single student by ID
     *
     * @param int $id
     * @return User
     */
    public function getStudentById(int $id): User
    {
        return User::with('studyProgram')
            ->where('id', $id)
            ->where('role', UserRole::MAHASISWA)
            ->firstOrFail();
    }

    /**
     * Create new student
     *
     * @param array{
     *     nim: string,
     *     name: string,
     *     email: string,
     *     password: string,
     *     study_program_id: int
     * } $data
     * @return User
     */
    public function createStudent(array $data): User
    {
        $student = User::create([
            'nim' => strtoupper($data['nim']),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => UserRole::MAHASISWA,
            'study_program_id' => $data['study_program_id'],
        ]);

        // Log creation
        $this->logAudit($student, 'create');

        return $student;
    }

    /**
     * Update existing student
     *
     * @param int $id
     * @param array{
     *     nim?: string,
     *     name?: string,
     *     email?: string,
     *     password?: string,
     *     study_program_id?: int
     * } $data
     * @return User
     */
    public function updateStudent(int $id, array $data): User
    {
        $student = $this->getStudentById($id);

        $updateData = [];

        if (isset($data['nim'])) {
            $updateData['nim'] = strtoupper($data['nim']);
        }

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = strtolower($data['email']);
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (isset($data['study_program_id'])) {
            $updateData['study_program_id'] = $data['study_program_id'];
        }

        $oldValues = $student->getAttributes();
        $student->update($updateData);

        // Log update
        $this->logAuditUpdate($student, 'update', $oldValues, $student->fresh()->getAttributes());

        return $student;
    }

    /**
     * Delete student
     *
     * @param int $id
     * @return bool
     */
    public function deleteStudent(int $id): bool
    {
        $student = $this->getStudentById($id);

        // Log deletion
        $this->logAudit($student, 'delete');

        return $student->delete();
    }

    /**
     * Search students
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function searchStudents(string $query, int $limit = 10): array
    {
        return User::where('role', UserRole::MAHASISWA)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('nim', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->with('studyProgram')
            ->limit($limit)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nim' => $student->nim,
                    'email' => $student->email,
                    'study_program' => $student->studyProgram?->name,
                ];
            })
            ->toArray();
    }

    /**
     * Get student debt statistics
     *
     * @param int $studentId
     * @return array{
     *     total_debt: float,
     *     total_receivable: float,
     *     active_debt_count: int,
     *     active_receivable_count: int,
     *     pending_count: int,
     *     overdue_count: int
     * }
     */
    public function getStudentStats(int $studentId): array
    {
        $student = User::findOrFail($studentId);

        // Use database aggregation instead of loading all debts into memory
        $debtsQuery = DebtRecord::where(function ($q) use ($student) {
            $q->where('creator_id', $student->id)
                ->orWhere('counterpart_id', $student->id);
        });

        $stats = [
            'total_debt' => (float) (clone $debtsQuery)->where('type', DebtType::DEBT->value)->sum('amount'),
            'total_receivable' => (float) (clone $debtsQuery)->where('type', DebtType::RECEIVABLE->value)->sum('amount'),
            'active_debt_count' => (clone $debtsQuery)->where('type', DebtType::DEBT->value)->where('status', DebtStatus::ACTIVE->value)->count(),
            'active_receivable_count' => (clone $debtsQuery)->where('type', DebtType::RECEIVABLE->value)->where('status', DebtStatus::ACTIVE->value)->count(),
            'pending_count' => (clone $debtsQuery)->where('status', DebtStatus::PENDING->value)->count(),
            'overdue_count' => (clone $debtsQuery)->where('status', DebtStatus::ACTIVE->value)
                ->where('due_date', '<', now())->count(),
        ];

        return $stats;
    }

    /**
     * Get student with all relationships
     *
     * @param int $id
     * @return User
     */
    public function getStudentWithDetails(int $id): User
    {
        return User::with([
            'studyProgram',
            'fcmTokens' => fn ($q) => $q->where('is_active', true),
            'createdDebts' => fn ($q) => $q->latest(),
            'receivedDebts' => fn ($q) => $q->latest(),
        ])->where('id', $id)
            ->where('role', UserRole::MAHASISWA)
            ->firstOrFail();
    }

    /**
     * Bulk create students
     *
     * @param array $students
     * @return int
     */
    public function bulkCreateStudents(array $students): int
    {
        $count = 0;

        foreach ($students as $data) {
            try {
                $this->createStudent($data);
                $count++;
            } catch (\Exception $e) {
                // Continue with next student if one fails
                continue;
            }
        }

        return $count;
    }

    /**
     * Export students to array
     *
     * @param array $filters
     * @return array
     */
    public function exportStudents(array $filters = []): array
    {
        $paginator = $this->getAllStudents(array_merge($filters, ['per_page' => 999]));
        $items = collect($paginator->items());
        
        return $items->map(function ($student) {
                return [
                    'id' => $student->id,
                    'nim' => $student->nim,
                    'name' => $student->name,
                    'email' => $student->email,
                    'study_program' => $student->studyProgram?->name,
                    'created_at' => $student->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Log audit action (simple)
     *
     * @param User $student
     * @param string $action
     * @return void
     */
    private function logAudit(User $student, string $action): void
    {
        // Log by system user (created_by is not set, so it's system action)
        // In real scenario, you might want to track who performed this action
    }

    /**
     * Log audit update with old and new values
     *
     * @param User $student
     * @param string $action
     * @param array $oldValues
     * @param array $newValues
     * @return void
     */
    private function logAuditUpdate(User $student, string $action, array $oldValues, array $newValues): void
    {
        $changes = [];

        foreach ($newValues as $key => $newValue) {
            if (isset($oldValues[$key]) && $oldValues[$key] !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValues[$key],
                    'new' => $newValue,
                ];
            }
        }

        if (! empty($changes)) {
            // Log would go here if we had admin user context
        }
    }
}
