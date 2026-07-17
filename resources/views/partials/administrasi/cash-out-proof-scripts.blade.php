{{-- ============================================================
     JAVASCRIPT: MODUL BUKTI KAS KELUAR
     Inisialisasi dan event handler untuk halaman bukti kas keluar.

     Fitur:
     - Fungsi utilitas mata uang (dari shared script)
     - Fungsi hapus massal (dari shared script)
     - Fungsi cetak terpilih (dari shared script)
     - Select All checkbox dan sinkronisasi checkbox individual
     - Pengelolaan status tombol aksi (hapus, cetak)
     - Format input jumlah dengan format Rupiah
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
        if (count > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
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
         FORMAT INPUT MATA UANG
         Format input jumlah dengan locale Indonesia
         saat halaman dimuat dan saat pengguna mengetik.
         ========================================== --}}

    document.querySelectorAll('.cash-out-amount-input').forEach(input => {
        {{-- Format nilai awal jika sudah ada --}}
        if (input.value) {
            formatCurrencyInput(input);
        }

        {{-- Format nilai saat pengguna mengetik --}}
        input.addEventListener('input', function() {
            formatCurrencyInput(this);
        });
    });

    {{-- ==========================================
         CETAK TERPILIH
         Fungsi wrapper untuk sharedPrintSelected dengan
         route export PDF yang sesuai.
         ========================================== --}}

    function printSelected(btn) {
        return sharedPrintSelected('{{ route('cash-out-proof.export.pdf.selected') }}', btn);
    }

    {{-- ==========================================
         PENCEGAHAN DOUBLE SUBMIT
         Mencegah pengiriman form ganda pada form Tambah
         dan Edit bukti kas keluar.
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
