<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE rab_subcategories MODIFY volume BIGINT NULL');
        DB::statement('ALTER TABLE rab_subcategories MODIFY unit VARCHAR(255) NULL');
        DB::statement('ALTER TABLE rab_subcategories MODIFY unit_price BIGINT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE rab_subcategories MODIFY volume BIGINT NOT NULL');
        DB::statement('ALTER TABLE rab_subcategories MODIFY unit VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE rab_subcategories MODIFY unit_price BIGINT NOT NULL');
    }
};