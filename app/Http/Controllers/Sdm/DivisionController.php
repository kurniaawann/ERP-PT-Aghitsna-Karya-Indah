<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Sdm\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $divisions = Division::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->paginate(15);

        return view('pages.sdm.division', compact('divisions', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:divisions,name',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama divisi harus diisi',
            'name.unique' => 'Nama divisi sudah ada',
        ]);

        Division::create($validated);

        return redirect()->route('division.index')->with('success', 'Data divisi berhasil ditambahkan!');
    }

    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:divisions,name,' . $division->id,
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama divisi harus diisi',
            'name.unique' => 'Nama divisi sudah ada',
        ]);

        $division->update($validated);

        return redirect()->route('division.index')->with('success', 'Data divisi berhasil diperbarui!');
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('division.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Check if any division has employees
        $divisionsWithEmployees = Division::whereIn('id', $ids)
            ->whereHas('employees')
            ->pluck('name')
            ->toArray();

        if (!empty($divisionsWithEmployees)) {
            return redirect()->route('division.index')->with('error', 'Divisi ' . implode(', ', $divisionsWithEmployees) . ' masih memiliki karyawan!');
        }

        Division::whereIn('id', $ids)->delete();

        return redirect()->route('division.index')->with('success', 'Data divisi berhasil dihapus!');
    }
}
