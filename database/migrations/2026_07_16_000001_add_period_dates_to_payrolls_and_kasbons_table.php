<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // === PAYROLLS TABLE ===
        Schema::table('payrolls', function (Blueprint $table) {
            $table->date('period_start_date')->nullable()->after('week_number');
            $table->date('period_end_date')->nullable()->after('period_start_date');
        });

        // Drop old unique constraint and add new one based on period_start_date
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique('payrolls_unique_constraint');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_start_date'], 'payrolls_unique_period');
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
        });

        // === KASBONS TABLE ===
        Schema::table('kasbons', function (Blueprint $table) {
            $table->date('period_start_date')->nullable()->after('week_number');
            $table->date('period_end_date')->nullable()->after('period_start_date');
        });

        // === DATA MIGRATION: Populate period_start_date & period_end_date for existing records ===
        $this->migrateExistingPayrollData();
    }

    public function down(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->dropColumn(['period_start_date', 'period_end_date']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique('payrolls_unique_period');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_month', 'period_year', 'week_number'], 'payrolls_unique_constraint');
            $table->foreign('employee_id')->references('employee_code')->on('employees')->onDelete('cascade');
            $table->dropColumn(['period_start_date', 'period_end_date']);
        });
    }

    private function migrateExistingPayrollData(): void
    {
        $payrolls = DB::table('payrolls')
            ->whereNull('period_start_date')
            ->get();

        foreach ($payrolls as $payroll) {
            $weekDates = $this->getWeekDateRangeLegacy(
                $payroll->period_year,
                $payroll->period_month,
                $payroll->week_number
            );

            DB::table('payrolls')
                ->where('id', $payroll->id)
                ->update([
                    'period_start_date' => $weekDates['start'],
                    'period_end_date' => $weekDates['end'],
                ]);
        }
    }

    private function getWeekDateRangeLegacy(int $year, int $month, int $weekNumber): array
    {
        $firstDayOfMonth = \Carbon\Carbon::create($year, $month, 1);
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

        $weeks = [];
        $weekNumberCounter = 1;
        $currentDate = $firstDayOfMonth->copy();

        while ($currentDate->lte($lastDayOfMonth)) {
            if ($currentDate->dayOfWeek === \Carbon\Carbon::SUNDAY) {
                $currentDate->addDay();
                continue;
            }

            $weekStart = $currentDate->copy();
            $weekEnd = $weekStart->copy();

            if ($weekEnd->dayOfWeek !== \Carbon\Carbon::SATURDAY) {
                while ($weekEnd->dayOfWeek !== \Carbon\Carbon::SATURDAY && $weekEnd->lt($lastDayOfMonth)) {
                    $weekEnd->addDay();
                }
            }

            if ($weekEnd->gt($lastDayOfMonth)) {
                $weekEnd = $lastDayOfMonth->copy();
            }

            $weeks[] = [
                'start' => $weekStart->copy()->format('Y-m-d'),
                'end' => $weekEnd->copy()->format('Y-m-d'),
            ];

            $weekNumberCounter++;
            $currentDate = $weekEnd->copy()->addDays(2);
        }

        $index = $weekNumber - 1;
        if ($index >= 0 && $index < count($weeks)) {
            return $weeks[$index];
        }

        return [
            'start' => $firstDayOfMonth->format('Y-m-d'),
            'end' => $firstDayOfMonth->format('Y-m-d'),
        ];
    }
};
