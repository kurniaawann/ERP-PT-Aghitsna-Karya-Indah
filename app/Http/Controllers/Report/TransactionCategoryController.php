<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreTransactionCategoryRequest;
use App\Http\Requests\Report\UpdateTransactionCategoryRequest;
use App\Services\Report\TransactionCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk mengelola Kategori Transaksi di modul Report.
 *
 * Menangani operasi CRUD, pencarian, filter, dan bulk delete.
 */
class TransactionCategoryController extends Controller
{
    public function __construct(
        private TransactionCategoryService $service
    ) {}

    /**
     * Menampilkan halaman daftar kategori transaksi dengan pencarian dan filter.
     *
     * @param Request $request Request HTTP dengan parameter search dan type
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');

        $categories = $this->service->getPaginatedCategories($search, $type);
        $existingCodes = $this->service->getExistingCodes();
        $usedCategoryIds = $this->service->getUsedCategoryIds();

        return view('pages.report.transaction-category', compact('categories', 'existingCodes', 'usedCategoryIds'));
    }

    /**
     * Menyimpan kategori transaksi baru.
     *
     * @param StoreTransactionCategoryRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreTransactionCategoryRequest $request)
    {
        try {
            $this->service->createCategory($request->validated());
            $this->service->flushCache();

            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil ditambahkan!');
        } catch (\Exception $e) {
            Log::error('Transaction Category store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan kategori. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Mengupdate kategori transaksi yang sudah ada.
     *
     * @param UpdateTransactionCategoryRequest $request
     * @param int                               $id ID kategori yang akan diupdate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateTransactionCategoryRequest $request, $id)
    {
        try {
            $this->service->updateCategory($id, $request->validated());
            $this->service->flushCache();

            return redirect()->route('transaction-category.index')
                ->with('success', 'Kategori transaksi berhasil diupdate!');
        } catch (\Exception $e) {
            Log::error('Transaction Category update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate kategori. Silakan coba lagi.');
        }
    }

    /**
     * Mengubah status aktif/nonaktif kategori transaksi.
     *
     * @param int $id ID kategori yang akan di-toggle
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus($id)
    {
        try {
            $category = $this->service->toggleStatus($id);
            $this->service->flushCache();
            $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return redirect()->route('transaction-category.index')
                ->with('success', "Kategori berhasil {$status}!");
        } catch (\Exception $e) {
            Log::error('Transaction Category toggleStatus failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengubah status kategori. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus beberapa kategori transaksi sekaligus (bulk delete).
     *
     * Kategori yang sedang digunakan di expense reports tidak akan dihapus.
     *
     * @param Request $request Request HTTP dengan array selected_categories
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_categories', []);

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        try {
            $result = $this->service->deleteSelected($selectedIds);

            if (!empty($result['used'])) {
                return back()->with('error', 'Kategori berikut tidak dapat dihapus karena sedang digunakan: ' . implode(', ', $result['used']));
            }

            $this->service->flushCache();

            return redirect()->route('transaction-category.index')
                ->with('success', "Berhasil menghapus {$result['deleted']} kategori.");
        } catch (\Exception $e) {
            Log::error('Transaction Category destroySelected failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus kategori. Silakan coba lagi.');
        }
    }
}
