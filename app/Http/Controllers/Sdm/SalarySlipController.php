<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\SalarySlip;
use App\Services\Sdm\SalarySlipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Controller untuk Slip Gaji Karyawan Bulanan.
 *
 * Halaman indeksnya kini menjadi tab "Slip Gaji" di dalam halaman Data
 * Payroll (dikelola PayrollController@index); controller ini menangani
 * seluruh aksi (eligible, generate, update, bayar, hapus, cetak PDF).
 *
 * Hanya role admin & superadmin (route dibungkus middleware role:admin;
 * superadmin lolos otomatis via CheckRole). Alur:
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
     * Halaman Slip Gaji dipindah menjadi tab di dalam halaman Data Payroll
     * (route payroll.index?tab=salary-slip), sehingga URL lama /salary-slip
     * cukup dialihkan ke tab tersebut.
     */
    public function index(Request $request)
    {
        return redirect()->route('payroll.index', array_merge(
            $request->query(),
            ['tab' => 'salary-slip']
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

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $message = $result['message'];

        // Perjelas periode tujuan agar slip yang baru dibuat mudah ditemukan
        // (mis. "Berhasil membuat 1 slip gaji. Periode Desember 2026.").
        if ($result['count'] > 0) {
            $message .= ' Periode '.$this->monthNames[$periodMonth].' '.$periodYear.'.';
        }

        return redirect()
            ->route('salary-slips.index', ['month' => $periodMonth, 'year' => $periodYear])
            ->with('success', $message);
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

        $pdf = Pdf::loadView('exports.sdm.salary-slip-pdf', [
            'slips' => collect([$salarySlip]),
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($this->buildFileName($salarySlip));
    }

    /**
     * Cetak PDF slip gaji terpilih (checkbox) — satu slip per halaman.
     */
    public function printPdfSelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada slip gaji yang dipilih!');
        }

        $slips = $this->service->getSlipsByIds($ids);

        if ($slips->isEmpty()) {
            return back()->with('error', 'Tidak ada slip gaji yang ditemukan!');
        }

        $pdf = Pdf::loadView('exports.sdm.salary-slip-pdf', [
            'slips' => $slips,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Slip_Gaji_Terpilih_'.date('Y-m-d').'.pdf');
    }

    /**
     * Cetak PDF slip gaji sesuai filter (bulan/tahun/pencarian) — satu slip
     * per halaman.
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

        $pdf = Pdf::loadView('exports.sdm.salary-slip-pdf', [
            'slips' => $slips,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $periodText = $month && $year
            ? $this->monthNames[$month].' '.$year
            : 'Semua_Periode';

        return $pdf->download('Slip_Gaji_'.str_replace(' ', '_', $periodText).'.pdf');
    }
}
