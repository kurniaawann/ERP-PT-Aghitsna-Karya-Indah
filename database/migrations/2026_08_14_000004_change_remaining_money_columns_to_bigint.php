<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom uang yang tersisa menjadi BIGINT.
 *
 * - employees.daily_wage (upah harian pekerja) terlewat pada migrasi HR.
 * - total_after_discount pada quotation (aluminium & proyek) masih INT.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'daily_wage')) {
                $table->bigInteger('daily_wage')->nullable()->change();
            }
        });

        Schema::table('aluminium_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('aluminium_quotations', 'total_after_discount')) {
                $table->bigInteger('total_after_discount')->nullable()->change();
            }
        });

        Schema::table('project_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('project_quotations', 'total_after_discount')) {
                $table->bigInteger('total_after_discount')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('daily_wage')->nullable()->change();
        });

        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->integer('total_after_discount')->nullable()->change();
        });

        Schema::table('project_quotations', function (Blueprint $table) {
            $table->integer('total_after_discount')->nullable()->change();
        });
    }
};