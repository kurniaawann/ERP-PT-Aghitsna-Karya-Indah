{{-- ============================================================
     JAVASCRIPT: MODUL TANDA TERIMA DOKUMEN
     Inisialisasi dan event handler untuk halaman tanda terima dokumen.

     Fitur:
     - Fungsi hapus massal (dari shared script)
     - Fungsi cetak terpilih (dari shared script)
     - Select All checkbox dan sinkronisasi checkbox individual
     - Pengelolaan status tombol aksi (hapus, cetak)
     - Pencegahan double submit pada form tambah/edit
============================================================ --}}

<script>
    {{-- ==========================================
         UTILITAS: Hapus dan Cetak
         ========================================== --}}

    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')

    {{-- ==========================================
         SELECT ALL CHECKBOX
         Mengelola checkbox "Pilih Semua" dan sinkronisasi
         dengan checkbox individual di setiap baris data.
         ========================================== --}}

    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    {{-- Sinkronisasi checkbox individual dengan checkbox "Pilih Semua" --}}
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    {{-- ==========================================
         STATUS TOMBOL AKSI
         Mengelola status aktif/nonaktif tombol Hapus
         dan tampilan/menampilkan tombol Cetak Terpilih
         berdasarkan jumlah data yang dipilih.
         ========================================== --}}

    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const printSelectedItem = document.getElementById('printSelectedItem');
        const selectedCountText = document.getElementById('selectedCountText');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        const count = checkedCheckboxes.length;

        {{-- Aktifkan/nonaktifkan tombol Hapus --}}
        if (deleteButton) {
            if (count > 0) {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        {{-- Tampilkan/sembunyikan tombol Cetak Terpilih --}}
        if (printSelectedItem) {
            if (count > 0) {
                printSelectedItem.classList.remove('hidden');
            } else {
                printSelectedItem.classList.add('hidden');
            }
        }

        {{-- Perbarui teks jumlah data terpilih --}}
        if (selectedCountText) {
            selectedCountText.textContent = count;
        }
    }

    {{-- Inisialisasi status tombol saat halaman dimuat --}}
    updateButtonStates();

    {{-- ==========================================
         CETAK TERPILIH
         Fungsi wrapper untuk sharedPrintSelected dengan
         route export PDF yang sesuai.
         ========================================== --}}

    function printSelected(btn) {
        return sharedPrintSelected('{{ route('document-receipt.export.pdf.selected') }}', btn);
    }

    {{-- ==========================================
         PENCEGAHAN DOUBLE SUBMIT
         Mencegah pengiriman form ganda pada form Tambah
         dan Edit tanda terima dokumen.
         ========================================== --}}

    {{-- Form Tambah --}}
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    {{-- Form Edit (satu form per baris data) --}}
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
