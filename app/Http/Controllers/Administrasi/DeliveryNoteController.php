<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\DeliveryNote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HasBulkActions;

class DeliveryNoteController extends Controller
{
    use HasBulkActions;
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data delivery notes dengan filter pencarian
        $deliveryNotes = DeliveryNote::when($search, function ($query, $search) {
            return $query->where('id_delivery_note', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%")
                ->orWhere('receiver_name', 'like', "%{$search}%")
                ->orWhere('shipper_name', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->paginate(15);

        return view('pages.administrasi.delivery-note', compact('deliveryNotes', 'search'));
    }

    public function store(Request $request)
    {
        // Validasi input dasar
        $request->validate([
            'document_number' => 'required',
            'delivery_date' => 'required|date',
            'shipper_name' => 'required',
            'shipper_address' => 'required',
            'receiver_name' => 'required',
            'receiver_address' => 'required',
        ]);

        // Auto-generate kode delivery note
        $deliveryNoteId = DeliveryNote::generateDeliveryNoteId();

        // Process items array
        $items = [];
        $totalQuantity = 0;

        if ($request->has('item_no')) {
            $itemNos = $request->input('item_no', []);
            $itemNames = $request->input('item_name', []);
            $quantities = $request->input('quantity', []);
            $units = $request->input('unit', []);
            $itemNotes = $request->input('item_notes', []);

            foreach ($itemNos as $index => $no) {
                if (!empty($no) && !empty($itemNames[$index]) && !empty($quantities[$index])) {
                    $qty = (int) $quantities[$index];
                    $totalQuantity += $qty;

                    $items[] = [
                        'no' => (int) $no,
                        'item_name' => $itemNames[$index],
                        'quantity' => $qty,
                        'unit' => $units[$index] ?? 'pcs',
                        'notes' => $itemNotes[$index] ?? '',
                    ];
                }
            }
        }

        // Create delivery note
        DeliveryNote::create([
            'id_delivery_note' => $deliveryNoteId,
            'document_number' => $request->input('document_number'),
            'delivery_date' => $request->input('delivery_date'),
            'shipper_name' => $request->input('shipper_name'),
            'shipper_address' => $request->input('shipper_address'),
            'receiver_name' => $request->input('receiver_name'),
            'receiver_address' => $request->input('receiver_address'),
            'description' => $request->input('description'),
            'items' => $items,
            'driver_name' => $request->input('driver_name'),
            'vehicle_number' => $request->input('vehicle_number'),
            'total_quantity' => $totalQuantity,
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('delivery-note.administrasi.index')->with('success', 'Surat Jalan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $deliveryNote = DeliveryNote::findOrFail($id);

        // Validasi input dasar
        $request->validate([
            'document_number' => 'required',
            'delivery_date' => 'required|date',
            'shipper_name' => 'required',
            'shipper_address' => 'required',
            'receiver_name' => 'required',
            'receiver_address' => 'required',
        ]);

        // Process items array
        $items = [];
        $totalQuantity = 0;

        if ($request->has('item_no')) {
            $itemNos = $request->input('item_no', []);
            $itemNames = $request->input('item_name', []);
            $quantities = $request->input('quantity', []);
            $units = $request->input('unit', []);
            $itemNotes = $request->input('item_notes', []);

            foreach ($itemNos as $index => $no) {
                if (!empty($no) && !empty($itemNames[$index]) && !empty($quantities[$index])) {
                    $qty = (int) $quantities[$index];
                    $totalQuantity += $qty;

                    $items[] = [
                        'no' => (int) $no,
                        'item_name' => $itemNames[$index],
                        'quantity' => $qty,
                        'unit' => $units[$index] ?? 'pcs',
                        'notes' => $itemNotes[$index] ?? '',
                    ];
                }
            }
        }

        // Update delivery note
        $deliveryNote->update([
            'document_number' => $request->input('document_number'),
            'delivery_date' => $request->input('delivery_date'),
            'shipper_name' => $request->input('shipper_name'),
            'shipper_address' => $request->input('shipper_address'),
            'receiver_name' => $request->input('receiver_name'),
            'receiver_address' => $request->input('receiver_address'),
            'description' => $request->input('description'),
            'items' => $items,
            'driver_name' => $request->input('driver_name'),
            'vehicle_number' => $request->input('vehicle_number'),
            'total_quantity' => $totalQuantity,
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('delivery-note.administrasi.index')->with('success', 'Surat Jalan berhasil diperbarui!');
    }

    public function destroySelected(Request $request)
    {
        // Ambil array id dari checkbox selection
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('delivery-note.administrasi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        return $this->destroySelectedBy($request, DeliveryNote::class, 'ids', 'id_delivery_note', 'delivery-note.administrasi.index');
    }

    /**
     * Export delivery notes to PDF
     */
    public function exportPdfAll(Request $request)
    {
        $search = $request->input('search');

        $deliveryNotes = DeliveryNote::when($search, function ($query, $search) {
            return $query->where('id_delivery_note', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%")
                ->orWhere('receiver_name', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('exports.administrasi.delivery-note-pdf', compact('deliveryNotes'));

        return $pdf->download('Surat_Jalan_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export selected delivery notes to PDF
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('delivery-note.administrasi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        $deliveryNotes = DeliveryNote::whereIn('id_delivery_note', $ids)
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('exports.administrasi.delivery-note-pdf', compact('deliveryNotes'));

        if (count($ids) == 1) {
            $safeId = str_replace(['/', '\\'], '-', $ids[0]);
            $filename = "Surat_Jalan_{$safeId}_" . date('Y-m-d') . '.pdf';
        } else {
            $filename = 'Surat_Jalan_' . date('Y-m-d') . '.pdf';
        }

        return $pdf->download($filename);
    }
}
