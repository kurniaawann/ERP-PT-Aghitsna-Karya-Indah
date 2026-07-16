<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position', 100)->nullable()->change();
            $table->string('email', 100)->nullable()->change();
            $table->integer('base_salary')->nullable()->change();
            $table->date('join_date')->nullable()->change();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        // Tidak dapat membalikkan migrasi ini dengan aman tanpa kehilangan data
        // jika terdapat nilai NULL, sehingga migrasi down dilewati
    }
};
