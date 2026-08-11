<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreKasbonRequest;
use App\Http\Requests\Sdm\UpdateKasbonRequest;
use App\Http\Requests\Sdm\DestroySelectedKasbonRequest;
use App\Models\Sdm\Kasbon;
use App\Services\Sdm\KasbonService;
use App\Services\InputNormalizer;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola operasi kasbon (cash advance).
 *
 * Menangani permintaan HTTP untuk CRUD kasbon, pencarian, penyaringan,
 * pemeriksaan kasbon maksimum, dan perhitungan total.
 *
 * Logika bisnis didelegasikan ke KasbonService.
 * Validasi ditangani oleh kelas FormRequest yang dedikasi.
 */
class KasbonController extends Controller
{
    /**
     * Instance layanan kasbon.
     *
     * @var KasbonService
     */
    protected KasbonService $kasbonService;

    /**
     * Membuat instance controller baru.
     *
     * @param  KasbonService  $kasbonService  Layanan kasbon
     */
    public function __construct(KasbonService $kasbonService)
    {
        $this->kasbonService = $kasbonService;
    }

    /**
     * Menampilkan halaman daftar kasbon dengan pencarian dan penyaringan.
     *
     * @param  Request  $request  Permintaan HTTP dengan parameter penyaringan opsional
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $kasbons = $this->kasbonService->getPaginatedKasbons(
            $request->input('search'),
            $request->input('month') ? (int) $request->input('month') : null,
            $request->input('year') ? (int) $request->input('year') : null,
            $request->input('status'),
            $request->input('type'),
            $request->input('payment_status'),
            $request->input('project_name')
        );

        $employees = $this->kasbonService->getAllEmployees();
        $divisions = $this->kasbonService->getAllDivisions();
        $projects = $this->kasbonService->getProjectOptions();

        return view('pages.sdm.kasbon', compact('kasbons', 'employees', 'divisions', 'projects'));
    }

    /**
     * Menyimpan data kasbon baru.
     *
     * Memvalidasi input melalui StoreKasbonRequest, kemudian mendelegasikan
     * pembuatan dan validasi berbasis absensi ke KasbonService.
     *
     * @param  StoreKasbonRequest  $request  Data kasbon yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreKasbonRequest $request)
    {
        $validated = $request->validated();

        $this->kasbonService->storeKasbon($validated);

        return redirect()->back()->with('success', 'Kasbon berhasil ditambahkan');
    }

    /**
     * Memperbarui data kasbon yang sudah ada.
     *
     * Hanya kasbon yang masih menunggu yang dapat diperbarui. Memvalidasi input melalui
     * UpdateKasbonRequest, kemudian mendelegasikan ke KasbonService.
     *
     * @param  UpdateKasbonRequest  $request     Data kasbon yang sudah divalidasi
     * @param  string               $kasbonCode  Kode kasbon yang akan diperbarui
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateKasbonRequest $request, string $kasbonCode)
    {
        $kasbon = Kasbon::findOrFail($kasbonCode);

        if ($kasbon->status === 'deducted') {
            return redirect()->back()->with('error', 'Kasbon yang sudah dipotong tidak bisa diubah');
        }

        if ($kasbon->payment_status !== 'unpaid') {
            return redirect()->back()->with('error', 'Kasbon yang sudah dibayar tidak bisa diubah');
        }

        $validated = $request->validated();

        $this->kasbonService->updateKasbon($kasbon, $validated);

        return redirect()->back()->with('success', 'Kasbon berhasil diupdate');
    }

    /**
     * Menghapus data kasbon yang dipilih secara massal.
     *
     * Hanya kasbon yang masih menunggu yang dapat dihapus. Kasbon yang sudah dipotong dilewati.
     *
     * @param  DestroySelectedKasbonRequest  $request  Data pilihan yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(DestroySelectedKasbonRequest $request)
    {
        $result = $this->kasbonService->deleteSelectedKasbons($request->input('selected_kasbons'));

        if ($result['deleted'] > 0 && $result['skipped'] > 0) {
            return redirect()->back()->with('success', "Berhasil menghapus {$result['deleted']} kasbon. {$result['skipped']} kasbon tidak dapat dihapus karena masih terhubung payroll.");
        } elseif ($result['deleted'] > 0) {
            return redirect()->back()->with('success', "Data terpilih berhasil dihapus. ({$result['deleted']} kasbon)");
        } else {
            return redirect()->back()->with('error', 'Semua kasbon yang dipilih masih terhubung payroll dan tidak dapat dihapus.');
        }
    }

    /**
     * Mendapatkan total kasbon untuk periode tertentu (endpoint AJAX).
     *
     * Mengembalikan total kasbon personal dan tim untuk periode yang diberikan.
     * Digunakan oleh modal pembuatan payroll.
     *
     * @param  Request  $request  Permintaan HTTP dengan period_start_date dan employee_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTotalForPeriod(Request $request)
    {
        $periodStartDate = $request->period_start_date;
        $employeeId = $request->employee_id;

        if ($periodStartDate) {
            $personalKasbon = $employeeId
                ? $this->kasbonService->getTotalForEmployee($employeeId, $periodStartDate)
                : 0;

            $teamKasbon = $this->kasbonService->getTotalTeamKasbon($periodStartDate);
        } else {
            $personalKasbon = 0;
            $teamKasbon = 0;
        }

        return response()->json([
            'personal_kasbon' => $personalKasbon,
            'team_kasbon' => $teamKasbon,
            'total_kasbon' => $personalKasbon + $teamKasbon,
        ]);
    }

    /**
     * Memeriksa kasbon maksimum yang diperbolehkan berdasarkan absensi (endpoint AJAX).
     *
     * Mengembalikan informasi absensi karyawan, batas kasbon maksimum, dan
     * apakah payroll untuk periode tersebut sudah dibayar.
     *
     * @param  Request  $request  Permintaan HTTP dengan employee_id, period_start_date, kasbon_date
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMaxKasbon(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $periodStartDate = $request->input('period_start_date');
        $kasbonDate = $request->input('kasbon_date');

        if (!$employeeId || !$periodStartDate || !$kasbonDate) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap. Pastikan karyawan, periode, dan tanggal kasbon sudah dipilih.',
            ], 400);
        }

        $result = $this->kasbonService->checkMaxKasbon($employeeId, $periodStartDate, $kasbonDate);

        // Menentukan kode status HTTP yang sesuai berdasarkan hasil
        if ($result['success']) {
            $statusCode = 200;
        } elseif ($result['message'] === 'Karyawan tidak ditemukan') {
            $statusCode = 404;
        } else {
            $statusCode = 400;
        }

        return response()->json($result, $statusCode);
    }

    /**
     * Mencatat pembayaran cicilan kasbon (tunai/manual).
     *
     * Menerima jumlah pembayaran dan mencatatnya sebagai cicilan.
     * Kasbon akan otomatis diperbarui (paid_amount, remaining_amount, payment_status).
     *
     * @param  Request   $request    Permintaan HTTP dengan amount
     * @param  string    $kasbonCode Kode kasbon yang akan dibayar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pay(Request $request, string $kasbonCode)
    {
        $kasbon = Kasbon::findOrFail($kasbonCode);

        if ($kasbon->payment_status === 'paid') {
            return redirect()->back()->with('error', 'Kasbon sudah lunas');
        }

        // Kasbon divisi ber-proyek tidak bisa dicicil manual: lunas otomatis
        // penuh saat payroll proyek + periode dibayar.
        if ($kasbon->kasbon_type === 'team' && ! empty($kasbon->project_names)) {
            return redirect()->back()->with('error', 'Kasbon divisi dengan proyek tidak bisa dibayar manual. Kasbon otomatis lunas saat payroll proyek tersebut dibayar.');
        }

        $request->validate([
            'amount' => 'required',
        ], [
            'amount.required' => 'Jumlah pembayaran harus diisi',
        ]);

        $amount = InputNormalizer::normalizeCurrency($request->amount);

        if ($amount <= 0) {
            return redirect()->back()->with('error', 'Jumlah pembayaran harus lebih dari 0');
        }

        if ($amount > $kasbon->remaining_amount) {
            return redirect()->back()->with('error', 'Jumlah pembayaran melebihi sisa hutang (' . $kasbon->formatted_remaining_amount . ')');
        }

        $this->kasbonService->recordPayment($kasbon, $amount, 'manual');

        $remaining = $kasbon->fresh()->remaining_amount;

        if ($remaining <= 0) {
            return redirect()->back()->with('success', 'Pembayaran berhasil! Kasbon sudah lunas.');
        }

        return redirect()->back()->with('success', 'Pembayaran cicilan berhasil. Sisa hutang: ' . $kasbon->fresh()->formatted_remaining_amount);
    }

    /**
     * Mendapatkan riwayat pembayaran kasbon (endpoint AJAX).
     *
     * @param  string  $kasbonCode  Kode kasbon
     * @return \Illuminate\Http\JsonResponse
     */
    public function payments(string $kasbonCode)
    {
        $kasbon = Kasbon::with('employee')->findOrFail($kasbonCode);
        $payments = $this->kasbonService->getPayments($kasbonCode);

        return response()->json([
            'kasbon' => [
                'kasbon_code' => $kasbon->kasbon_code,
                'amount' => $kasbon->amount,
                'paid_amount' => $kasbon->paid_amount,
                'remaining_amount' => $kasbon->remaining_amount,
                'payment_status' => $kasbon->payment_status,
                'employee_name' => $kasbon->employee?->name ?? '-',
            ],
            'payments' => $payments->map(fn($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'formatted_amount' => $p->formatted_amount,
                'payment_method' => $p->payment_method,
                'payment_method_label' => $p->payment_method_label,
                'payment_date' => $p->payment_date->format('d M Y'),
                'notes' => $p->notes,
            ]),
        ]);
    }
}
