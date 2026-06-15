<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private StudentService $studentService,
    ) {}

    /**
     * Get all students with pagination and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'study_program_id' => 'nullable|integer|exists:study_programs,id',
            'sort' => 'nullable|string|in:name,nim,email,created_at',
            'order' => 'nullable|string|in:asc,desc',
        ]);

        $students = $this->studentService->getAllStudents($filters);

        return response()->json([
            'message' => 'Students retrieved successfully',
            'data' => StudentResource::collection($students),
            'pagination' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ],
        ], 200);
    }

    /**
     * Show single student
     *
     * @param User $student
     * @return JsonResponse
     */
    public function show(User $student): JsonResponse
    {
        $this->authorize('view', $student);

        $student = $this->studentService->getStudentWithDetails($student->id);

        return response()->json([
            'message' => 'Student retrieved successfully',
            'data' => new StudentResource($student),
        ], 200);
    }

    /**
     * Create new student
     *
     * @param StoreStudentRequest $request
     * @return JsonResponse
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $student = $this->studentService->createStudent(
                $request->validated()
            );

            return response()->json([
                'message' => 'Student created successfully',
                'data' => new StudentResource($student),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create student',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update student
     *
     * @param UpdateStudentRequest $request
     * @param User $student
     * @return JsonResponse
     */
    public function update(UpdateStudentRequest $request, User $student): JsonResponse
    {
        try {
            $updated = $this->studentService->updateStudent(
                $student->id,
                $request->validated()
            );

            return response()->json([
                'message' => 'Student updated successfully',
                'data' => new StudentResource($updated),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update student',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete student
     *
     * @param User $student
     * @return JsonResponse
     */
    public function destroy(User $student): JsonResponse
    {
        $this->authorize('delete', $student);

        try {
            $this->studentService->deleteStudent($student->id);

            return response()->json([
                'message' => 'Student deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete student',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Search students
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('search', User::class);

        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $students = $this->studentService->searchStudents(
            $request->input('q'),
            $request->input('limit', 10)
        );

        return response()->json([
            'message' => 'Students search results',
            'data' => $students,
        ], 200);
    }

    /**
     * Get student statistics
     *
     * @param User $student
     * @return JsonResponse
     */
    public function stats(User $student): JsonResponse
    {
        $this->authorize('view', $student);

        $stats = $this->studentService->getStudentStats($student->id);

        return response()->json([
            'message' => 'Student statistics retrieved',
            'data' => $stats,
        ], 200);
    }

    /**
     * Export students to array format
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', User::class);

        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'study_program_id' => 'nullable|integer|exists:study_programs,id',
        ]);

        $students = $this->studentService->exportStudents($filters);

        return response()->json([
            'message' => 'Students exported successfully',
            'data' => $students,
            'count' => count($students),
        ], 200);
    }
}
