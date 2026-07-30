{{-- ============================================================
     JAVASCRIPT: MODUL KWINTANSI
     Inisialisasi dan event handler untuk halaman kwintansi.

     Fitur:
     - Fungsi mata uang (dari shared script)
     - Fungsi hapus massal (dari shared script)
     - Fungsi cetak terpilih (dari shared script)
     - Select All checkbox dan sinkronisasi checkbox individual
     - Pengelolaan status tombol aksi (hapus, cetak)
     - Pencegahan double submit pada form tambah/edit
============================================================ --}}

<script>
    {{-- ==========================================
         UTILITAS: Mata Uang, Hapus, dan Cetak
         ========================================== --}}

    @include('partials.shared.currency-utils-script')
    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')

    {{-- ==========================================
         SUBMIT DELETE FORM (global scope)
         ========================================== --}}

    window.submitDeleteForm = function () {
        var deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        var form = document.getElementById('deleteForm');
        if (form) {
            form.submit();
        }
    };

    {{-- ==========================================
         SELECT ALL CHECKBOX
         Mengelola checkbox "Pilih Semua" dan sinkronisasi
         dengan checkbox individual di setiap baris data.
         ========================================== --}}

    document.getElementById('selectAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    {{-- Sinkronisasi checkbox individual dengan checkbox "Pilih Semua" --}}
    document.querySelectorAll('input[name="ids[]"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
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
        var deleteButton = document.getElementById('delete-button');
        var printSelectedItem = document.getElementById('printSelectedItem');
        var selectedCountText = document.getElementById('selectedCountText');
        var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        var count = checkedCheckboxes.length;

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
        return sharedPrintSelected('{{ route('kwintansi.export.pdf.selected') }}', btn);
    }

    {{-- ==========================================
         PENCEGAHAN DOUBLE SUBMIT
         Mencegah pengiriman form ganda pada form Tambah
         dan Edit kwintansi.
         ========================================== --}}

    {{-- Form Tambah --}}
    var addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    {{-- Form Edit (satu form per baris data) --}}
    document.querySelectorAll('[id^="editModal-"] form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
