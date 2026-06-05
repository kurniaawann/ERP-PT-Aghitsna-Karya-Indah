<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ItemSeeder::class,
            AlumuniumInvoiceSeeder::class,
            TransactionCategorySeeder::class,
            SalesRecapSeeder::class,
            ExpenseRecapSeeder::class,
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            OvertimeSeeder::class,
            DivisionSeeder::class,
            PaymentAccountSeeder::class,
            ProjectQuotationSeeder::class,
                // Seeders dengan data inventory & keuangan:
            ItemStockInSeeder::class,
            ItemStockOutSeeder::class,
            ItemReturnSeeder::class,
            ReimburseSeeder::class,
            ReimburseDataSeeder::class,
            PayrollSeeder::class,
            KasbonSeeder::class,
            PurchaseInvoiceSeeder::class,
            CashOutProofSeeder::class,
            KwintansiSeeder::class,
            DocumentReceiptSeeder::class,
            RabMiscellaneousCostSeeder::class,
                // Seeders untuk proyek & reminders:
            ProyekInvoiceSeeder::class,
            DeliveryNoteSeeder::class,
            InvoiceProyekReminderSeeder::class,
            SalaryReminderSeeder::class,
            SdmSeeder::class,
        ]);
    }
}
