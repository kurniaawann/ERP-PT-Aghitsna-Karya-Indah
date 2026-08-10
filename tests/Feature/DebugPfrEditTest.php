<?php

namespace Tests\Feature;

use App\Models\Finance\ProjectRecap;
use App\Models\Report\ProjectFinancialReportItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugPfrEditTest extends TestCase
{
    public function test_update_recap_with_new_transaction(): void
    {
        $user = User::where('email', 'adminaghitsna1@gmail.com')->first();
        $this->actingAs($user);

        $recap = ProjectRecap::find('RP-00001');
        $report = $recap->financialReport;

        $countBefore = ProjectFinancialReportItem::where('project_financial_report_id', $report->id)->count();

        $response = $this->put(route('project-financial-report.update', $recap), [
            'project_name' => $recap->project_name,
            'location' => $recap->location,
            'total_rab' => '1.000.000',
            'items' => [
                [
                    'transaction_category_id' => 25, // Pengeluaran (EXPENSE)
                    'transaction_date' => '2026-08-10',
                    'description' => 'Kasbon Transport Tukang (TEST)',
                    'expense_amount' => '150.000',
                    'keterangan_bon' => 'Bon Test',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('project-financial-report.index'));

        $countAfter = ProjectFinancialReportItem::where('project_financial_report_id', $report->id)->count();
        $this->assertSame($countBefore + 1, $countAfter);

        // cleanup
        ProjectFinancialReportItem::where('project_financial_report_id', $report->id)
            ->where('description', 'Kasbon Transport Tukang (TEST)')
            ->delete();
    }
}
