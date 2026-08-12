<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Executive;
use App\Models\Sdm\SalarySlip;
use App\Services\Sdm\SalarySlipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller untuk Slip Gaji Karyawan Bulanan.
 *
 * Hanya role admin (route dibungkus middleware role:admin). Alur:
 * generate slip draft dari daftar karyawan bulanan, isi rekap absensi
 * per karyawan, bayar, lalu cetak PDF (per slip / rekap bulanan).
 */
class SalarySlipController extends Controller
{
    protected array $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(
        protected SalarySlipService $service
    ) {}

    /**
     * Daftar slip gaji dengan filter (bulan, tahun, pencarian).
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $year = $request->input('year') ? (int) $request->input('year') : null;

        $slips = $this->service->getSlipsForIndex($search, $month, $year)
            ->paginate(10)
            ->appends($request->all());

        $executives = Executive::where('created_by', auth()->id())
            ->orderBy('name')
            ->get();

        // Daftar karyawan bulanan yang belum punya slip untuk periode filter
        // (atau bulan berjalan bila tidak ada filter) — dipakai modal generate.
        $filterMonth = $month ?: (int) date('n');
        $filterYear = $year ?: (int) date('Y');
        $eligibleEmployees = $this->service->getEligibleEmployees($filterYear, $filterMonth);

        return view('pages.sdm.salary-slips', compact(
            'slips',
            'search',
            'month',
            'year',
            'executives',
            'eligibleEmployees',
            'filterMonth',
            'filterYear'
        ));
    }

    /**
     * Endpoint AJAX: karyawan bulanan yang belum punya slip pada periode
     * tertentu (dipakai modal generate agar daftar selalu sesuai periode).
     */
    public function eligibleEmployees(Request $request)
    {
        $periodMonth = (int) $request->input('period_month', (int) date('n'));
        $periodYear = (int) $request->input('period_year', (int) date('Y'));

        $employees = $this->service->getEligibleEmployees($periodYear, $periodMonth);

        return response()->json([
            'data' => $employees->map(fn ($employee) => [
                'value' => $employee->employee_code,
                'label' => $employee->name.' - '.$employee->employee_code,
            ])->values(),
        ]);
    }

    /**
     * Membuat slip gaji draft dari karyawan bulanan terpilih.
     */
    public function generate(Request $request)
    {
        $periodMonth = (int) $request->input('period_month');
        $periodYear = (int) $request->input('period_year');
        $employeeCodes = $request->input('employee_codes', []);

        if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 2000 || $periodYear > 2100) {
            return back()->with('error', 'Periode tidak valid.');
        }

        if (empty($employeeCodes)) {
            return back()->with('error', 'Pilih minimal satu karyawan bulanan terlebih dahulu.');
        }

        $result = $this->service->generateSlips(
            $employeeCodes,
            $periodYear,
            $periodMonth,
            $request->input('signatures', []),
            $request->input('holidays', [])
        );

        return $result['success']
            ? redirect()->route('salary-slips.index', ['month' => $periodMonth, 'year' => $periodYear])
                ->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Menyimpan matriks absensi, PPh 21 manual, dan catatan slip draft.
     */
    public function update(Request $request, SalarySlip $salarySlip)
    {
        try {
            $this->service->updateAttendance(
                $salarySlip,
                $request->input('attendance', []),
                $request->has('pph21') ? (int) $request->input('pph21') : null
            );
            $this->service->updateNotes($salarySlip, $request->input('notes'));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Data absensi slip gaji berhasil diperbarui!');
    }

    /**
     * Membayar beberapa slip gaji yang dipilih secara massal.
     */
    public function bulkPay(Request $request)
    {
        $ids = $request->input('ids');
        $paymentDate = $request->input('payment_date', now()->toDateString());

        $result = $this->service->bulkPay($ids, $paymentDate);

        return $result['success']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Menghapus slip gaji yang dipilih secara massal.
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        $result = $this->service->deleteSlips($ids);

        return $result['success']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Menyimpan nama file PDF slip.
     */
    private function buildFileName(SalarySlip $slip, string $suffix = ''): string
    {
        $name = $slip->employee?->name ?? $slip->employee_code;
        $name = preg_replace('/[^A-Za-z0-9_\-]+/', '_', trim($name)) ?: 'Slip';

        return 'Slip_Gaji_'.$name.'_'.$slip->formatted_period.($suffix ? '_'.$suffix : '').'.pdf';
    }

    /**
     * Cetak PDF satu slip gaji (portrait A4).
     */
    public function printPdf(SalarySlip $salarySlip)
    {
        if ($salarySlip->created_by !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke slip gaji ini.');
        }

        $data = [
            'slip' => $salarySlip,
            'signatures' => $this->service->getSlipSignatures($salarySlip),
        ];

        $pdf = Pdf::loadView('exports.sdm.salary-slip-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($this->buildFileName($salarySlip));
    }

    /**
     * Cetak PDF rekap slip gaji untuk periode yang sedang difilter.
     */
    public function printBulk(Request $request)
    {
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $year = $request->input('year') ? (int) $request->input('year') : null;
        $search = $request->input('search');

        $slips = $this->service->getSlipsForIndex($search, $month, $year)->get();

        if ($slips->isEmpty()) {
            return back()->with('error', 'Tidak ada slip gaji untuk dicetak!');
        }

        $periodText = $month && $year
            ? $this->monthNames[$month].' '.$year
            : 'Semua Periode';

        $data = [
            'slips' => $slips,
            'periodText' => $periodText,
            'signatures' => $this->service->getSlipSignatures($slips->first()),
            'printedAt' => Carbon::now(),
        ];

        $pdf = Pdf::loadView('exports.sdm.salary-slips-recap-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Rekap_Slip_Gaji_'.str_replace(' ', '_', $periodText).'.pdf';

        return $pdf->download($fileName);
    }
}
