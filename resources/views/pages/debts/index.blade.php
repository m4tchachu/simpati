@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70 md:flex-row md:items-center md:justify-between" data-auth-required data-requires-role="mahasiswa">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Daftar Hutang</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola hutang Anda, lihat status, dan kelola action pendukung.</p>
            </div>
            <a href="/debts/new" data-auth-only data-role="mahasiswa" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Buat Hutang Baru</a>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700">Cari hutang</label>
                    <input id="debtSearch" type="search" placeholder="Cari dengan kata kunci..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                </div>
                <button id="searchButton" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">Cari</button>
            </div>
        </div>

        <div id="debtList" class="space-y-4"></div>
    </div>
@endsection

@push('scripts')
<script>
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

    async function renderDebtItems(debts = []) {
        const list = document.getElementById('debtList');
        list.innerHTML = '';

        if (!debts.length) {
            list.innerHTML = '<div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-500">Tidak ada hutang ditemukan.</div>';
            return;
        }

        debts.forEach((item) => {
            const card = document.createElement('article');
            card.className = 'rounded-3xl border border-slate-200 bg-white p-6 shadow-sm';
            
            const typeBadgeColor = item.type === 'debt' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200';
            
            card.innerHTML = `
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap gap-2 items-center text-xs mb-2">
                            <span class="rounded-full px-2.5 py-1 font-semibold border uppercase ${typeBadgeColor}">${item.type_label || 'Jenis'}</span>
                            <span class="rounded-full px-2.5 py-1 font-semibold border uppercase ${getStatusBadgeClass(item.status)}">${item.status_label || '-'}</span>
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900">${item.description || 'Tanpa deskripsi'}</h2>
                        <p class="mt-2 text-sm text-slate-500">${item.creator?.name || '-'} → ${item.counterpart?.name || '-'}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        <span class="rounded-full bg-slate-100 px-3.5 py-1.5 font-semibold text-slate-700 border border-slate-200">Rp ${item.amount?.toLocaleString('id-ID') ?? '0'}</span>
                        <a href="/debts/${item.id}" class="rounded-2xl bg-slate-900 px-4 py-2 text-white hover:bg-slate-800 text-xs font-semibold transition">Detail</a>
                    </div>
                </div>
            `;
            list.appendChild(card);
        });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const searchInput = document.getElementById('debtSearch');
        const searchButton = document.getElementById('searchButton');

        async function loadDebts(query = '') {
            try {
                let endpoint = '/debts';
                if (query) {
                    endpoint = `/debts/search?q=${encodeURIComponent(query)}`;
                }

                const response = await window.SIMPATI.request(endpoint);
                renderDebtItems(response.data || []);
            } catch (error) {
                await window.SIMPATI.handleUnauthorized(error);
                document.getElementById('debtList').innerHTML = '<div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-rose-800">Gagal memuat daftar hutang.</div>';
            }
        }

        searchButton.addEventListener('click', async () => {
            await loadDebts(searchInput.value.trim());
        });

        await loadDebts();
    });
</script>
@endpush
