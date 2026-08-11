<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\UpdatePayrollRequest;
use App\Models\Sdm\Attendance;
use App\Models\Sdm\Executive;
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
     *
     * Filter mencakup bulan, tahun, minggu, dan proyek. Dropdown proyek pada
     * filter maupun modal generate memakai komponen searchable yang mengambil
     * data dari Rekap Proyek (route employee.projects-dropdown), bukan daftar
     * proyek statis.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $weekNumber = $request->input('week_number');
        $projectName = $request->input('project_name');

        $allPayrolls = $this->payrollService->getPayrollsForIndex(
            $search,
            $month ? (int) $month : null,
            $year ? (int) $year : null,
            $weekNumber ? (int) $weekNumber : null,
            $projectName ?: null,
        );

        $payrollGroups = $this->payrollService->paginatePayrollGroups(
            $this->payrollService->groupPayrollsForView($allPayrolls)
        );

        // Payroll pada halaman saat ini (untuk loop modal edit/detail).
        $currentPayrolls = $payrollGroups->getCollection()->flatMap(
            fn ($group) => $group['payrolls']
        );

        $projects = $this->payrollService->getProjectOptions();

        // Petinggi milik user untuk pemilihan penandatangan pada modal
        // Generate Payroll (blok Disetujui/Diperiksa/Dibuat oleh).
        $executives = Executive::where('created_by', auth()->id())
            ->orderBy('name')
            ->get();

        return view('pages.sdm.payroll', compact('payrollGroups', 'currentPayrolls', 'search', 'month', 'year', 'weekNumber', 'projectName', 'projects', 'executives'));
    }

    /**
     * Menormalkan input proyek (tunggal/array) menjadi array nama proyek
     * yang tidak kosong.
     *
     * @param  mixed  $input
     * @return array<int, string>
     */
    private function normalizeProjectNames(mixed $input): array
    {
        $names = is_array($input) ? $input : (array) $input;

        return array_values(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $names
        )));
    }

    /**
     * Membersihkan teks agar aman dipakai sebagai nama file.
     *
     * @param  string  $name
     * @return string
     */
    private function sanitizeForFilename(string $name): string
    {
        $name = preg_replace('/\s+/', '_', trim($name));
        return preg_replace('/[^A-Za-z0-9_\-]+/', '', $name) ?: 'Semua_Proyek';
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

    public function create(Request $request)
    {
        return $this->index($request);
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

        $payroll->update([
            'project_name' => $validated['project_name'],
            'notes' => $validated['notes'] ?: null,
        ]);

        return redirect()->route('payroll.index')
            ->with('success', 'Payroll draft berhasil diperbarui!');
    }

    /**
     * Memvalidasi kelengkapan absensi untuk periode dan proyek tertentu.
     *
     * Menerima period_start_date, period_end_date, dan project_name (bisa
     * array nama proyek) dari frontend.
     */
    public function checkAttendanceCompleteness(Request $request)
    {
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);
        $projectNames = $this->normalizeProjectNames($request->input('project_name'));

        if (empty($projectNames)) {
            return response()->json([
                'can_generate' => false,
                'incomplete_employees' => [],
                'complete_employees' => [],
                'employees_without_project' => [],
                'already_generated' => [],
                'has_new_employees' => false,
                'period_start' => $startDate->format('d/m/Y'),
                'period_end' => $endDate->format('d/m/Y'),
                'working_days' => 0,
            ]);
        }

        $result = $this->payrollService->validateAttendanceCompleteness($startDate, $endDate, $projectNames);

        return response()->json($result);
    }

    /**
     * Membuat payroll mingguan untuk pekerja harian pada proyek tertentu.
     *
     * Menerima period_start_date, period_end_date, project_name (bisa array
     * nama proyek), serta penanda tangan per proyek (signatories[Nama
     * Proyek][disetujui|diperiksa|dibuat] = ID petinggi) dari frontend.
     * Penanda tangan bersifat opsional dan disimpan sebagai snapshot pada
     * tiap payroll untuk kebutuhan blok tanda tangan PDF.
     */
    public function generate(Request $request)
    {
        $startDate = Carbon::parse($request->period_start_date);
        $endDate = Carbon::parse($request->period_end_date);
        $projectNames = $this->normalizeProjectNames($request->input('project_name'));

        if (empty($projectNames)) {
            return redirect()->back()
                ->with('error', 'Pilih minimal satu proyek terlebih dahulu.');
        }

        // Penanda tangan dipilih PER PROYEK (setiap proyek bisa berbeda).
        // Bersifat OPSIONAL — bila kosong, blok tanda tangan PDF menampilkan
        // garis putus-putus sebagai fallback.
        $signatories = $request->input('signatories', []);

        $result = $this->payrollService->generatePayroll(
            $startDate,
            $endDate,
            $projectNames,
            $signatories
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
     *
     * Menghormati filter aktif (bulan, tahun, minggu, dan proyek) sehingga
     * output hanya memuat payroll sesuai filter yang dipilih.
     */
    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $weekNumber = $request->input('week_number');
        $projectName = $request->input('project_name') ?: null;

        $payrolls = $this->payrollService->getPayrollsForExport(
            $month ? (int) $month : null,
            $year ? (int) $year : null,
            $weekNumber ? (int) $weekNumber : null,
            $projectName,
        );

        if ($payrolls === null) {
            return redirect()->route('payroll.index')
                ->with('error', 'Tidak ada data payroll untuk diexport!');
        }

        $projectPart = $projectName ? '_' . $this->sanitizeForFilename($projectName) : '';
        $fileName = 'Laporan_Payroll' . $projectPart . '_' . ($month ? $month . '_' : '') . ($year ? $year : 'Semua') . '_' . date('Ymd_His') . '.xlsx';

        $teamKasbonRecap = $this->payrollService->getTeamKasbonRecap($payrolls);

        return Excel::download(new PayrollExport($payrolls, $month ? (int) $month : null, $year ? (int) $year : null, $projectName, $teamKasbonRecap), $fileName);
    }

    /**
     * Mengekspor data payroll ke PDF (landscape A4).
     *
     * Menghormati filter aktif (bulan, tahun, minggu, dan proyek). Nama proyek
     * pada header diambil dari filter proyek yang dipilih.
     *
     * Menggunakan period_start_date dari data payroll pertama untuk menentukan
     * rentang tanggal detail absensi.
     */
    public function exportPdf(Request $request)
    {
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $year = $request->input('year') ? (int) $request->input('year') : null;
        $weekNumber = $request->input('week_number') ? (int) $request->input('week_number') : null;
        $projectName = $request->input('project_name') ?: null;

        $payrolls = $this->payrollService->getPayrollsForExport($month, $year, $weekNumber, $projectName);

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

        // Nama proyek diambil langsung dari filter proyek yang dipilih user
        // (Print Laporan hanya aktif saat proyek dipilih).
        $projectName = $projectName ?: null;

        // Memuat data absensi menggunakan period_start_date dari data payroll
        $dateRange = '';
        $weekDays = [];

        $firstPayroll = $payrolls->first();
        if ($firstPayroll && $firstPayroll->period_start_date && $firstPayroll->period_end_date) {
            $startDate = Carbon::parse($firstPayroll->period_start_date);
            $endDate = Carbon::parse($firstPayroll->period_end_date);
            $dateRange = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');

            // Membuat array hari untuk kolom header (Senin-Sabtu = 6 hari kerja).
            // Minggu dikecualikan karena hari Minggu adalah hari libur
            // (tidak ada absensi maupun lembur di hari Minggu).
            $current = $startDate->copy();
            for ($i = 0; $i < 6; $i++) {
                $weekDays[] = $current->format('Y-m-d');
                $current->addDay();
            }
            $lastWeekDay = $current->copy()->subDay();

            foreach ($payrolls as $payroll) {
                $payroll->attendances = Attendance::where('employee_id', $payroll->employee_id)
                    ->whereBetween('attendance_date', [
                        $startDate->format('Y-m-d'),
                        $lastWeekDay->format('Y-m-d'),
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

        $teamKasbonRecap = $this->payrollService->getTeamKasbonRecap($payrolls);

        // Snapshot petinggi untuk blok tanda tangan (Disetujui/Diperiksa/Dibuat).
        // Mengutamakan pilihan yang tersimpan saat payroll di-generate;
        // payroll lama (belum punya snapshot) memakai fallback garis putus-putus.
        $signatures = $this->payrollService->getPayrollSignatures($payrolls->first());

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
            'teamKasbonRecap' => $teamKasbonRecap,
            'signatures' => $signatures,
        ];

        $pdf = Pdf::loadView('exports.sdm.payroll-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        $filenameParts = ['Payroll'];
        if ($projectName) {
            $filenameParts[] = $this->sanitizeForFilename($projectName);
        }
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
