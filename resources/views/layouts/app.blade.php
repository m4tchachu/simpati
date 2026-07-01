<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#1f2937" />
        <title>{{ $title ?? 'SIMPATI' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900">
        <div class="min-h-screen flex flex-col">
            @unless(request()->routeIs('login'))
                <header class="border-b border-slate-200 bg-white/90 backdrop-blur-sm sticky top-0 z-20">
                    <div class="mx-auto flex w-full max-w-[1200px] items-center justify-between px-4 py-4 md:px-6">
                        <a href="/" class="text-lg font-semibold tracking-tight text-slate-900">SIMPATI</a>
                        <nav id="appNav" class="hidden items-center gap-3 text-sm text-slate-700 md:flex">
                            <a href="/dashboard" data-auth-only class="rounded-md px-3 py-2 hover:bg-slate-100">Dashboard</a>
                            <a href="/debts" data-auth-only data-role="mahasiswa" class="rounded-md px-3 py-2 hover:bg-slate-100">Hutang</a>
                            <a href="/notifications" data-auth-only data-role="mahasiswa" class="rounded-md px-3 py-2 hover:bg-slate-100">Notifikasi</a>
                            <a href="/students" data-auth-only data-role="admin" class="rounded-md px-3 py-2 hover:bg-slate-100">Mahasiswa</a>
                            <a href="/profile" data-auth-only class="rounded-md px-3 py-2 hover:bg-slate-100">Profil</a>
                            <button id="logoutButton" data-auth-only class="rounded-md bg-slate-900 px-3 py-2 text-white hover:bg-slate-800">Keluar</button>
                        </nav>
                        <div class="flex items-center gap-2">
                            <a href="/login" data-guest-only class="hidden rounded-md border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-100">Login</a>
                        </div>
                    </div>
                </header>
            @endunless

            <main class="flex-1 bg-slate-50 py-8">
                <div class="mx-auto w-full max-w-[1200px] px-4 md:px-6">
                    @yield('content')
                </div>
            </main>

            <footer class="border-t border-slate-200 bg-white py-4 text-center text-sm text-slate-500">
                © {{ date('Y') }} SIMPATI. Siti Zahra Azizah - SIKC 4B.
            </footer>
        </div>

        @stack('scripts')

        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                // Find elements that require auth or specific role
                const authRequired = document.querySelector('[data-auth-required]');
                const roleRequiredEl = document.querySelector('[data-requires-role]');

                // Helper: redirect to login
                const toLogin = () => { window.location.href = '/login'; };

                // If no auth required on this page, nothing to enforce
                if (!authRequired && !roleRequiredEl) return;

                try {
                    // Ensure we have user info and role stored
                    await window.SIMPATI.me();
                } catch (err) {
                    // Not authenticated
                    return toLogin();
                }

                const role = localStorage.getItem('simpati_role');

                if (roleRequiredEl) {
                    const required = roleRequiredEl.getAttribute('data-requires-role');
                    if (required && required !== role) {
                        // role mismatch: redirect to dashboard (if logged in) or login
                        if (window.SIMPATI.isLoggedIn()) {
                            window.location.href = '/dashboard';
                        } else {
                            toLogin();
                        }
                    }
                }
            });
        </script>
    </body>
</html>
