@extends('layouts.app')

@section('content')
    <div class="space-y-6" data-auth-required>
        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Profil & Pengaturan</h1>
                    <p class="text-sm text-slate-500">Ubah password akun dan lihat informasi profil.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
                <h2 class="text-lg font-semibold text-slate-900">Informasi Akun</h2>
                <div class="mt-6 space-y-4 text-sm text-slate-700">
                    <div>
                        <p class="font-medium text-slate-900">Nama</p>
                        <p id="profileName">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900">Email</p>
                        <p id="profileEmail">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900">NIM</p>
                        <p id="profileNim">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900">Program Studi</p>
                        <p id="profileProgram">-</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
                <h2 class="text-lg font-semibold text-slate-900">Ubah Password</h2>
                <form id="changePasswordForm" class="mt-6 space-y-4">
                    <div>
                        <label for="oldPassword" class="block text-sm font-medium text-slate-700">Password Lama</label>
                        <input id="oldPassword" type="password" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                    </div>
                    <div>
                        <label for="newPassword" class="block text-sm font-medium text-slate-700">Password Baru</label>
                        <input id="newPassword" type="password" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                    </div>
                    <div>
                        <label for="newPasswordConfirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password Baru</label>
                        <input id="newPasswordConfirmation" type="password" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                    </div>
                    <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Simpan Password</button>
                    <p id="passwordAlert" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></p>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const profileName = document.getElementById('profileName');
        const profileEmail = document.getElementById('profileEmail');
        const profileNim = document.getElementById('profileNim');
        const profileProgram = document.getElementById('profileProgram');
        const passwordAlert = document.getElementById('passwordAlert');
        const form = document.getElementById('changePasswordForm');

        try {
            const user = await window.SIMPATI.fetchProfile();
            profileName.textContent = user.name || '-';
            profileEmail.textContent = user.email || '-';
            profileNim.textContent = user.nim || '-';
            profileProgram.textContent = user.study_program?.name || '-';
        } catch (error) {
            await window.SIMPATI.handleUnauthorized(error);
            profileName.textContent = 'Gagal memuat profil.';
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            passwordAlert.classList.add('hidden');

            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const newPasswordConfirmation = document.getElementById('newPasswordConfirmation').value;

            try {
                await window.SIMPATI.changePassword(oldPassword, newPassword, newPasswordConfirmation);
                passwordAlert.textContent = 'Password berhasil diperbarui.';
                passwordAlert.classList.remove('hidden');
                passwordAlert.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-700');
                passwordAlert.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
            } catch (error) {
                await window.SIMPATI.handleUnauthorized(error);
                passwordAlert.textContent = error.message || 'Gagal memperbarui password.';
                passwordAlert.classList.remove('hidden');
                passwordAlert.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                passwordAlert.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
            }
        });
    });
</script>
@endpush
