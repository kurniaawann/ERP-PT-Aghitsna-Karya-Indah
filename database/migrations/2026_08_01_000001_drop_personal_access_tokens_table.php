<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel personal_access_tokens (Sanctum) karena tidak digunakan.
     *
     * Logika: tabel token API ini dibuat oleh migration vendor Laravel Sanctum
     * dan tidak dipakai fitur apa pun di aplikasi (middleware Sanctum dinonaktifkan,
     * tidak ada login API). Dihapus lewat migration agar di lingkungan production
     * perubahan ini tercatat dan bisa di-rollback.
     */
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    /**
     * Rollback: buat ulang tabel sesuai skema asli Sanctum.
     */
    public function down(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
