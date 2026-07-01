<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Models\DebtRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private DebtRecordService $debtRecordService,
        private NotificationService $notificationService,
        private StudentService $studentService,
    ) {}

    /**
     * Get complete dashboard data for user
     *
     * @param User $user
     * @return array
     */
    public function getDashboard(User $user): array
    {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];

        if ($user->isAdmin()) {
            $totalStudents = User::where('role', \App\Enums\UserRole::MAHASISWA->value)->count();
            $activeStudents = User::where('role', \App\Enums\UserRole::MAHASISWA->value)
                ->where('is_active', true)
                ->count();
            $inactiveStudents = User::where('role', \App\Enums\UserRole::MAHASISWA->value)
                ->where('is_active', false)
                ->count();

            $byProgram = DB::table('users')
                ->join('study_programs', 'users.study_program_id', '=', 'study_programs.id')
                ->where('users.role', \App\Enums\UserRole::MAHASISWA->value)
                ->whereNull('users.deleted_at')
                ->groupBy('study_programs.name')
                ->select('study_programs.name', DB::raw('count(*) as count'))
                ->pluck('count', 'study_programs.name')
                ->toArray();

            return [
                'user' => $userData,
                'student_stats' => [
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'inactive_students' => $inactiveStudents,
                    'by_program' => $byProgram,
                ],
            ];
        }

        return [
            'user' => $userData,
            'debt_stats' => $this->getDebtStats($user),
            'notifications' => $this->getNotificationSummary($user),
            'recent_transactions' => $this->getRecentTransactions($user, 5),
            'upcoming_debts' => $this->getUpcomingDebtDates($user, 7),
            'overdue_debts' => $this->getOverdueDebts($user),
            'summary_cards' => $this->getSummaryCards($user),
            'charts_data' => $this->getChartsData($user),
        ];
    }

    /**
     * Get debt statistics for dashboard
     * Uses database aggregation to avoid N+1 queries
     *
     * @param User $user
     * @return array
     */
    public function getDebtStats(User $user): array
    {
        // Debts for this user (where they owe money)
        $debtSumQuery = DebtRecord::where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)->where('type', DebtType::DEBT->value);
            })->orWhere(function ($q) use ($user) {
                $q->where('counterpart_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
            });
        });

        // Receivables for this user (where they are owed money)
        $receivableSumQuery = DebtRecord::where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
            })->orWhere(function ($q) use ($user) {
                $q->where('counterpart_id', $user->id)->where('type', DebtType::DEBT->value);
            });
        });

        $totalDebt = (float) $debtSumQuery->clone()->sum('amount');
        $totalReceivable = (float) $receivableSumQuery->clone()->sum('amount');

        $activeDebtAmount = (float) $debtSumQuery->clone()
            ->where('status', DebtStatus::ACTIVE->value)
            ->sum('amount');

        $activeReceivableAmount = (float) $receivableSumQuery->clone()
            ->where('status', DebtStatus::ACTIVE->value)
            ->sum('amount');

        // All debts query involving this user for general status counts
        $allDebtsQuery = DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        });

        return [
            'total_debt' => $totalDebt,
            'total_receivable' => $totalReceivable,
            'net_balance' => $totalReceivable - $totalDebt,
            'active_debt_count' => $debtSumQuery->clone()
                ->where('status', DebtStatus::ACTIVE->value)
                ->count(),
            'active_receivable_count' => $receivableSumQuery->clone()
                ->where('status', DebtStatus::ACTIVE->value)
                ->count(),
            'active_debt_amount' => $activeDebtAmount,
            'active_receivable_amount' => $activeReceivableAmount,
            'pending_count' => $allDebtsQuery->clone()
                ->where('status', DebtStatus::PENDING->value)
                ->count(),
            'overdue_count' => $debtSumQuery->clone()
                ->where('status', DebtStatus::ACTIVE->value)
                ->where('due_date', '<', now())
                ->count(),
            'settled_count' => $allDebtsQuery->clone()
                ->where('status', DebtStatus::SETTLED->value)
                ->count(),
            'rejected_count' => $allDebtsQuery->clone()
                ->where('status', DebtStatus::REJECTED->value)
                ->count(),
        ];
    }

    /**
     * Get notification summary
     *
     * @param User $user
     * @return array
     */
    public function getNotificationSummary(User $user): array
    {
        $stats = $this->notificationService->getNotificationStats($user);
        
        $stats['latest'] = \App\Models\Notification::where('user_id', $user->id)
            ->with('type')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            })
            ->toArray();

        return $stats;
    }

    /**
     * Get recent transactions
     *
     * @param User $user
     * @param int $limit
     * @return array
     */
    public function getRecentTransactions(User $user, int $limit = 5): array
    {
        return DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        })->with('creator', 'counterpart')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($debt) use ($user) {
                $isCreator = $debt->creator_id === $user->id;
                $perspectiveType = $isCreator ? $debt->type : ($debt->type === DebtType::DEBT ? DebtType::RECEIVABLE : DebtType::DEBT);

                return [
                    'id' => $debt->id,
                    'type' => $debt->type->value,
                    'type_label' => $perspectiveType->label(),
                    'amount' => $debt->amount,
                    'status' => $debt->status->value,
                    'status_label' => $debt->status->label(),
                    'status_color' => $debt->status->color(),
                    'description' => substr($debt->description, 0, 50) . (strlen($debt->description) > 50 ? '...' : ''),
                    'creator' => $debt->creator->name,
                    'counterpart' => $debt->counterpart->name,
                    'role' => $isCreator ? 'creator' : 'counterpart',
                    'created_at' => $debt->created_at->diffForHumans(),
                    'due_date' => $debt->due_date,
                ];
            })
            ->toArray();
    }

    /**
     * Get upcoming debt due dates
     *
     * @param User $user
     * @param int $days
     * @return array
     */
    public function getUpcomingDebtDates(User $user, int $days = 7): array
    {
        return DebtRecord::where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)->where('type', DebtType::DEBT->value);
            })->orWhere(function ($q) use ($user) {
                $q->where('counterpart_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
            });
        })->where('status', DebtStatus::ACTIVE)
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->with('creator', 'counterpart')
            ->orderBy('due_date')
            ->get()
            ->map(function ($debt) use ($user) {
                $daysUntilDue = now()->diffInDays($debt->due_date);
                $isPastDue = $debt->due_date->isPast();

                return [
                    'id' => $debt->id,
                    'amount' => $debt->amount,
                    'creator' => $debt->creator->name,
                    'counterpart' => $debt->counterpart->name,
                    'role' => $debt->creator_id === $user->id ? 'creator' : 'counterpart',
                    'due_date' => $debt->due_date,
                    'days_until_due' => $daysUntilDue,
                    'is_past_due' => $isPastDue,
                    'urgency' => $this->calculateUrgency($daysUntilDue),
                ];
            })
            ->toArray();
    }

    /**
     * Get overdue debts
     *
     * @param User $user
     * @return array
     */
    public function getOverdueDebts(User $user): array
    {
        return DebtRecord::where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)->where('type', DebtType::DEBT->value);
            })->orWhere(function ($q) use ($user) {
                $q->where('counterpart_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
            });
        })->where('status', DebtStatus::ACTIVE)
            ->where('due_date', '<', now())
            ->with('creator', 'counterpart')
            ->orderBy('due_date')
            ->get()
            ->map(function ($debt) use ($user) {
                return [
                    'id' => $debt->id,
                    'amount' => $debt->amount,
                    'creator' => $debt->creator->name,
                    'counterpart' => $debt->counterpart->name,
                    'role' => $debt->creator_id === $user->id ? 'creator' : 'counterpart',
                    'due_date' => $debt->due_date,
                    'days_overdue' => now()->diffInDays($debt->due_date),
                ];
            })
            ->toArray();
    }

    /**
     * Get summary cards data
     *
     * @param User $user
     * @return array
     */
    public function getSummaryCards(User $user): array
    {
        $stats = $this->getDebtStats($user);

        return [
            [
                'title' => 'Total Hutang',
                'value' => 'Rp ' . number_format($stats['total_debt'], 0, ',', '.'),
                'color' => 'danger',
                'icon' => 'trending-down',
                'change' => $this->getMonthlyChange($user, DebtType::DEBT),
            ],
            [
                'title' => 'Total Piutang',
                'value' => 'Rp ' . number_format($stats['total_receivable'], 0, ',', '.'),
                'color' => 'success',
                'icon' => 'trending-up',
                'change' => $this->getMonthlyChange($user, DebtType::RECEIVABLE),
            ],
            [
                'title' => 'Saldo Bersih',
                'value' => 'Rp ' . number_format($stats['net_balance'], 0, ',', '.'),
                'color' => $stats['net_balance'] >= 0 ? 'success' : 'danger',
                'icon' => 'balance',
                'change' => null,
            ],
            [
                'title' => 'Transaksi Menunggu',
                'value' => $stats['pending_count'],
                'color' => 'warning',
                'icon' => 'clock',
                'change' => null,
            ],
            [
                'title' => 'Transaksi Jatuh Tempo',
                'value' => $stats['overdue_count'],
                'color' => $stats['overdue_count'] > 0 ? 'danger' : 'success',
                'icon' => 'alert-circle',
                'change' => null,
            ],
        ];
    }

    /**
     * Get charts data for visualization
     *
     * @param User $user
     * @return array
     */
    public function getChartsData(User $user): array
    {
        return [
            'debt_status_distribution' => $this->getDebtStatusDistribution($user),
            'debt_type_distribution' => $this->getDebtTypeDistribution($user),
            'monthly_trend' => $this->getMonthlyTrend($user),
            'top_counterparts' => $this->getTopCounterparts($user, 5),
        ];
    }

    /**
     * Get debt status distribution chart data
     *
     * @param User $user
     * @return array
     */
    private function getDebtStatusDistribution(User $user): array
    {
        $statuses = [
            DebtStatus::PENDING,
            DebtStatus::ACTIVE,
            DebtStatus::SETTLED,
            DebtStatus::REJECTED,
        ];

        $data = [];
        foreach ($statuses as $status) {
            $count = DebtRecord::where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                    ->orWhere('counterpart_id', $user->id);
            })->where('status', $status)->count();

            if ($count > 0) {
                $data[] = [
                    'name' => $status->label(),
                    'value' => $count,
                    'color' => $status->color(),
                ];
            }
        }

        return $data;
    }

    /**
     * Get debt type distribution chart data
     *
     * @param User $user
     * @return array
     */
    private function getDebtTypeDistribution(User $user): array
    {
        $debtQuery = DebtRecord::where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)->where('type', DebtType::DEBT->value);
            })->orWhere(function ($q) use ($user) {
                $q->where('counterpart_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
            });
        });

        $receivableQuery = DebtRecord::where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
            })->orWhere(function ($q) use ($user) {
                $q->where('counterpart_id', $user->id)->where('type', DebtType::DEBT->value);
            });
        });

        return [
            [
                'name' => DebtType::DEBT->label(),
                'value' => (float) $debtQuery->clone()->sum('amount'),
                'count' => $debtQuery->clone()->count(),
                'color' => '#dc3545',
            ],
            [
                'name' => DebtType::RECEIVABLE->label(),
                'value' => (float) $receivableQuery->clone()->sum('amount'),
                'count' => $receivableQuery->clone()->count(),
                'color' => '#28a745',
            ],
        ];
    }

    /**
     * Get monthly trend data
     *
     * @param User $user
     * @return array
     */
    private function getMonthlyTrend(User $user): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'label' => $date->format('Y-m'),
            ];
        }

        $trends = [];
        foreach ($months as $month) {
            $startDate = Carbon::createFromFormat('Y-m', $month['label'])->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month['label'])->endOfMonth();

            $debtAmount = DebtRecord::where(function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('creator_id', $user->id)->where('type', DebtType::DEBT->value);
                })->orWhere(function ($q) use ($user) {
                    $q->where('counterpart_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
                });
            })->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $receivableAmount = DebtRecord::where(function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('creator_id', $user->id)->where('type', DebtType::RECEIVABLE->value);
                })->orWhere(function ($q) use ($user) {
                    $q->where('counterpart_id', $user->id)->where('type', DebtType::DEBT->value);
                });
            })->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $trends[] = [
                'month' => $month['month'],
                'debt' => (float) $debtAmount,
                'receivable' => (float) $receivableAmount,
                'net' => (float) ($receivableAmount - $debtAmount),
            ];
        }

        return $trends;
    }

    /**
     * Get top counterparts
     *
     * @param User $user
     * @param int $limit
     * @return array
     */
    private function getTopCounterparts(User $user, int $limit = 5): array
    {
        $counterparts = DebtRecord::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)
                ->orWhere('counterpart_id', $user->id);
        })->select('counterpart_id', 'creator_id')
            ->addSelect(DB::raw('COUNT(*) as transaction_count'))
            ->addSelect(DB::raw('SUM(CASE WHEN creator_id = ? THEN amount ELSE 0 END) as total_created'))
            ->addSelect(DB::raw('SUM(CASE WHEN counterpart_id = ? THEN amount ELSE 0 END) as total_received'))
            ->setBindings([$user->id, $user->id], 'select')
            ->with(['creator', 'counterpart'])
            ->groupBy(['counterpart_id', 'creator_id'])
            ->orderBy('transaction_count', 'desc')
            ->limit($limit)
            ->get();

        return $counterparts->map(function ($item) use ($user) {
            $counterpartId = $item->counterpart_id === $user->id ? $item->creator_id : $item->counterpart_id;
            $counterpart = User::find($counterpartId);

            return [
                'id' => $counterpart->id,
                'name' => $counterpart->name,
                'transaction_count' => $item->transaction_count,
                'total_amount' => $item->total_created + $item->total_received,
            ];
        })->toArray();
    }

    /**
     * Calculate urgency level based on days until due
     *
     * @param int $daysUntilDue
     * @return string
     */
    private function calculateUrgency(int $daysUntilDue): string
    {
        if ($daysUntilDue <= 0) {
            return 'critical';
        } elseif ($daysUntilDue <= 1) {
            return 'urgent';
        } elseif ($daysUntilDue <= 3) {
            return 'high';
        } elseif ($daysUntilDue <= 7) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get monthly change percentage
     *
     * @param User $user
     * @param DebtType $type
     * @return array|null
     */
    private function getMonthlyChange(User $user, DebtType $type): ?array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $thisMonthAmount = DebtRecord::where(function ($query) use ($user, $type) {
            $query->where(function ($q) use ($user, $type) {
                $q->where('creator_id', $user->id)->where('type', $type->value);
            })->orWhere(function ($q) use ($user, $type) {
                $oppositeType = $type === DebtType::DEBT ? DebtType::RECEIVABLE->value : DebtType::DEBT->value;
                $q->where('counterpart_id', $user->id)->where('type', $oppositeType);
            });
        })->whereBetween('created_at', [$thisMonth, now()])
            ->sum('amount');

        $lastMonthAmount = DebtRecord::where(function ($query) use ($user, $type) {
            $query->where(function ($q) use ($user, $type) {
                $q->where('creator_id', $user->id)->where('type', $type->value);
            })->orWhere(function ($q) use ($user, $type) {
                $oppositeType = $type === DebtType::DEBT ? DebtType::RECEIVABLE->value : DebtType::DEBT->value;
                $q->where('counterpart_id', $user->id)->where('type', $oppositeType);
            });
        })->whereBetween('created_at', [$lastMonth, $thisMonth])
            ->sum('amount');

        if ($lastMonthAmount == 0) {
            return null;
        }

        $percentage = (($thisMonthAmount - $lastMonthAmount) / $lastMonthAmount) * 100;

        return [
            'percentage' => round($percentage, 2),
            'trend' => $percentage > 0 ? 'up' : 'down',
        ];
    }
}
