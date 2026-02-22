<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Administrasi\ProjectQuotationGroup;
use App\Models\Administrasi\ProjectQuotationItem;
use App\Models\Finance\PaymentAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectQuotationController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $quotations = ProjectQuotation::with(['groups.items'])
            ->when($search, function ($query, $search) {
                return $query->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->orderBy('sequence_number', 'desc')
            ->paginate(10);

        $paymentAccounts = PaymentAccount::active()->get();

        return view('pages.administrasi.project-quotation', compact('quotations', 'paymentAccounts', 'search'));
    }

    // ─── Get Next Number (AJAX) ───────────────────────────────────────────────

    public function getNextQuotationNumber()
    {
        return response()->json([
            'quotation_number' => ProjectQuotation::generateQuotationNumber(),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        // Validate required fields
        $request->validate([
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'groups_json' => 'required|string',
        ]);

        // Validate at least 1 payment account
        if (empty($request->input('selected_payment_accounts', []))) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih.')->withInput();
        }

        // Parse groups JSON
        $groups = json_decode($request->input('groups_json'), true);
        if (!$groups || !is_array($groups) || count($groups) === 0) {
            return back()->with('error', 'Data kelompok item tidak valid.')->withInput();
        }

        // Auto-generate quotation number
        $seqNumber = ProjectQuotation::getNextSequenceNumber();
        $year = date('y');
        $quotationNumber = "{$seqNumber}/{$seqNumber}/PT.AKI/{$year}";

        // Calculate grand total
        $totalAmount = 0;
        foreach ($groups as $group) {
            $subtotal = 0;
            foreach ($group['items'] as $item) {
                $subtotal += (int) ($item['total_price'] ?? 0);
            }
            $totalAmount += $subtotal;
        }

        DB::transaction(function () use ($request, $quotationNumber, $seqNumber, $totalAmount, $groups) {
            // Create header
            $quotation = ProjectQuotation::create([
                'quotation_number' => $quotationNumber,
                'sequence_number' => $seqNumber,
                'date' => $request->input('date'),
                'subject' => $request->input('subject', 'Penawaran Harga'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $request->input('selected_payment_accounts', []),
                'signed_by' => $request->input('signed_by'),
                'division' => $request->input('division'),
            ]);

            // Create groups + items
            foreach ($groups as $groupIndex => $groupData) {
                $subtotal = 0;
                foreach ($groupData['items'] as $item) {
                    $subtotal += (int) ($item['total_price'] ?? 0);
                }

                $group = ProjectQuotationGroup::create([
                    'quotation_number' => $quotationNumber,
                    'order_number' => $groupIndex + 1,
                    'name' => $groupData['name'],
                    'subtotal' => $subtotal,
                ]);

                foreach ($groupData['items'] as $itemIndex => $itemData) {
                    ProjectQuotationItem::create([
                        'group_id' => $group->id,
                        'order_number' => $itemIndex + 1,
                        'description' => $itemData['description'],
                        'volume' => $itemData['volume'] ?? null,
                        'unit' => $itemData['unit'] ?? null,
                        'unit_price' => (int) ($itemData['unit_price'] ?? 0),
                        'total_price' => (int) ($itemData['total_price'] ?? 0),
                    ]);
                }
            }
        });

        return redirect()->route('project-quotation.index')
            ->with('success', "Penawaran {$quotationNumber} berhasil ditambahkan!");
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, string $quotationNumber)
    {
        $quotation = ProjectQuotation::findOrFail($quotationNumber);

        $request->validate([
            'recipient' => 'required|string|max:255',
            'date' => 'required|date',
            'groups_json' => 'required|string',
        ]);

        if (empty($request->input('selected_payment_accounts', []))) {
            return back()->with('error', 'Minimal 1 rekening pembayaran harus dipilih.')->withInput();
        }

        $groups = json_decode($request->input('groups_json'), true);
        if (!$groups || !is_array($groups) || count($groups) === 0) {
            return back()->with('error', 'Data kelompok item tidak valid.')->withInput();
        }

        // Calculate grand total
        $totalAmount = 0;
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $totalAmount += (int) ($item['total_price'] ?? 0);
            }
        }

        DB::transaction(function () use ($request, $quotation, $totalAmount, $groups) {
            // Delete old groups (cascade deletes items)
            $quotation->groups()->each(function ($group) {
                $group->items()->delete();
            });
            $quotation->groups()->delete();

            // Update header
            $quotation->update([
                'date' => $request->input('date'),
                'subject' => $request->input('subject', 'Penawaran Harga'),
                'recipient' => $request->input('recipient'),
                'recipient_address' => $request->input('recipient_address', 'Ditempat'),
                'total_amount' => $totalAmount,
                'amount_in_words' => ucwords(terbilang($totalAmount)) . ' rupiah',
                'selected_payment_accounts' => $request->input('selected_payment_accounts', []),
                'signed_by' => $request->input('signed_by'),
                'division' => $request->input('division'),
            ]);

            // Re-create groups + items
            foreach ($groups as $groupIndex => $groupData) {
                $subtotal = 0;
                foreach ($groupData['items'] as $item) {
                    $subtotal += (int) ($item['total_price'] ?? 0);
                }

                $group = ProjectQuotationGroup::create([
                    'quotation_number' => $quotation->quotation_number,
                    'order_number' => $groupIndex + 1,
                    'name' => $groupData['name'],
                    'subtotal' => $subtotal,
                ]);

                foreach ($groupData['items'] as $itemIndex => $itemData) {
                    ProjectQuotationItem::create([
                        'group_id' => $group->id,
                        'order_number' => $itemIndex + 1,
                        'description' => $itemData['description'],
                        'volume' => $itemData['volume'] ?? null,
                        'unit' => $itemData['unit'] ?? null,
                        'unit_price' => (int) ($itemData['unit_price'] ?? 0),
                        'total_price' => (int) ($itemData['total_price'] ?? 0),
                    ]);
                }
            }
        });

        return redirect()->route('project-quotation.index')
            ->with('success', 'Penawaran berhasil diperbarui!');
    }

    // ─── Destroy Selected ────────────────────────────────────────────────────

    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        // Groups & items cascade deleted via DB constraint
        ProjectQuotation::whereIn('quotation_number', $ids)->each(function ($q) {
            $q->groups()->each(fn($g) => $g->items()->delete());
            $q->groups()->delete();
            $q->delete();
        });

        return redirect()->route('project-quotation.index')
            ->with('success', 'Penawaran berhasil dihapus!');
    }

    // ─── Print single PDF ────────────────────────────────────────────────────

    public function printPdf(string $quotationNumber)
    {
        $quotation = ProjectQuotation::with(['groups.items'])->findOrFail($quotationNumber);

        $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf', compact('quotation'))
            ->setPaper('a4', 'portrait');

        $safeNumber = str_replace(['/', '\\'], '-', $quotationNumber);
        return $pdf->download("penawaran-{$safeNumber}.pdf");
    }

    // ─── Export selected PDF ─────────────────────────────────────────────────

    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $quotations = ProjectQuotation::with(['groups.items'])
            ->whereIn('quotation_number', $ids)
            ->orderBy('sequence_number')
            ->get();

        $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf-bulk', compact('quotations'))
            ->setPaper('a4', 'portrait');

        $filename = count($ids) === 1
            ? 'penawaran-' . str_replace(['/', '\\'], '-', $ids[0]) . '.pdf'
            : 'penawaran-selected-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
