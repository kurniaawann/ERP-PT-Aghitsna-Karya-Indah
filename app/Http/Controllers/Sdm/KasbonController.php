<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Kasbon;
use App\Models\Sdm\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KasbonController extends Controller
{
    /**
     * Menampilkan halaman daftar kasbon dengan fitur filter dan pencarian.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');
        $status = $request->input('status');
        $type = $request->input('type');

        $kasbons = Kasbon::with('employee')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('kasbon_code', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($empQuery) use ($search) {
                            $empQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($month, function ($query, $month) {
                return $query->where('period_month', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->where('period_year', $year);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($type, function ($query, $type) {
                return $query->where('kasbon_type', $type);
            })
            ->latest('kasbon_date')
            ->latest('created_at')
            ->paginate(10);

        // Ambil data employees untuk dropdown
        $employees = Employee::orderBy('name')->get();

        return view('pages.sdm.kasbon', compact('kasbons', 'employees', 'search', 'month', 'year', 'status', 'type'));
    }

    /**
     * Simpan kasbon baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1|max:4',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'notes' => 'nullable|string|max:500',
        ], [
            'kasbon_type.required' => 'Jenis kasbon harus dipilih',
            'employee_id.required_if' => 'Karyawan harus dipilih untuk kasbon personal',
            'division.required_if' => 'Divisi harus dipilih untuk kasbon tim',
            'amount.required' => 'Jumlah kasbon harus diisi',
            'amount.min' => 'Jumlah kasbon minimal Rp 1.000',
            'kasbon_date.required' => 'Tanggal kasbon harus diisi',
            'period_month.required' => 'Bulan periode harus diisi',
            'period_year.required' => 'Tahun periode harus diisi',
        ]);

        // Generate kasbon code
        $validated['kasbon_code'] = Kasbon::generateKasbonCode();
        $validated['status'] = 'pending';

        // Jika kasbon team, set employee_id ke null
        if ($validated['kasbon_type'] === 'team') {
            $validated['employee_id'] = null;
        } else {
            // Jika kasbon personal, set division ke null
            $validated['division'] = null;
        }

        Kasbon::create($validated);

        return redirect()->back()->with('success', 'Kasbon berhasil ditambahkan');
    }

    /**
     * Update kasbon yang sudah ada
     */
    public function update(Request $request, $kasbonCode)
    {
        $kasbon = Kasbon::findOrFail($kasbonCode);

        // Hanya bisa update jika status masih pending
        if ($kasbon->status === 'deducted') {
            return redirect()->back()->with('error', 'Kasbon yang sudah dipotong tidak bisa diubah');
        }

        $validated = $request->validate([
            'kasbon_type' => 'required|in:personal,team',
            'employee_id' => 'required_if:kasbon_type,personal|nullable|exists:employees,employee_code',
            'division' => 'required_if:kasbon_type,team|nullable|string|max:100',
            'amount' => 'required|integer|min:1000',
            'kasbon_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1|max:4',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020',
            'notes' => 'nullable|string|max:500',
        ]);

        // Jika kasbon team, set employee_id ke null
        if ($validated['kasbon_type'] === 'team') {
            $validated['employee_id'] = null;
        } else {
            // Jika kasbon personal, set division ke null
            $validated['division'] = null;
        }

        $kasbon->update($validated);

        return redirect()->back()->with('success', 'Kasbon berhasil diupdate');
    }

    /**
     * Hapus kasbon
     */
    public function destroy($kasbonCode)
    {
        $kasbon = Kasbon::findOrFail($kasbonCode);

        // Hanya bisa hapus jika status masih pending
        if ($kasbon->status === 'deducted') {
            return redirect()->back()->with('error', 'Kasbon yang sudah dipotong tidak bisa dihapus');
        }

        $kasbon->delete();

        return redirect()->back()->with('success', 'Kasbon berhasil dihapus');
    }

    /**
     * Get total kasbon untuk periode tertentu (untuk ditampilkan saat generate payroll)
     */
    public function getTotalForPeriod(Request $request)
    {
        $month = $request->period_month;
        $year = $request->period_year;
        $weekNumber = $request->week_number;
        $employeeId = $request->employee_id;

        if ($employeeId) {
            // Get kasbon personal untuk employee tertentu
            $personalKasbon = Kasbon::getTotalForEmployee($employeeId, $month, $year, $weekNumber);
        } else {
            $personalKasbon = 0;
        }

        // Get kasbon team untuk periode ini
        $teamKasbon = Kasbon::getTotalTeamKasbon($month, $year, $weekNumber);

        return response()->json([
            'personal_kasbon' => $personalKasbon,
            'team_kasbon' => $teamKasbon,
            'total_kasbon' => $personalKasbon + $teamKasbon,
        ]);
    }
}

