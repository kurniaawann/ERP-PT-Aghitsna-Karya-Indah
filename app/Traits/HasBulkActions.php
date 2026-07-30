<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasBulkActions
{
    /**
     * Generic bulk delete helper for controllers.
     * Example: $this->destroySelectedBy($request, Items::class, 'selected_items', 'id_item');
     */
    protected function destroySelectedBy(Request $request, string $modelClass, string $inputKey = 'selected_items', string $idColumn = 'id', string $redirectRoute = null)
    {
        $selectedIds = $request->input($inputKey, []);

        if (empty($selectedIds)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $modelClass::whereIn($idColumn, $selectedIds)->each(function ($model) {
            $model->delete();
        });

        $message = count($selectedIds) . ' data terpilih berhasil dihapus.';

        if ($redirectRoute) {
            return redirect()->route($redirectRoute)->with('success', $message);
        }

        return redirect()->back()->with('success', $message);
    }
}
