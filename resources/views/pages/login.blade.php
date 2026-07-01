@extends('layouts.app')

@section('content')
    <div class="flex min-h-[calc(100vh-180px)] flex-col items-center justify-center">
        <div class="w-full max-w-xl rounded-3xl bg-white p-8 shadow-lg shadow-slate-200/80 md:p-12">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-semibold text-slate-900">Masuk ke SIMPATI</h1>
                <p class="mt-2 text-sm text-slate-500">Gunakan akun email Anda untuk mengakses sistem.</p>
            </div>

            <div id="alert" class="hidden mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 animate-shake"></div>

            <form id="loginForm" class="space-y-6">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:bg-white" />
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-slate-500 focus:bg-white" />
                </div>

                <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Masuk</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('loginForm');
            const alertBox = document.getElementById('alert');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                alertBox.classList.add('hidden');
                alertBox.textContent = '';

                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();

                try {
                    await window.SIMPATI.login(email, password);
                    window.location.href = '/dashboard';
                } catch (error) {
                    alertBox.textContent = error.message ?? 'Login gagal. Periksa kembali kredensial Anda.';
                    alertBox.classList.remove('hidden');
                }
            });
        });
    </script>
@endpush
