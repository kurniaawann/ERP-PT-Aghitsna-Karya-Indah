<?php

namespace Tests\Feature;

use App\Models\Finance\PaymentProof;
use App\Models\Finance\ProjectRecap;
use App\Models\Report\ProjectFinancialReport;
use App\Models\Report\ProjectFinancialReportItem;
use App\Models\Report\TransactionCategory;
use App\Models\User;
use App\Services\Finance\PaymentProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validasi batas "uang masuk" (kategori INCOME) agar tidak melebihi sisa
 * pembayaran rekap proyek — baik dari sisi Bon Laporan Keuangan (store/update)
 * maupun dari sisi Bukti Pembayaran.
 */
class RecapProyekIncomeCapTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TransactionCategory $incomeCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->incomeCategory = TransactionCategory::create([
            'name' => 'Uang Masuk Test',
            'code' => 'UANG_MASUK_CAP_'.fake()->unique()->numberBetween(1000, 9999),
            'type' => TransactionCategory::TYPE_INCOME,
            'module' => TransactionCategory::MODULE_PROJECT_FINANCE,
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeRecap(int $totalRab): ProjectRecap
    {
        return ProjectRecap::create([
            'id' => 'RP-CAP-'.fake()->unique()->numberBetween(1000, 9999),
            'project_name' => 'Proyek Cap Test',
            'location' => null,
            'total_rab' => $totalRab,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeReport(ProjectRecap $recap): ProjectFinancialReport
    {
        return ProjectFinancialReport::create([
            'id' => 'LFP-CAP-'.fake()->unique()->numberBetween(1000, 9999),
            'project_recap_id' => $recap->id,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeIncomeItem(ProjectFinancialReport $report, int $amount, ?string $paymentProofId = null): ProjectFinancialReportItem
    {
        return ProjectFinancialReportItem::create([
            'project_financial_report_id' => $report->id,
            'payment_proof_id' => $paymentProofId,
            'transaction_category_id' => $this->incomeCategory->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Uang masuk test',
            'income_amount' => $amount,
            'expense_amount' => null,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeProof(ProjectRecap $recap, int $amount): PaymentProof
    {
        return PaymentProof::create([
            'module_type' => 'finance',
            'invoice_type' => 'recap',
            'invoice_number' => $recap->id,
            'payment_stage' => 1,
            'amount' => $amount,
            'file_name' => fake()->uuid().'.jpg',
            'file_path' => 'proofs/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 100000,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_income_exceeding_remaining(): void
    {
        $recap = $this->makeRecap(100_000_000);

        $response = $this->from(route('project-financial-report.index'))
            ->post(route('project-financial-report.store'), [
                'project_recap_id' => $recap->id,
                'items' => [
                    [
                        'transaction_category_id' => $this->incomeCategory->id,
                        'transaction_date' => '2026-08-10',
                        'description' => 'Pemasukan terlalu besar',
                        'expense_amount' => '150.000.000',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertStringContainsString('tidak boleh melebihi sisa pembayaran', session('errors')->first('items'));
        $this->assertDatabaseCount('project_financial_report_items', 0);
    }

    public function test_store_accepts_income_within_remaining(): void
    {
        $recap = $this->makeRecap(100_000_000);

        $response = $this->post(route('project-financial-report.store'), [
            'project_recap_id' => $recap->id,
            'items' => [
                [
                    'transaction_category_id' => $this->incomeCategory->id,
                    'transaction_date' => '2026-08-10',
                    'description' => 'Pemasukan DP',
                    'expense_amount' => '50.000.000',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('project-financial-report.index'));

        $this->assertDatabaseHas('project_financial_report_items', [
            'income_amount' => 50_000_000,
            'description' => 'Pemasukan DP',
        ]);
    }

    public function test_store_rejects_when_existing_income_plus_new_exceeds_remaining(): void
    {
        $recap = $this->makeRecap(100_000_000);
        $report = $this->makeReport($recap);
        $this->makeIncomeItem($report, 80_000_000);

        $response = $this->from(route('project-financial-report.index'))
            ->post(route('project-financial-report.store'), [
                'project_recap_id' => $recap->id,
                'items' => [
                    [
                        'transaction_category_id' => $this->incomeCategory->id,
                        'transaction_date' => '2026-08-10',
                        'description' => 'Pemasukan tambahan',
                        'expense_amount' => '30.000.000',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_proof_rejected_when_income_consumes_remaining(): void
    {
        $recap = $this->makeRecap(100_000_000);
        $report = $this->makeReport($recap);
        $this->makeIncomeItem($report, 80_000_000);

        $service = app(PaymentProofService::class);

        $this->assertNotNull($service->validatePaymentAmount($recap, 30_000_000));
        $this->assertNull($service->validatePaymentAmount($recap, 20_000_000));
    }

    public function test_proof_update_excludes_current_proof_from_remaining(): void
    {
        $recap = $this->makeRecap(100_000_000);

        $proof = $this->makeProof($recap, 10_000_000);

        $service = app(PaymentProofService::class);

        // Update bukti ke 95jt: tanpa mengecualikan bukti itu sendiri,
        // sisa terhitung 90jt (karena bukti 10jt ikut dihitung).
        $this->assertNotNull($service->validatePaymentAmount($recap, 95_000_000));

        // Dengan mengecualikan bukti yang sedang diedit, sisa = 100jt → valid.
        $this->assertNull($service->validatePaymentAmount($recap, 95_000_000, (int) $proof->id));
    }

    public function test_update_rejects_projected_income_exceeding_allowed(): void
    {
        $recap = $this->makeRecap(100_000_000);

        $report = $this->makeReport($recap);
        $existingIncome = $this->makeIncomeItem($report, 10_000_000);

        $this->makeProof($recap, 40_000_000);

        // Sisa untuk income manual = 100jt - 40jt (bukti) = 60jt.
        // Update item income ke 70jt → melebihi sisa.
        $response = $this->from(route('project-financial-report.index'))
            ->put(route('project-financial-report.update', $recap), [
                'project_name' => $recap->project_name,
                'location' => $recap->location,
                'total_rab' => '100.000.000',
                'items' => [
                    [
                        'id' => (string) $existingIncome->id,
                        'transaction_category_id' => $this->incomeCategory->id,
                        'transaction_date' => '2026-08-10',
                        'description' => 'Uang masuk naik',
                        'expense_amount' => '70.000.000',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_update_accepts_projected_income_within_allowed(): void
    {
        $recap = $this->makeRecap(100_000_000);

        $report = $this->makeReport($recap);
        $existingIncome = $this->makeIncomeItem($report, 10_000_000);

        $this->makeProof($recap, 40_000_000);

        $response = $this->put(route('project-financial-report.update', $recap), [
            'project_name' => $recap->project_name,
            'location' => $recap->location,
            'total_rab' => '100.000.000',
            'items' => [
                [
                    'id' => (string) $existingIncome->id,
                    'transaction_category_id' => $this->incomeCategory->id,
                    'transaction_date' => '2026-08-10',
                    'description' => 'Uang masuk naik',
                    'expense_amount' => '60.000.000',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('project-financial-report.index'));

        $this->assertDatabaseHas('project_financial_report_items', [
            'id' => $existingIncome->id,
            'income_amount' => 60_000_000,
        ]);
    }
}
