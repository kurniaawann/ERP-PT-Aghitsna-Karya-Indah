<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Sdm\Kasbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->integer('remaining_amount')->nullable()->after('amount');
        });

        // Backfill existing records: pending -> full amount, deducted -> 0
        Kasbon::where('status', 'pending')->whereNull('remaining_amount')->update([
            'remaining_amount' => DB::raw('amount')
        ]);
        Kasbon::where('status', 'deducted')->whereNull('remaining_amount')->update([
            'remaining_amount' => 0
        ]);
    }

    public function down(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->dropColumn('remaining_amount');
        });
    }
};
