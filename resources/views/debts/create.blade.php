@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-xl rounded-3xl bg-white p-8 shadow-lg shadow-slate-200/80 md:p-12" data-auth-required data-requires-role="mahasiswa">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold text-slate-900">Buat Hutang / Piutang</h1>
            <p class="mt-2 text-sm text-slate-500">Isi detail transaksi hutang/piutang.</p>
        </div>

        <div id="alert" class="hidden rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"></div>

        <form id="debtCreateForm" class="space-y-6">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Tipe</label>
                <select id="type" class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none">
                    <option value="debt">Hutang</option>
                    <option value="receivable">Piutang</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Cari Mahasiswa Tujuan</label>
                <div class="flex gap-2">
                    <input id="searchCounterpartInput" type="text" placeholder="Masukkan minimal 2 karakter nama/NIM..." class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none flex-1" />
                    <button type="button" id="btnSearchCounterpart" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Cari</button>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Pilih Mahasiswa Tujuan</label>
                <select id="counterpart" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none">
                    <option value="">Cari mahasiswa terlebih dahulu...</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Jumlah (Rp)</label>
                <input id="amount" type="number" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Deskripsi</label>
                <textarea id="description" class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none"></textarea>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Transaksi</label>
                <input id="transaction_date" type="date" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Jatuh Tempo</label>
                <input id="due_date" type="date" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Buat</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('debtCreateForm');
        const alertBox = document.getElementById('alert');
        const btnSearch = document.getElementById('btnSearchCounterpart');
        const searchInput = document.getElementById('searchCounterpartInput');
        const select = document.getElementById('counterpart');
        const transactionDateInput = document.getElementById('transaction_date');

        // Set default transaction date to today
        transactionDateInput.value = new Date().toISOString().split('T')[0];

        btnSearch.addEventListener('click', async () => {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                alert('Masukkan minimal 2 karakter untuk mencari.');
                return;
            }

            try {
                select.innerHTML = '<option value="">Memuat...</option>';
                const res = await window.SIMPATI.request(`/students/search?q=${encodeURIComponent(query)}`);
                select.innerHTML = '<option value="">Pilih Mahasiswa</option>';
                if (res.data && res.data.length > 0) {
                    res.data.forEach((s) => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = `${s.name} • ${s.nim || s.email}`;
                        select.appendChild(opt);
                    });
                } else {
                    select.innerHTML = '<option value="">Tidak ada mahasiswa ditemukan.</option>';
                }
            } catch (err) {
                console.error(err);
                select.innerHTML = '<option value="">Gagal memuat mahasiswa.</option>';
                alert(err.message || 'Gagal mencari mahasiswa.');
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.classList.add('hidden');
            try {
                const payload = {
                    type: document.getElementById('type').value,
                    counterpart_id: parseInt(document.getElementById('counterpart').value),
                    amount: parseFloat(document.getElementById('amount').value),
                    description: document.getElementById('description').value.trim(),
                    transaction_date: document.getElementById('transaction_date').value,
                    due_date: document.getElementById('due_date').value || null,
                };

                await window.SIMPATI.request('/debts', { method: 'POST', body: payload });
                window.location.href = '/debts';
            } catch (err) {
                alertBox.textContent = err.message || 'Gagal membuat transaksi.';
                alertBox.classList.remove('hidden');
            }
        });
    });
</script>
@endpush
