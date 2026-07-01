@extends('layouts.app')

@section('content')
    <div class="space-y-6" data-auth-required>
        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Detail Hutang</h1>
                    <p class="text-sm text-slate-500">Detail lengkap transaksi hutang dan histori status.</p>
                </div>
                <a href="/debts" class="rounded-2xl bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200">Kembali ke daftar</a>
            </div>

            <div id="debtLoader" class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Memuat detail hutang...</div>

            <div id="debtContent" class="hidden mt-8 space-y-6">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                        <h2 class="text-base font-semibold text-slate-900">Informasi Transaksi</h2>
                        <dl class="mt-4 space-y-3 text-sm text-slate-700">
                            <div>
                                <dt class="font-medium text-slate-500">Tipe</dt>
                                <dd class="mt-1"><span id="debtType" class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase border">-</span></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Jumlah</dt>
                                <dd id="debtAmount" class="mt-1 font-semibold text-slate-950">-</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Status</dt>
                                <dd class="mt-1"><span id="debtStatus" class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase border">-</span></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Tanggal Transaksi</dt>
                                <dd id="debtTransactionDate" class="mt-1 text-slate-950">-</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Tanggal Jatuh Tempo</dt>
                                <dd id="debtDueDate" class="mt-1 text-slate-950">-</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                        <h2 class="text-base font-semibold text-slate-900">Pihak Terkait</h2>
                        <dl class="mt-4 space-y-3 text-sm text-slate-700">
                            <div>
                                <dt class="font-medium text-slate-500">Pembuat</dt>
                                <dd id="debtCreator" class="mt-1 text-slate-950">-</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Penerima</dt>
                                <dd id="debtCounterpart" class="mt-1 text-slate-950">-</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Alasan Penolakan</dt>
                                <dd id="debtRejectionReason" class="mt-1 text-slate-950">-</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <h2 class="text-base font-semibold text-slate-900">Deskripsi</h2>
                    <p id="debtDescription" class="mt-4 text-sm text-slate-700">-</p>
                </div>

                <div id="actionButtonsContainer" class="hidden flex flex-wrap gap-4">
                    <button id="confirmButton" class="rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">Konfirmasi</button>
                    <button id="rejectButton" class="rounded-2xl bg-rose-600 px-6 py-3 text-sm font-semibold text-white hover:bg-rose-700 transition">Tolak</button>
                    <button id="settleButton" class="rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Lunas</button>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <h2 class="text-base font-semibold text-slate-900">Histori Status</h2>
                    <div id="historyList" class="mt-4 space-y-3 text-sm text-slate-700">Memuat histori...</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        try {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}-${month}-${year}`;
        } catch (e) {
            return dateStr;
        }
    }

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'pending':
                return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'active':
                return 'bg-blue-50 text-blue-700 border-blue-200';
            case 'rejected':
                return 'bg-rose-50 text-rose-700 border-rose-200';
            case 'settled':
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            default:
                return 'bg-slate-50 text-slate-700 border-slate-200';
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const debtId = '{{ $debtRecordId }}';
        const loader = document.getElementById('debtLoader');
        const content = document.getElementById('debtContent');

        async function loadDebt() {
            try {
                const response = await window.SIMPATI.request(`/debts/${debtId}`);
                const debt = response.data;

                document.getElementById('debtAmount').textContent = `Rp ${debt.amount?.toLocaleString('id-ID') ?? '-'}`;
                
                // Set type badge class and text
                const typeEl = document.getElementById('debtType');
                typeEl.textContent = debt.type_label || '-';
                typeEl.className = `inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase border ${debt.type === 'debt' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'}`;

                // Set status badge class and text
                const statusEl = document.getElementById('debtStatus');
                statusEl.textContent = debt.status_label || '-';
                statusEl.className = `inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase border ${getStatusBadgeClass(debt.status)}`;

                document.getElementById('debtTransactionDate').textContent = formatDate(debt.transaction_date);
                document.getElementById('debtDueDate').textContent = formatDate(debt.due_date);
                document.getElementById('debtCreator').textContent = debt.creator?.name || '-';
                document.getElementById('debtCounterpart').textContent = debt.counterpart?.name || '-';
                document.getElementById('debtRejectionReason').textContent = debt.rejection_reason || '-';
                document.getElementById('debtDescription').textContent = debt.description || '-';

                // Action Buttons Visibility Logic
                const confirmBtn = document.getElementById('confirmButton');
                const rejectBtn = document.getElementById('rejectButton');
                const settleBtn = document.getElementById('settleButton');
                const actionButtonsContainer = document.getElementById('actionButtonsContainer');

                const currentUserId = parseInt(localStorage.getItem('simpati_user_id'));
                const isCounterpart = debt.counterpart?.id === currentUserId;
                const isCreator = debt.creator?.id === currentUserId;

                confirmBtn.classList.add('hidden');
                rejectBtn.classList.add('hidden');
                settleBtn.classList.add('hidden');
                actionButtonsContainer.classList.add('hidden');

                if (debt.status === 'pending') {
                    // Only counterpart of pending transaction sees Confirm & Reject
                    if (isCounterpart) {
                        confirmBtn.classList.remove('hidden');
                        rejectBtn.classList.remove('hidden');
                        actionButtonsContainer.classList.remove('hidden');
                    }
                } else if (debt.status === 'active') {
                    // Both creator and counterpart of active transaction see Settle (Lunas)
                    if (isCreator || isCounterpart) {
                        settleBtn.classList.remove('hidden');
                        actionButtonsContainer.classList.remove('hidden');
                    }
                }

                // Render history
                const historyList = document.getElementById('historyList');
                try {
                    const historyRes = await window.SIMPATI.request(`/debts/${debtId}/history`);
                    historyList.innerHTML = '';
                    const history = historyRes.data || [];
                    if (history.length > 0) {
                        history.forEach((h) => {
                            const item = document.createElement('div');
                            item.className = 'border-l-2 border-slate-200 pl-4 py-1';
                            item.innerHTML = `
                                <p class="font-medium text-slate-800">${h.old_status_label} → ${h.new_status_label}</p>
                                <p class="text-xs text-slate-500">Oleh: ${h.changed_by?.name || 'Sistem'} pada ${formatDate(h.created_at)}</p>
                                ${h.reason ? `<p class="text-xs italic text-slate-600 mt-1">Alasan: "${h.reason}"</p>` : ''}
                            `;
                            historyList.appendChild(item);
                        });
                    } else {
                        historyList.innerHTML = '<p class="text-sm text-slate-500">Tidak ada histori status yang ditampilkan.</p>';
                    }
                } catch (historyErr) {
                    console.error('Gagal memuat histori', historyErr);
                    historyList.innerHTML = '<p class="text-sm text-slate-500">Gagal memuat histori status.</p>';
                }

                content.classList.remove('hidden');
                loader.classList.add('hidden');
            } catch (error) {
                await window.SIMPATI.handleUnauthorized(error);
                loader.textContent = 'Gagal memuat detail hutang.';
            }
        }

        document.getElementById('confirmButton').addEventListener('click', async () => {
            const confirmed = confirm('Yakin ingin mengonfirmasi transaksi ini?');
            if (!confirmed) return;
            try {
                await window.SIMPATI.request(`/debts/${debtId}/confirm`, { method: 'POST' });
                await loadDebt();
            } catch (error) {
                await window.SIMPATI.handleUnauthorized(error);
                alert(error.message || 'Aksi konfirmasi gagal.');
            }
        });

        document.getElementById('rejectButton').addEventListener('click', async () => {
            const reason = prompt('Masukkan alasan penolakan (minimal 10 karakter):');
            if (reason === null) return;
            if (reason.trim().length < 10) {
                alert('Alasan penolakan minimal 10 karakter.');
                return;
            }
            try {
                await window.SIMPATI.request(`/debts/${debtId}/reject`, {
                    method: 'POST',
                    body: { rejection_reason: reason.trim() }
                });
                await loadDebt();
            } catch (error) {
                await window.SIMPATI.handleUnauthorized(error);
                alert(error.message || 'Aksi tolak gagal.');
            }
        });

        document.getElementById('settleButton').addEventListener('click', async () => {
            const confirmed = confirm('Yakin ingin menandai transaksi ini sebagai Lunas?');
            if (!confirmed) return;
            try {
                await window.SIMPATI.request(`/debts/${debtId}/settle`, { method: 'POST' });
                await loadDebt();
            } catch (error) {
                await window.SIMPATI.handleUnauthorized(error);
                alert(error.message || 'Aksi pelunasan gagal.');
            }
        });

        await loadDebt();
    });
</script>
@endpush
