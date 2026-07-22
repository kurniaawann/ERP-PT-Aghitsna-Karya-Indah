<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\UpdatePayrollRequest;
use App\Models\Sdm\Payroll;
use App\Services\Sdm\PayrollService;
use App\Exports\Sdm\PayrollExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk mengelola payroll karyawan.
 *
 * Menangani permintaan HTTP untuk daftar payroll, validasi absensi,
 * pembuatan, pembayaran massal, penghapusan, dan ekspor (Excel/PDF).
 *
 * Seluruh logika bisnis didelegasikan ke PayrollService.
 * Validasi untuk pembaruan ditangani oleh UpdatePayrollRequest.
 */
class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    protected array $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Menampilkan daftar payroll dengan paginasi, pencarian, dan penyaringan.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $weekNumber = $request->input('week_number');

        $payrolls = $this->payrollService->getPaginatedPayrolls(
            $search,
            $month ? (int) $month : null,
            $year ? (int) $year : null,
            $weekNumber ? (int) $weekNumber : null,
        );

        return view('pages.sdm.payroll', compact('payrolls', 'search', 'month', 'year', 'weekNumber'));
    }

    /**
     * Mendapatkan opsi minggu untuk bulan dan tahun tertentu.
     *
     * Mengembalikan array JSON objek minggu dengan week_number, label, start, end,
     * start_date, end_date. Digunakan oleh frontend untuk mengisi dropdown minggu.
     */
    public function getWeeks(Request $request)
    {
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return response()->json(['weeks' => []]);
        }

        $weeks = PayrollService::getWeeksInMonth($year, $month);

        $result = array_map(function ($week) {
            $start = $week['start'];
            $end = $week['end'];

            if ($start->month === $end->month) {
                $label = 'Minggu ' . $week['week_number'] .
                    ' (' . $start->format('d') . '-' . $end->format('d M') . ')';
            } else {
                $label = 'Minggu ' . $week['week_number'] .
                    ' (' . $start->format('d M') . ' - ' . $end->format('d M') . ')';
            }

            return [
                'week_number' => $week['week_number'],
                'label' => $label,
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ];
        }, $weeks);

        return response()->json(['weeks' => $result]);
    }

    public function create()
    {
        return view('pages.sdm.payroll');
    }

    /**
     * Memperbarui data payroll draft.
     */
    public function update(UpdatePayrollRequest $request, Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->route('payroll.index')
                ->with('error', 'Payroll yang sudah dibayar tidak dapat diubah.');
        }

        $validated = $request->validated();

        if ((int) $validated['additional_expenses'] > 0 && empty(trim((string) ($validated['additional_expenses_notes'] ?? '')))) {
            return redirect()->back()
                ->withErrors([
                    'additional_expenses_notes' => 'Keterangan pengeluaran tambahan wajib diisi jika nominal lebih dari 0.',
                ])
                ->withInput();
        }

        $payroll->update([
            'project_name' => $validated['project_name'],
            'additional_expenses' => (int) $validated['additional_expenses'],
            'additional_expenses_notes' => $validated['additional_expenses_notes'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        return redirect()->route('payroll.index')
            ->with('success', 'Payroll draft berhasil diperbarui!');
    }

    /**
     * Memvalidasi kelengkapan absensi untuk periode tertentu.
     *
     * Menerima period_start_date dan period_end_date dari frontend.
     */
    public function checkAttendanceCompleteness(Request $request)
    {
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);

        $result = $this->payrollService->validateAttendanceCompleteness($startDate, $endDate);

        return response()->json($result);
    }

    /**
     * Membuat payroll mingguan untuk pekerja harian.
     *
     * Menerima period_start_date dan period_end_date dari frontend.
     */
    public function generate(Request $request)
    {
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);

        $expenses = $this->payrollService->validateAdditionalExpenses(
            $request->input('additional_expenses'),
            $request->input('additional_expenses_notes')
        );

        $result = $this->payrollService->generatePayroll(
            $startDate,
            $endDate,
            $expenses['total'],
            $expenses['notes'],
            $request->input('project_name')
        );

        if ($result['success']) {
            return redirect()->route('payroll.index')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Membayar beberapa data payroll yang dipilih secara massal.
     */
    public function bulkPay(Request $request)
    {
        $ids = $request->input('ids');
        $paymentDate = $request->input('payment_date', now()->toDateString());

        $result = $this->payrollService->bulkPayPayrolls($ids, $paymentDate);

        if ($result['success']) {
            return redirect()->route('payroll.index')->with('success', $result['message']);
        }

        return redirect()->route('payroll.index')->with('error', $result['message']);
    }

    /**
     * Menghapus data payroll draft yang dipilih secara massal.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        $result = $this->payrollService->deleteDraftPayrolls($ids);

        if ($result['success']) {
            return redirect()->route('payroll.index')->with('success', $result['message']);
        }

        return redirect()->route('payroll.index')->with('error', $result['message']);
    }

    /**
     * Mengekspor data payroll ke Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $weekNumber = $request->input('week_number');

        $payrolls = $this->payrollService->getPayrollsForExport(
            $month ? (int) $month : null,
            $year ? (int) $year : null,
            $weekNumber ? (int) $weekNumber : null,
        );

        if ($payrolls === null) {
            return redirect()->route('payroll.index')
                ->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        $fileName = 'Laporan_Payroll_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new PayrollExport($payrolls, $month, $year), $fileName);
    }

    /**
     * Mengekspor data payroll ke PDF (landscape A4).
     *
     * Menggunakan period_start_date dari data payroll pertama untuk menentukan
     * rentang tanggal detail absensi.
     */
    public function exportPdf(Request $request)
    {
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $year = $request->input('year') ? (int) $request->input('year') : null;
        $weekNumber = $request->input('week_number') ? (int) $request->input('week_number') : null;

        $payrolls = $this->payrollService->getPayrollsForExport($month, $year, $weekNumber);

        if ($payrolls === null) {
            return redirect()->route('payroll.index')
                ->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        // Memformat teks periode
        if ($month && $year) {
            $periodText = $this->monthNames[$month] . ' ' . $year;
        } elseif ($year) {
            $periodText = 'Tahun ' . $year;
        } else {
            $periodText = 'Semua Periode';
        }

        $projectName = $payrolls->first()->project_name ?? null;

        // Memuat data absensi menggunakan period_start_date dari data payroll
        $dateRange = '';
        $weekDays = [];

        $firstPayroll = $payrolls->first();
        if ($firstPayroll && $firstPayroll->period_start_date && $firstPayroll->period_end_date) {
            $startDate = Carbon::parse($firstPayroll->period_start_date);
            $endDate = Carbon::parse($firstPayroll->period_end_date);
            $dateRange = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');

            // Membuat array hari minggu untuk header kolom (Senin-Sabtu saja)
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                if ($current->dayOfWeek !== Carbon::SUNDAY) {
                    $weekDays[] = $current->format('Y-m-d');
                }
                $current->addDay();
            }

            foreach ($payrolls as $payroll) {
                $payroll->attendances = \App\Models\Sdm\Attendance::where('employee_id', $payroll->employee_id)
                    ->whereBetween('attendance_date', [
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d'),
                    ])
                    ->get();
            }
        } else {
            foreach ($payrolls as $payroll) {
                $payroll->attendances = collect();
            }
        }

        $totalBaseSalary = $payrolls->sum('base_salary');
        $totalDeduction = $payrolls->sum('deduction_amount');
        $totalOvertime = $payrolls->sum('overtime_total');
        $totalNetSalary = $payrolls->sum('net_salary');

        $data = [
            'payrolls' => $payrolls,
            'periodText' => $periodText,
            'projectName' => $projectName,
            'dateRange' => $dateRange,
            'weekDays' => $weekDays,
            'totalBaseSalary' => $totalBaseSalary,
            'totalDeduction' => $totalDeduction,
            'totalOvertime' => $totalOvertime,
            'totalNetSalary' => $totalNetSalary,
        ];

        $pdf = Pdf::loadView('exports.sdm.payroll-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        $filenameParts = ['Payroll'];
        if ($month) {
            $filenameParts[] = $this->monthNames[$month];
        }
        if ($year) {
            $filenameParts[] = $year;
        }
        if ($firstPayroll && $firstPayroll->period_start_date) {
            $filenameParts[] = Carbon::parse($firstPayroll->period_start_date)->format('d_M');
            if ($firstPayroll->period_end_date) {
                $filenameParts[] = 'sd';
                $filenameParts[] = Carbon::parse($firstPayroll->period_end_date)->format('d_M_Y');
            }
        }
        if (!$month && !$year) {
            $filenameParts[] = 'Semua_Periode';
        }
        $fileName = implode('_', $filenameParts) . '.pdf';

        return $pdf->download($fileName);
    }
}
