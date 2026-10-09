<?php

namespace App\View\Components;

use App\Models\Sdm\Division;
use App\Models\Sdm\Executive;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Modal "Lengkapi Tanda Tangan Nota" — muncul saat nota yang tanda tangannya
 * belum lengkap (Penerima & Hormat Kami) akan di-download (PDF per baris,
 * Export Dipilih, Export Semua, link nota di Reimbursement). Isian disimpan
 * ke nota (NotaController::sign) lalu pratinjau dibuka.
 *
 * Interaksi: resources/js/shared/nota-sign.js.
 */
class NotaSignModal extends Component
{
    /** @var Collection<int, Executive> Data Penandatangan milik user login */
    public Collection $executives;

    /** @var Collection<int, Division> Divisi milik user login */
    public Collection $divisions;

    public function __construct()
    {
        $this->executives = Executive::where('created_by', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        $this->divisions = Division::where('created_by', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render(): View|Closure|string
    {
        return view('components.nota-sign-modal');
    }
}
