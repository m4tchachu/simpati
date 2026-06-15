<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;  
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    /**
     * Get complete dashboard data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $dashboard = $this->dashboardService->getDashboard($request->user());

            return response()->json([
                'message' => 'Dashboard data retrieved successfully',
                'data' => $dashboard,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get debt statistics only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function debtStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getDebtStats($request->user());

            return response()->json([
                'message' => 'Debt statistics retrieved',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve debt statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification summary only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function notificationSummary(Request $request): JsonResponse
    {
        try {
            $summary = $this->dashboardService->getNotificationSummary($request->user());

            return response()->json([
                'message' => 'Notification summary retrieved',
                'data' => $summary,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve notification summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent transactions only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recentTransactions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $transactions = $this->dashboardService->getRecentTransactions(
                $request->user(),
                $request->input('limit', 5)
            );

            return response()->json([
                'message' => 'Recent transactions retrieved',
                'data' => $transactions,
                'count' => count($transactions),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve recent transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upcoming due dates only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upcomingDebts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'nullable|integer|min:1|max:90',
            ]);

            $upcoming = $this->dashboardService->getUpcomingDebtDates(
                $request->user(),
                $request->input('days', 7)
            );

            return response()->json([
                'message' => 'Upcoming debts retrieved',
                'data' => $upcoming,
                'count' => count($upcoming),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve upcoming debts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overdue debts only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overdueDebts(Request $request): JsonResponse
    {
        try {
            $overdue = $this->dashboardService->getOverdueDebts($request->user());

            return response()->json([
                'message' => 'Overdue debts retrieved',
                'data' => $overdue,
                'count' => count($overdue),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve overdue debts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get summary cards only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summaryCards(Request $request): JsonResponse
    {
        try {
            $cards = $this->dashboardService->getSummaryCards($request->user());

            return response()->json([
                'message' => 'Summary cards retrieved',
                'data' => $cards,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve summary cards',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get charts data only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chartsData(Request $request): JsonResponse
    {
        try {
            $charts = $this->dashboardService->getChartsData($request->user());

            return response()->json([
                'message' => 'Charts data retrieved',
                'data' => $charts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve charts data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
