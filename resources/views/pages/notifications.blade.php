@extends('layouts.app')

@section('content')
    <div class="space-y-6" data-auth-required data-requires-role="mahasiswa">
        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
            <h1 class="text-2xl font-semibold text-slate-900">Notifikasi</h1>
            <p class="text-sm text-slate-500 mt-1">Pantau notifikasi terbaru Anda.</p>
        </div>

        <div id="notificationList" class="space-y-4"></div>
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

    async function loadNotifications() {
        try {
            const result = await window.SIMPATI.request('/notifications');
            const list = document.getElementById('notificationList');
            list.innerHTML = '';

            if (!result.data?.length) {
                list.innerHTML = '<div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Tidak ada notifikasi.</div>';
                return;
            }

            result.data.forEach((item) => {
                const card = document.createElement('a');
                // Direct redirect to detail hutang/piutang
                card.href = item.debt_record?.id ? `/debts/${item.debt_record.id}` : '#';
                card.className = 'block rounded-3xl border border-slate-200 bg-white p-6 shadow-sm hover:bg-slate-50 transition cursor-pointer';
                card.innerHTML = `
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-4">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold border uppercase bg-slate-100 text-slate-700 border-slate-200">
                                ${item.type?.name ?? 'Notifikasi'}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">${formatDate(item.created_at)}</span>
                        </div>
                        <h2 class="text-base font-semibold text-slate-900 mt-1">${item.message ?? '-'}</h2>
                    </div>
                `;
                list.appendChild(card);
            });
        } catch (error) {
            await window.SIMPATI.handleUnauthorized(error);
            document.getElementById('notificationList').innerHTML = '<div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-rose-800">Gagal memuat notifikasi.</div>';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadNotifications();
    });
</script>
@endpush
