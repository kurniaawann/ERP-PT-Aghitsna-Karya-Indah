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
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            // Discount fields
            $table->string('discount_type')->nullable()->after('total_amount'); // 'percentage' or 'amount'
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type'); // nilai discount
            $table->integer('total_after_discount')->nullable()->after('discount_value'); // total setelah discount

            // DP fields
            $table->string('dp_type')->nullable()->after('total_after_discount'); // 'percentage' or 'amount'
            $table->decimal('dp_value', 15, 2)->nullable()->after('dp_type'); // nilai DP
            $table->integer('dp_amount')->nullable()->after('dp_value'); // jumlah DP dalam rupiah
        });

        // Create payment_accounts table
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name'); // Nama Bank (Mandiri, BCA, dll)
            $table->string('account_number'); // Nomor Rekening
            $table->string('account_holder'); // Nama Pemilik Rekening
            $table->boolean('is_active')->default(true); // Status aktif/tidak
            $table->integer('order')->default(0); // Urutan tampilan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'total_after_discount',
                'dp_type',
                'dp_value',
                'dp_amount'
            ]);
        });

        Schema::dropIfExists('payment_accounts');
    }
};
