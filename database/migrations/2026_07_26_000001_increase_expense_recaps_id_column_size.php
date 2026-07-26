<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE expense_recaps MODIFY COLUMN id VARCHAR(30) NOT NULL");
        } elseif ($driver === 'sqlite') {
            Schema::table('expense_recaps', function ($table) {
                $table->string('id', 30)->primary()->change();
            });
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE expense_recaps ALTER COLUMN id TYPE VARCHAR(30)");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE expense_recaps MODIFY COLUMN id VARCHAR(20) NOT NULL");
        } elseif ($driver === 'sqlite') {
            Schema::table('expense_recaps', function ($table) {
                $table->string('id', 20)->primary()->change();
            });
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE expense_recaps ALTER COLUMN id TYPE VARCHAR(20)");
        }
    }
};
