@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70 md:flex-row md:items-center md:justify-between" data-auth-required data-requires-role="admin">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Manajemen Mahasiswa</h1>
                <p class="mt-2 text-sm text-slate-500">Lihat, cari, dan kelola data mahasiswa.</p>
            </div>
            <a href="/students/new" data-auth-only data-role="admin" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Tambah Mahasiswa</a>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200/70">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Cari mahasiswa</label>
                    <input id="studentSearch" type="search" placeholder="Masukkan nama, nim, atau email" class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Program Studi</label>
                    <select id="studyProgramFilter" class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white">
                        <option value="">Semua Program Studi</option>
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <button id="studentSearchButton" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Cari</button>
                </div>
            </div>
        </div>

        <div id="studentList" class="space-y-4"></div>
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

    async function showStudentDetails(id) {
        try {
            const studentRes = await window.SIMPATI.request(`/students/${id}`);
            const statsRes = await window.SIMPATI.request(`/students/${id}/stats`);
            const programsRes = await window.SIMPATI.fetchStudyPrograms();
            
            const student = studentRes.data;
            const stats = statsRes.data;
            const programs = programsRes || [];

            // Render detail modal
            let modal = document.getElementById('studentDetailModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'studentDetailModal';
                modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm';
                document.body.appendChild(modal);
            }

            const programOptions = programs.map(p => 
                `<option value="${p.id}" ${student.study_program?.id === p.id ? 'selected' : ''}>${p.name}</option>`
            ).join('');

            modal.innerHTML = `
                <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h3 class="text-lg font-bold text-slate-900">Detail Mahasiswa</h3>
                        <button onclick="document.getElementById('studentDetailModal').remove()" class="rounded-xl p-1 text-slate-400 hover:bg-slate-50 hover:text-slate-700">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <!-- View Mode -->
                    <div id="detailViewMode" class="mt-4 space-y-4 text-sm text-slate-700">
                        <div class="grid grid-cols-3 gap-2">
                            <span class="font-medium text-slate-500 font-semibold">Nama:</span>
                            <span class="col-span-2 text-slate-900 font-semibold">${student.name}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="font-medium text-slate-500 font-semibold">NIM:</span>
                            <span class="col-span-2 text-slate-900">${student.nim || '-'}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="font-medium text-slate-500 font-semibold">Email:</span>
                            <span class="col-span-2 text-slate-900">${student.email}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="font-medium text-slate-500 font-semibold">Program Studi:</span>
                            <span class="col-span-2 text-slate-900">${student.study_program?.name || '-'}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="font-medium text-slate-500 font-semibold">Status Akun:</span>
                            <span class="col-span-2">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold border uppercase ${student.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'}">
                                    ${student.is_active ? 'Aktif' : 'Non-aktif'}
                                </span>
                            </span>
                        </div>
                        
                        <div class="border-t border-slate-100 pt-4">
                            <h4 class="font-bold text-slate-900 mb-2">Ringkasan Keuangan</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-2xl bg-rose-50 p-3 border border-rose-100 text-center">
                                    <p class="text-xs text-rose-600 font-semibold">Total Hutang</p>
                                    <p class="text-base font-bold text-rose-700 mt-1">Rp ${stats.total_debt?.toLocaleString('id-ID') ?? 0}</p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-3 border border-emerald-100 text-center">
                                    <p class="text-xs text-emerald-600 font-semibold">Total Piutang</p>
                                    <p class="text-base font-bold text-emerald-700 mt-1">Rp ${stats.total_receivable?.toLocaleString('id-ID') ?? 0}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 mt-6">
                            <button onclick="document.getElementById('detailViewMode').classList.add('hidden'); document.getElementById('detailEditMode').classList.remove('hidden');" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Edit</button>
                            <button onclick="document.getElementById('studentDetailModal').remove()" class="rounded-2xl bg-slate-100 border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Tutup</button>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <div id="detailEditMode" class="hidden mt-4 space-y-4 text-sm text-slate-700">
                        <form onsubmit="event.preventDefault(); window.handleUpdateStudent(${student.id})">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">Nama</label>
                                    <input type="text" id="editStudentName" value="${student.name}" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">NIM</label>
                                    <input type="text" id="editStudentNim" value="${student.nim || ''}" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">Email</label>
                                    <input type="email" id="editStudentEmail" value="${student.email}" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">Program Studi</label>
                                    <select id="editStudentProgram" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white">
                                        ${programOptions}
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">Kata Sandi Baru (Opsional)</label>
                                    <input type="password" id="editStudentPassword" placeholder="Biarkan kosong jika tidak diubah" class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-slate-500 focus:bg-white" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 mt-6">
                                <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Simpan</button>
                                <button type="button" onclick="document.getElementById('detailEditMode').classList.add('hidden'); document.getElementById('detailViewMode').classList.remove('hidden');" class="rounded-2xl bg-slate-100 border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        } catch (err) {
            alert('Gagal mengambil detail mahasiswa: ' + err.message);
        }
    }

    window.handleUpdateStudent = async function(id) {
        const name = document.getElementById('editStudentName').value.trim();
        const nim = document.getElementById('editStudentNim').value.trim();
        const email = document.getElementById('editStudentEmail').value.trim();
        const study_program_id = document.getElementById('editStudentProgram').value;
        const password = document.getElementById('editStudentPassword').value;

        const body = {
            name,
            nim,
            email,
            study_program_id: parseInt(study_program_id)
        };

        if (password) {
            body.password = password;
            body.password_confirmation = password;
        }

        try {
            await window.SIMPATI.request(`/students/${id}`, {
                method: 'PUT',
                body: body
            });
            alert('Data mahasiswa berhasil diperbarui!');
            document.getElementById('studentDetailModal').remove();
            
            // Trigger refresh
            const searchInput = document.getElementById('studentSearch');
            const filterSelect = document.getElementById('studyProgramFilter');
            await loadStudents(searchInput.value.trim(), filterSelect.value);
        } catch (err) {
            alert('Gagal memperbarui data mahasiswa: ' + err.message);
        }
    }

    async function renderStudents(items = []) {
        const list = document.getElementById('studentList');
        list.innerHTML = '';

        if (!items.length) {
            list.innerHTML = '<div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Tidak ada mahasiswa ditemukan.</div>';
            return;
        }

        items.forEach((student) => {
            const card = document.createElement('article');
            card.className = 'rounded-3xl border border-slate-200 bg-white p-6 shadow-sm';
            card.innerHTML = `
                <div class="grid gap-4 lg:grid-cols-3 lg:items-center">
                    <div>
                        <p class="text-base font-semibold text-slate-900">${student.name}</p>
                        <p class="mt-1 text-sm text-slate-500">${student.nim} • ${student.email}</p>
                        <p class="mt-1 text-sm text-slate-500">${student.study_program?.name ?? '-'}</p>
                    </div>
                    <div class="text-sm text-slate-600">
                        <p class="font-medium text-slate-400 text-xs uppercase">Status</p>
                        <span class="inline-block mt-1 rounded-full px-2.5 py-0.5 text-xs font-semibold border uppercase ${student.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'}">
                            ${student.is_active ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 justify-end">
                        <button data-id="${student.id}" class="showDetail rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Detail</button>
                        <button data-id="${student.id}" class="toggleStudentStatus rounded-2xl ${student.is_active ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100'} px-4 py-2 text-sm font-semibold transition">
                            ${student.is_active ? 'Stop Akun' : 'Aktifkan'}
                        </button>
                        <button data-id="${student.id}" class="deleteStudent rounded-2xl bg-rose-50 border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 transition">Hapus</button>
                    </div>
                </div>
            `;
            list.appendChild(card);
        });
    }

    async function loadStudents(q = '', programId = '') {
        try {
            let url = '/students';
            const params = [];
            if (q) params.push(`search=${encodeURIComponent(q)}`);
            if (programId) params.push(`study_program_id=${programId}`);
            if (params.length) {
                url += `?${params.join('&')}`;
            }

            const response = await window.SIMPATI.request(url);
            renderStudents(response.data || []);
        } catch (error) {
            await window.SIMPATI.handleUnauthorized(error);
            document.getElementById('studentList').innerHTML = '<div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-rose-800">Gagal memuat mahasiswa.</div>';
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const searchInput = document.getElementById('studentSearch');
        const filterSelect = document.getElementById('studyProgramFilter');
        const searchButton = document.getElementById('studentSearchButton');

        // Populate study programs filter list
        try {
            const programs = await window.SIMPATI.fetchStudyPrograms();
            programs.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                filterSelect.appendChild(opt);
            });
        } catch (err) {
            console.error('Gagal memuat program studi', err);
        }

        async function triggerSearch() {
            await loadStudents(searchInput.value.trim(), filterSelect.value);
        }

        searchButton.addEventListener('click', triggerSearch);
        filterSelect.addEventListener('change', triggerSearch);

        document.getElementById('studentList').addEventListener('click', async (event) => {
            const target = event.target;
            
            // Handle show detail
            const detailBtn = target.closest('.showDetail');
            if (detailBtn) {
                showStudentDetails(detailBtn.dataset.id);
                return;
            }

            // Handle toggle status
            const toggleBtn = target.closest('.toggleStudentStatus');
            if (toggleBtn) {
                const id = toggleBtn.dataset.id;
                try {
                    await window.SIMPATI.request(`/students/${id}/toggle-status`, { method: 'POST' });
                    await triggerSearch();
                } catch (err) {
                    await window.SIMPATI.handleUnauthorized(err);
                    alert('Gagal mengubah status akun: ' + err.message);
                }
                return;
            }

            // Handle delete
            const deleteBtn = target.closest('.deleteStudent');
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                const confirmed = confirm('Yakin ingin menghapus mahasiswa ini?');
                if (!confirmed) return;

                try {
                    await window.SIMPATI.request(`/students/${id}`, { method: 'DELETE' });
                    await triggerSearch();
                } catch (error) {
                    await window.SIMPATI.handleUnauthorized(error);
                    alert('Gagal menghapus mahasiswa.');
                }
            }
        });

        loadStudents();
    });
</script>
@endpush
