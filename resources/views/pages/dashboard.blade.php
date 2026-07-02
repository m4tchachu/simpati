@extends('layouts.app')

@section('content')
    <div class="space-y-6" data-auth-required>
        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
                    <p class="text-sm text-slate-500">Ringkasan aktivitas hutang dan notifikasi Anda.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-sm">
                    <span class="rounded-2xl bg-slate-100 px-4 py-2 text-slate-700 font-semibold">Status: Aktif</span>
                    <a href="/debts" class="rounded-2xl bg-slate-900 px-4 py-2 text-white hover:bg-slate-800 font-semibold transition">Lihat Hutang</a>
                </div>
            </div>

            <div id="dashboardLoader" class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Memuat data dashboard...</div>

            <div id="dashboardContent" class="hidden space-y-6 mt-8">
                <!-- Admin user view -->
                <div id="adminDashboard" class="hidden space-y-6">
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Total Mahasiswa</p>
                            <p id="cardTotalStudents" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Mahasiswa Aktif</p>
                            <p id="cardActiveStudents" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Mahasiswa Non-aktif</p>
                            <p id="cardInactiveStudents" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Export Data</p>
                            <p class="mt-3"><a href="#" id="exportStudents" class="inline-block rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Export CSV</a></p>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-6 border border-slate-200">
                        <h2 class="text-base font-semibold text-slate-900">Statistik Program Studi</h2>
                        <div id="programStats" class="mt-4 text-sm text-slate-650">Memuat...</div>
                    </div>
                </div>

                <!-- Mahasiswa user view -->
                <div id="mahasiswaDashboard" class="hidden space-y-6">
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Total Hutang</p>
                            <p id="cardTotalDebt" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Total Piutang</p>
                            <p id="cardTotalReceivable" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Hutang Jatuh Tempo</p>
                            <p id="cardUpcoming" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm text-slate-500 font-medium">Notifikasi Baru</p>
                            <p id="cardNotifications" class="mt-3 text-2xl font-semibold text-slate-900">-</p>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="rounded-3xl bg-slate-950 p-6 text-white">
                            <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-400">Ringkasan Notifikasi</h2>
                            <div id="notificationSummary" class="mt-4 space-y-4 text-sm leading-6">Memuat...</div>
                        </div>
                        <div class="col-span-2 rounded-3xl bg-white border border-slate-200 p-6">
                            <h2 class="text-base font-semibold text-slate-900">Transaksi Terbaru</h2>
                            <div id="recentTransactions" class="mt-4 space-y-3 text-sm text-slate-600">Memuat...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const data = await window.SIMPATI.fetchDashboard();
            document.getElementById('dashboardLoader').classList.add('hidden');
            document.getElementById('dashboardContent').classList.remove('hidden');
            // Decide view based on role
            const role = localStorage.getItem('simpati_role');
            if (role === 'admin') {
                document.getElementById('adminDashboard').classList.remove('hidden');
                document.getElementById('mahasiswaDashboard').classList.add('hidden');

                // Fill student stats if available
                document.getElementById('cardTotalStudents').textContent = data.student_stats?.total_students ?? '-';
                document.getElementById('cardActiveStudents').textContent = data.student_stats?.active_students ?? '-';
                document.getElementById('cardInactiveStudents').textContent = data.student_stats?.inactive_students ?? '-';
                const byProgram = data.student_stats?.by_program || {};
                const totalStudents = data.student_stats?.total_students || 1;
                const programStatsContainer = document.getElementById('programStats');
                programStatsContainer.innerHTML = '';
                
                if (Object.keys(byProgram).length === 0) {
                    programStatsContainer.innerHTML = '<p class="text-slate-500">Tidak ada data program studi.</p>';
                } else {
                    const gridDiv = document.createElement('div');
                    gridDiv.className = 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-4';
                    
                    Object.entries(byProgram).forEach(([programName, count]) => {
                        const percent = ((count / totalStudents) * 100).toFixed(0);
                        const card = document.createElement('div');
                        card.className = 'rounded-3xl border border-slate-200 bg-slate-50 p-6 flex flex-col justify-between';
                        card.innerHTML = `
                            <div>
                                <p class="text-sm font-semibold text-slate-900">${programName}</p>
                                <p class="mt-3 text-2xl font-bold text-slate-900">${count} <span class="text-xs font-normal text-slate-500">Mahasiswa</span></p>
                            </div>
                            <div class="mt-4">
                                <div class="flex justify-between text-xs text-slate-500 mb-1">
                                    <span>Persentase</span>
                                    <span class="font-semibold">${percent}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5">
                                    <div class="bg-slate-900 h-1.5 rounded-full" style="width: ${percent}%"></div>
                                </div>
                            </div>
                        `;
                        gridDiv.appendChild(card);
                    });
                    programStatsContainer.appendChild(gridDiv);
                }

                // Export functionality
                const exportBtn = document.getElementById('exportStudents');
                if (exportBtn) {
                    exportBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        try {
                            const res = await window.SIMPATI.request('/students/export', { method: 'POST' });
                            const students = res.data || [];
                            if (students.length === 0) {
                                alert('Tidak ada data mahasiswa untuk diekspor.');
                                return;
                            }
                            
                            // Convert to CSV
                            const headers = ['ID', 'NIM', 'Nama', 'Email', 'Program Studi', 'Tanggal Dibuat'];
                            const csvRows = [headers.join(',')];
                            
                            students.forEach(s => {
                                const row = [
                                    s.id,
                                    `"${s.nim || ''}"`,
                                    `"${s.name || ''}"`,
                                    `"${s.email || ''}"`,
                                    `"${s.study_program || ''}"`,
                                    `"${s.created_at || ''}"`
                                ];
                                csvRows.push(row.join(','));
                            });
                            
                            const csvContent = csvRows.join('\n');
                            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                            const url = URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.setAttribute('href', url);
                            link.setAttribute('download', 'data_mahasiswa_simpati.csv');
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } catch (err) {
                            alert('Gagal mengekspor data: ' + err.message);
                        }
                    });
                }
            } else {
                document.getElementById('mahasiswaDashboard').classList.remove('hidden');
                document.getElementById('adminDashboard').classList.add('hidden');

                document.getElementById('cardTotalDebt').textContent = data.debt_stats?.total_debt ?? '-';
                document.getElementById('cardTotalReceivable').textContent = data.debt_stats?.total_receivable ?? '-';
                document.getElementById('cardUpcoming').textContent = data.upcoming_debts?.length ?? 0;
                document.getElementById('cardNotifications').textContent = data.notifications?.unread_count ?? 0;

                // Render dynamic notifications with details inside dashboard notification card
                const summaryContainer = document.getElementById('notificationSummary');
                summaryContainer.innerHTML = '';

                const notificationsList = data.notifications?.latest || [];
                if (notificationsList.length > 0) {
                    notificationsList.forEach((n) => {
                        const item = document.createElement('a');
                        item.href = `/notifications`;
                        item.className = 'block border-b border-slate-800 pb-3 last:border-0 last:pb-0 mb-3 last:mb-0 hover:bg-slate-900/50 rounded p-1 transition';
                        item.innerHTML = `
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">${n.title}</p>
                            <p class="text-sm font-medium text-slate-200 mt-1">${n.message}</p>
                            <p class="text-[10px] text-slate-500 mt-1">${n.created_at}</p>
                        `;
                        summaryContainer.appendChild(item);
                    });
                } else {
                    summaryContainer.innerHTML = '<p class="text-sm text-slate-400">Tidak ada notifikasi terbaru.</p>';
                }

                const transactionsContainer = document.getElementById('recentTransactions');
                transactionsContainer.textContent = '';

                if (data.recent_transactions?.length > 0) {
                    const getStatusClass = (status) => {
                        switch (status) {
                            case 'pending': return 'bg-amber-50 text-amber-700 border-amber-200';
                            case 'active': return 'bg-blue-50 text-blue-700 border-blue-200';
                            case 'rejected': return 'bg-rose-50 text-rose-700 border-rose-200';
                            case 'settled': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            default: return 'bg-slate-50 text-slate-700 border-slate-200';
                        }
                    };

                    data.recent_transactions.forEach((item) => {
                        const card = document.createElement('div');
                        card.className = 'rounded-3xl bg-slate-50 p-4 border border-slate-200';
                        
                        const typeClass = item.type === 'debt' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        
                        card.innerHTML = `
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">${item.description ?? 'Transaksi baru'}</p>
                                    <div class="flex flex-wrap gap-2 items-center mt-2 text-xs">
                                        <span class="rounded-full px-2.5 py-0.5 font-semibold border uppercase ${typeClass}">${item.type_label ?? ''}</span>
                                        <span class="rounded-full px-2.5 py-0.5 font-semibold border uppercase ${getStatusClass(item.status)}">${item.status_label ?? ''}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-slate-900">Rp ${item.amount?.toLocaleString('id-ID') ?? '0'}</span>
                            </div>
                        `;
                        transactionsContainer.append(card);
                    });
                } else {
                    transactionsContainer.textContent = 'Tidak ada transaksi terbaru.';
                }
            }
        } catch (error) {
            await window.SIMPATI.handleUnauthorized(error);
            document.getElementById('dashboardLoader').textContent = 'Gagal memuat data dashboard.';
        }
    });
</script>
@endpush
