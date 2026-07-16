<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sdm\StoreKasbonRequest;
use App\Http\Requests\Sdm\UpdateKasbonRequest;
use App\Http\Requests\Sdm\DestroySelectedKasbonRequest;
use App\Models\Sdm\Kasbon;
use App\Services\Sdm\KasbonService;
use Illuminate\Http\Request;

/**
 * Controller for managing cash advance (kasbon) operations.
 *
 * Handles HTTP requests for kasbon CRUD, search, filtering,
 * max kasbon check, and total calculation.
 *
 * Business logic is delegated to KasbonService.
 * Validation is handled by dedicated FormRequest classes.
 */
class KasbonController extends Controller
{
    /**
     * The kasbon service instance.
     *
     * @var KasbonService
     */
    protected KasbonService $kasbonService;

    /**
     * Create a new controller instance.
     *
     * @param  KasbonService  $kasbonService  The kasbon service
     */
    public function __construct(KasbonService $kasbonService)
    {
        $this->kasbonService = $kasbonService;
    }

    /**
     * Display the kasbon listing page with search and filters.
     *
     * @param  Request  $request  HTTP request with optional filter parameters
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $kasbons = $this->kasbonService->getPaginatedKasbons(
            $request->input('search'),
            $request->input('month') ? (int) $request->input('month') : null,
            $request->input('year') ? (int) $request->input('year') : null,
            $request->input('status'),
            $request->input('type')
        );

        $employees = $this->kasbonService->getAllEmployees();
        $divisions = $this->kasbonService->getAllDivisions();

        return view('pages.sdm.kasbon', compact('kasbons', 'employees', 'divisions'));
    }

    /**
     * Store a new kasbon record.
     *
     * Validates input via StoreKasbonRequest, then delegates
     * creation and attendance-based validation to KasbonService.
     *
     * @param  StoreKasbonRequest  $request  Validated kasbon data
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreKasbonRequest $request)
    {
        $validated = $request->validated();

        // Validate attendance-based kasbon limit for personal kasbon
        if ($validated['kasbon_type'] === 'personal') {
            $validation = $this->kasbonService->validatePersonalKasbonLimit(
                $validated['employee_id'],
                $validated['period_start_date'],
                $validated['kasbon_date'],
                $validated['amount']
            );

            if (!$validation['valid']) {
                return redirect()->back()->withInput()->with('error', $validation['message']);
            }
        }

        $this->kasbonService->storeKasbon($validated);

        return redirect()->back()->with('success', 'Kasbon berhasil ditambahkan');
    }

    /**
     * Update an existing kasbon record.
     *
     * Only pending kasbons can be updated. Validates input via
     * UpdateKasbonRequest, then delegates to KasbonService.
     *
     * @param  UpdateKasbonRequest  $request     Validated kasbon data
     * @param  string               $kasbonCode  The kasbon code to update
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateKasbonRequest $request, string $kasbonCode)
    {
        $kasbon = Kasbon::findOrFail($kasbonCode);

        if ($kasbon->status === 'deducted') {
            return redirect()->back()->with('error', 'Kasbon yang sudah dipotong tidak bisa diubah');
        }

        $validated = $request->validated();

        // Validate attendance-based kasbon limit for personal kasbon
        if ($validated['kasbon_type'] === 'personal' && !empty($validated['period_start_date'])) {
            $validation = $this->kasbonService->validatePersonalKasbonUpdate(
                $validated['employee_id'],
                $validated['period_start_date'],
                $validated['kasbon_date'],
                $validated['amount']
            );

            if (!$validation['valid']) {
                return redirect()->back()->withInput()->with('error', $validation['message']);
            }
        }

        $this->kasbonService->updateKasbon($kasbon, $validated);

        return redirect()->back()->with('success', 'Kasbon berhasil diupdate');
    }

    /**
     * Bulk delete selected kasbon records.
     *
     * Only pending kasbons can be deleted. Deducted kasbons are skipped.
     *
     * @param  DestroySelectedKasbonRequest  $request  Validated selection data
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(DestroySelectedKasbonRequest $request)
    {
        $result = $this->kasbonService->deleteSelectedKasbons($request->input('selected_kasbons'));

        if ($result['deleted'] > 0 && $result['skipped'] > 0) {
            return redirect()->back()->with('success', "Berhasil menghapus {$result['deleted']} kasbon. {$result['skipped']} kasbon tidak dapat dihapus karena sudah dipotong.");
        } elseif ($result['deleted'] > 0) {
            return redirect()->back()->with('success', "Data terpilih berhasil dihapus. ({$result['deleted']} kasbon)");
        } else {
            return redirect()->back()->with('error', 'Semua kasbon yang dipilih sudah dipotong dan tidak dapat dihapus.');
        }
    }

    /**
     * Get total kasbon for a specific period (AJAX endpoint).
     *
     * Returns personal and team kasbon totals for a given period.
     * Used by the payroll generate modal.
     *
     * @param  Request  $request  HTTP request with period_start_date and employee_id
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
     * Check maximum allowed kasbon based on attendance (AJAX endpoint).
     *
     * Returns employee attendance info, max kasbon limit, and
     * whether the payroll for the period is already paid.
     *
     * @param  Request  $request  HTTP request with employee_id, period_start_date, kasbon_date
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

        // Determine appropriate HTTP status code based on result
        if ($result['success']) {
            $statusCode = 200;
        } elseif ($result['message'] === 'Karyawan tidak ditemukan') {
            $statusCode = 404;
        } else {
            $statusCode = 400;
        }

        return response()->json($result, $statusCode);
    }
}
