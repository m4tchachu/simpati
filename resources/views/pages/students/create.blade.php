@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-xl rounded-3xl bg-white p-8 shadow-lg shadow-slate-200/80 md:p-12" data-auth-required data-requires-role="admin">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold text-slate-900">Tambah Mahasiswa</h1>
            <p class="mt-2 text-sm text-slate-500">Isi data mahasiswa baru.</p>
        </div>

        <div id="alert" class="hidden rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"></div>

        <form id="studentCreateForm" class="space-y-6">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                <input id="name" name="name" type="text" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">NIM</label>
                <input id="nim" name="nim" type="text" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Program Studi</label>
                <select id="study_program_id" name="study_program_id" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none">
                    <option value="">Pilih Program Studi</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none" />
            </div>

            <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Buat Mahasiswa</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const form = document.getElementById('studentCreateForm');
        const alertBox = document.getElementById('alert');
        const studyProgramSelect = document.getElementById('study_program_id');

        // Load study programs
        try {
            const programs = await window.SIMPATI.fetchStudyPrograms();
            studyProgramSelect.innerHTML = '<option value="">Pilih Program Studi</option>';
            programs.forEach((prog) => {
                const opt = document.createElement('option');
                opt.value = prog.id;
                opt.textContent = `${prog.name} (${prog.code})`;
                studyProgramSelect.appendChild(opt);
            });
        } catch (err) {
            console.error('Gagal memuat program studi', err);
            studyProgramSelect.innerHTML = '<option value="">Gagal memuat program studi</option>';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertBox.classList.add('hidden');

            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                alertBox.textContent = 'Konfirmasi password tidak cocok.';
                alertBox.classList.remove('hidden');
                return;
            }

            try {
                const payload = {
                    name: document.getElementById('name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    nim: document.getElementById('nim').value.trim(),
                    study_program_id: parseInt(studyProgramSelect.value),
                    password: password,
                    password_confirmation: passwordConfirmation,
                };

                await window.SIMPATI.request('/students', { method: 'POST', body: payload });
                window.location.href = '/students';
            } catch (err) {
                alertBox.textContent = err.message || 'Gagal membuat mahasiswa.';
                alertBox.classList.remove('hidden');
            }
        });
    });
</script>
@endpush
