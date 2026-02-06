<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position', 100)->nullable(false)->change();
            $table->string('email', 100)->nullable(false)->change();
            $table->integer('base_salary')->nullable(false)->change();
            $table->date('join_date')->nullable(false)->change();
        });
    }
};
