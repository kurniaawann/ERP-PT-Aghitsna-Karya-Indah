<?php

namespace Tests\Feature;

use App\Models\Finance\ProjectRecap;
use App\Models\Report\ProjectFinancialReport;
use App\Models\Report\ProjectFinancialReportItem;
use App\Models\Report\TransactionCategory;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Payroll;
use App\Models\User;
use Database\Factories\EmployeeFactory;
use Database\Factories\KasbonFactory;
use Database\Factories\PayrollFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard penghapusan Rekap Proyek.
 *
 * Laporan Keuangan Proyek adalah anak (1:1) milik rekap dan ikut terhapus
 * (cascade) sehingga tidak memblokir penghapusan. Yang memblokir hanya
 * referensi eksternal via nama proyek: payroll, kasbon, atau karyawan.
 */
class RecapProyekDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function makeRecap(string $projectName = 'Proyek Hapus Test'): ProjectRecap
    {
        return ProjectRecap::create([
            'id' => 'RP-DEL-'.fake()->unique()->numberBetween(1000, 9999),
            'project_name' => $projectName,
            'location' => null,
            'total_rab' => 100_000_000,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeEmployee(): Employee
    {
        return EmployeeFactory::new()->createdBy($this->user)->create([
            'employee_code' => 'EMP-DEL-'.fake()->unique()->numberBetween(1000, 9999),
        ]);
    }

    private function makePayroll(ProjectRecap $recap, User $user): Payroll
    {
        $employee = EmployeeFactory::new()->createdBy($user)->create([
            'employee_code' => 'EMP-DEL-'.fake()->unique()->numberBetween(1000, 9999),
        ]);

        return PayrollFactory::new()->createdBy($user)->create([
            'employee_id' => $employee->employee_code,
            'project_name' => $recap->project_name,
        ]);
    }

    private function makeReportWithManualItem(ProjectRecap $recap): array
    {
        $category = TransactionCategory::create([
            'name' => 'Bon Test',
            'code' => 'BON_DEL_'.fake()->unique()->numberBetween(1000, 9999),
            'type' => TransactionCategory::TYPE_INCOME,
            'module' => TransactionCategory::MODULE_PROJECT_FINANCE,
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $report = ProjectFinancialReport::create([
            'id' => 'LFP-DEL-'.fake()->unique()->numberBetween(1000, 9999),
            'project_recap_id' => $recap->id,
            'created_by' => $this->user->id,
        ]);

        $item = ProjectFinancialReportItem::create([
            'project_financial_report_id' => $report->id,
            'transaction_category_id' => $category->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Bon manual',
            'income_amount' => 50_000_000,
            'expense_amount' => null,
            'payment_proof_id' => null,
            'created_by' => $this->user->id,
        ]);

        return [$report, $item];
    }

    public function test_recap_with_report_containing_manual_items_can_be_deleted(): void
    {
        $recap = $this->makeRecap();
        [$report, $item] = $this->makeReportWithManualItem($recap);

        $response = $this->delete(route('recap-proyek.destroySelected'), [
            'selected_recaps' => [$recap->id],
        ]);

        $response->assertRedirect(route('recap-proyek.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('project_recaps', ['id' => $recap->id]);
        $this->assertDatabaseMissing('project_financial_reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('project_financial_report_items', ['id' => $item->id]);
    }

    public function test_recap_referenced_by_payroll_is_blocked(): void
    {
        $recap = $this->makeRecap();

        $this->makePayroll($recap, $this->user);

        $response = $this->delete(route('recap-proyek.destroySelected'), [
            'selected_recaps' => [$recap->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'tidak dapat dihapus karena masih digunakan',
            session('error')
        );
        $this->assertDatabaseHas('project_recaps', ['id' => $recap->id]);
    }

    public function test_recap_referenced_by_kasbon_is_blocked(): void
    {
        $recap = $this->makeRecap();

        KasbonFactory::new()->createdBy($this->user)->create([
            'kasbon_code' => 'KAS-DEL-'.fake()->unique()->numberBetween(1000, 9999),
            'project_names' => [$recap->project_name],
        ]);

        $response = $this->delete(route('recap-proyek.destroySelected'), [
            'selected_recaps' => [$recap->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'tidak dapat dihapus karena masih digunakan',
            session('error')
        );
        $this->assertDatabaseHas('project_recaps', ['id' => $recap->id]);
    }

    public function test_recap_referenced_by_employee_is_blocked(): void
    {
        $recap = $this->makeRecap();

        EmployeeFactory::new()->createdBy($this->user)->create([
            'employee_code' => 'EMP-DEL-'.fake()->unique()->numberBetween(1000, 9999),
            'project_name' => $recap->project_name,
        ]);

        $response = $this->delete(route('recap-proyek.destroySelected'), [
            'selected_recaps' => [$recap->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'tidak dapat dihapus karena masih digunakan',
            session('error')
        );
        $this->assertDatabaseHas('project_recaps', ['id' => $recap->id]);
    }

    public function test_recap_referenced_by_sdm_of_other_user_is_not_blocked(): void
    {
        $recap = $this->makeRecap();
        $otherUser = User::factory()->create();

        $this->makePayroll($recap, $otherUser);

        $response = $this->delete(route('recap-proyek.destroySelected'), [
            'selected_recaps' => [$recap->id],
        ]);

        $response->assertRedirect(route('recap-proyek.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('project_recaps', ['id' => $recap->id]);
    }
}
