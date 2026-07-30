<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            if (!Schema::hasColumn('kasbons', 'paid_amount')) {
                $table->integer('paid_amount')->default(0)->after('amount');
            }
            if (!Schema::hasColumn('kasbons', 'remaining_amount')) {
                $table->integer('remaining_amount')->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('kasbons', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('status');
            }
        });

        // Set remaining_amount = amount untuk data yang sudah ada
        DB::table('kasbons')
            ->where('remaining_amount', 0)
            ->update(['remaining_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'remaining_amount', 'payment_status']);
        });
    }
};
