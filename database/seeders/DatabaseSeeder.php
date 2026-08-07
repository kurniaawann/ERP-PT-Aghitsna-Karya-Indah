<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Foundation
        $this->call(UserSeeder::class);
        $this->call(DivisionSeeder::class);
        $this->call(PaymentAccountSeeder::class);
        $this->call(TransactionCategorySeeder::class);

        // 2. Core entities
        $this->call(EmployeeSeeder::class);
        $this->call(ItemSeeder::class);
        $this->call(SalesRecapSeeder::class);

        // 3. SDM
        $this->call(AttendanceSeeder::class);
        $this->call(PayrollSeeder::class);
        $this->call(KasbonSeeder::class);
        $this->call(KasbonPaymentSeeder::class);

        // 4. Inventory
        $this->call(ItemStockInSeeder::class);
        $this->call(ItemStockOutSeeder::class);
        $this->call(ItemReturnSeeder::class);

        // 5. Finance
        $this->call(AlumuniumInvoiceSeeder::class);
        $this->call(ProyekInvoiceSeeder::class);
        $this->call(BarangInvoiceSeeder::class);
        $this->call(PurchaseInvoiceSeeder::class);
        $this->call(ReimburseSeeder::class);
        $this->call(CashOutProofSeeder::class);
        $this->call(PaymentProofSeeder::class);

        // 6. Report
        $this->call(ExpenseRecapSeeder::class);

        // 7. Administrasi
        $this->call(DeliveryNoteSeeder::class);
        $this->call(KwintansiSeeder::class);
        $this->call(DocumentReceiptSeeder::class);
        $this->call(NotaSeeder::class);
        $this->call(RABSeeder::class);
        $this->call(ProjectQuotationSeeder::class);
        $this->call(AluminiumQuotationSeeder::class);
    }
}
