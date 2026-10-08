<?php

namespace App\View\Components;

use App\Models\Sdm\Executive;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Modal "Penandatangan Laporan" — wajib dipilih sebelum cetak PDF/Excel
 * laporan yang memakai satu penandatangan (Laporan Pengeluaran / Kas Kantor).
 *
 * Dirender sekali per halaman oleh <x-buttons.print-dropdown :requireSigner="true">
 * (lewat @push('modals')); interaksi di resources/js/shared/print-signer.js.
 */
class PrintSignerModal extends Component
{
    /** @var Collection<int, Executive> Data Penandatangan milik user login */
    public Collection $executives;

    public function __construct()
    {
        $this->executives = Executive::where('created_by', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'position']);
    }

    public function render(): View|Closure|string
    {
        return view('components.print-signer-modal');
    }
}
