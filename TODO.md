# TODO - Perubahan Role, Menu, dan Seeder

## Checklist Implementasi

1. [x] Tambah helper role pada `app/Models/User.php`.
2. [x] Terapkan pembatasan backend untuk role terbatas pada `routes/web.php` (selain superadmin):
    - staf_gudang: hanya Inventory
    - staf_sdm: hanya Human Resource
    - general_manager: hanya User Management + Report
    - another: akses penuh
    - superadmin: tetap full backend (tanpa pembatasan backend)
3. [ ] Update sidebar `resources/views/layouts/sidebar.blade.php` sesuai aturan role:
    - superadmin: sembunyikan Inventory & Laporan Penjualan dari sidebar, label UI untuk Invoice/Penawaran, admin menu sesuai.
    - role lain sesuai pembatasan modul.
4. [ ] Update dashboard:
    - `app/Http/Controllers/PageController.php` dan `resources/views/pages/dashboard.blade.php` agar `staf_gudang` hanya melihat widget Reminder Stok Menipis.
5. [ ] Terapkan label UI hanya untuk superadmin:
    - Invoice Proyek -> Invoice
    - Penawaran Proyek -> Penawaran
      pada halaman list/detail/form + breadcrumb/judul/botton yang relevan.
6. [ ] Update seeder role yang relevan (terutama `database/seeders/UserSeeder.php`).
7. [ ] Jalankan `php artisan migrate:fresh --seed` dan validasi:
    - login dengan beberapa role dan coba akses route yang tidak seharusnya.
    - cek tampilan sidebar & dashboard sesuai.
