# Membuat Model, Migration, Factory, Seeder, dan Controller sekaligus.
# Cocok digunakan saat memulai membuat fitur baru.
php artisan make:model NamaModel -mfsc

# Membuat Form Request untuk validasi input.
# Contoh: validasi nama, email, harga, stok, dll.
php artisan make:request StoreDataRequest

# Menjalankan semua migration yang belum pernah dijalankan.
# Digunakan untuk membuat atau mengubah struktur database.
php artisan migrate

# Menghapus seluruh tabel di database, kemudian membuatnya kembali
# dan menjalankan semua seeder.
# Sangat berguna saat development.
php artisan migrate:fresh --seed

# Menjalankan seluruh file seeder untuk mengisi data awal
# atau data dummy ke database.
php artisan db:seed

# Menampilkan seluruh daftar route beserta method, URI,
# nama route, dan controller yang digunakan.
php artisan route:list

# Menghapus seluruh cache Laravel (config, route, view, cache, dll.).
# Biasanya dijalankan setelah mengubah konfigurasi atau deployment.
php artisan optimize:clear

# Membuat symbolic link dari storage/app/public ke public/storage
# agar file upload dapat diakses melalui browser.
php artisan storage:link

# Menjalankan Queue Worker untuk memproses job di background.
# Contohnya: kirim email, generate PDF, import data, dll.
php artisan queue:work

# Menjalankan Laravel Scheduler secara terus-menerus.
# Digunakan untuk mengeksekusi task yang dijadwalkan di app/Console/Kernel.php.
php artisan schedule:work

# Menjalankan seluruh Unit Test dan Feature Test Laravel
# untuk memastikan aplikasi berjalan dengan benar.
php artisan test

# Kalau hanya ingin membuat migration saja,
php artisan make:migration nama_migration

php artisan 