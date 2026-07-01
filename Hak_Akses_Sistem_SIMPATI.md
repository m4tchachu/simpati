# Hak Akses Sistem SIMPATI

## Hak Akses Admin

Admin bertanggung jawab mengelola data mahasiswa sebagai referensi utama
dalam sistem. Admin tidak terlibat langsung dalam transaksi
hutang-piutang antar mahasiswa.

### Fitur Admin

1.  **Login ke Sistem**
    -   Login menggunakan akun admin.
2.  **Mengelola Data Mahasiswa (CRUD)**
    -   Menambah data mahasiswa.
    -   Mengubah data mahasiswa.
    -   Menghapus data mahasiswa (Soft Delete).
    -   Melihat detail mahasiswa.

    Data yang dikelola:
    -   Nama Lengkap
    -   NIM
    -   Program Studi
    -   Status Mahasiswa
3.  **Mencari Data Mahasiswa**
    -   Berdasarkan Nama.
    -   Berdasarkan NIM.
4.  **Melihat Statistik Mahasiswa**
    -   Jumlah mahasiswa.
    -   Mahasiswa aktif.
    -   Statistik penggunaan sistem.
5.  **Mengekspor Data Mahasiswa**
    -   Export ke format laporan (Excel/CSV).
6.  **Mengelola Profil**
    -   Melihat profil.
    -   Mengubah password.
    -   Logout.

------------------------------------------------------------------------

# Hak Akses Mahasiswa

Mahasiswa merupakan pengguna utama sistem yang melakukan pencatatan
hutang-piutang dengan mahasiswa lain.

## Fitur Mahasiswa

### 1. Login

-   Login menggunakan akun yang telah terdaftar.

### 2. Dashboard

Menampilkan: - Total Hutang - Total Piutang - Total Transaksi Aktif -
Total Transaksi Pending - Ringkasan Statistik - Grafik Transaksi -
Hutang Mendekati Jatuh Tempo - Hutang Terlambat - Transaksi Terbaru

### 3. Mencari Mahasiswa

-   Berdasarkan Nama.
-   Berdasarkan NIM.

### 4. Membuat Catatan Hutang atau Piutang

Mahasiswa dapat membuat transaksi baru dengan: - Jenis Transaksi
(Hutang/Piutang) - Mahasiswa Tujuan - Nominal - Keterangan - Tanggal
Transaksi - Tanggal Jatuh Tempo

Status awal transaksi adalah **Pending (Menunggu Konfirmasi)**.

### 5. Melihat Daftar Transaksi

-   Melihat seluruh transaksi.
-   Filter transaksi.
-   Search transaksi.
-   Melihat detail transaksi.

### 6. Mengubah Catatan

-   Mengubah data transaksi yang telah dibuat.

### 7. Menghapus Catatan

-   Menghapus transaksi yang tidak diperlukan.

### 8. Konfirmasi Transaksi

-   Konfirmasi transaksi.
-   Menolak transaksi.

Status menjadi: - Active - Rejected

### 9. Menandai Transaksi Lunas

-   Mengubah status menjadi **Settled (Lunas)**.

### 10. Melihat Riwayat

-   Melihat seluruh transaksi yang telah selesai.

### 11. Statistik Hutang-Piutang

-   Total Hutang
-   Total Piutang
-   Total Pending
-   Total Lunas
-   Grafik Transaksi

### 12. Mengelola Notifikasi

-   Melihat notifikasi.
-   Melihat notifikasi belum dibaca.
-   Menandai dibaca.
-   Menandai belum dibaca.
-   Tandai semua dibaca.
-   Menghapus notifikasi.

### 13. Reminder Jatuh Tempo

Sistem mengirim notifikasi otomatis: - H-3 sebelum jatuh tempo. - H-1
sebelum jatuh tempo.

Notifikasi juga dikirim ketika: - Transaksi baru dibuat. - Transaksi
dikonfirmasi. - Transaksi ditolak. - Transaksi dilunasi. - Data
transaksi diperbarui.

### 14. Mengelola Profil

-   Melihat profil.
-   Mengubah password.
-   Logout.

------------------------------------------------------------------------

# Ringkasan Hak Akses

  Fitur                               Admin             Mahasiswa
  ----------------------------- ------------------ -------------------
  Login                                 ✅                 ✅
  Logout                                ✅                 ✅
  Lihat Profil                          ✅                 ✅
  Ganti Password                        ✅                 ✅
  Dashboard                      Ringkasan Sistem   Ringkasan Pribadi
  Kelola Mahasiswa (CRUD)               ✅                 ❌
  Cari Mahasiswa                        ✅                 ✅
  Export Data Mahasiswa                 ✅                 ❌
  Buat Catatan Hutang/Piutang           ❌                 ✅
  Edit Catatan                          ❌                 ✅
  Hapus Catatan                         ❌                 ✅
  Konfirmasi Transaksi                  ❌                 ✅
  Tolak Transaksi                       ❌                 ✅
  Tandai Lunas                          ❌                 ✅
  Riwayat Transaksi                     ❌                 ✅
  Statistik Hutang/Piutang              ❌                 ✅
  Kelola Notifikasi                     ❌                 ✅
  Reminder Jatuh Tempo                  ❌                 ✅
