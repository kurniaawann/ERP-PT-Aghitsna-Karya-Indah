@push('scripts')
    <script>
        // ==========================================
        // PREVENT DOUBLE SUBMIT & LOADING STATE
        // ==========================================

        let isSubmitting = false;

        function handleFormSubmit(submitBtn, originalText) {
            if (isSubmitting) return false;

            isSubmitting = true;

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sedang Memproses...';
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
            }

            return true;
        }

        // ==========================================
        // CATEGORY, SUBCATEGORY, ITEM MANAGEMENT
        // ==========================================

        // Add Category
        function addCategoryBlock() {
            const container = document.getElementById('rabCategoriesContainer');
            const categoryBlock = document.createElement('div');
            categoryBlock.className = 'category-block border rounded p-3 mb-3';
            categoryBlock.innerHTML = `
                <div class="mb-3">
                    <label class="block text-text-primary mb-1 text-sm font-semibold">Kategori (Romawi)</label>
                    <div class="flex gap-2">
                        <input type="text" class="flex-1 w-full border rounded p-2 category-name"
                            placeholder="Contoh: Pekerjaan Persiapan" required>
                        <button type="button" onclick="removeCategoryBlock(this)" class="btn btn-sm btn-danger" title="Hapus kategori">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="subcategories-container space-y-3 mb-3">
                    <div class="subcategory-block border rounded p-3 bg-gray-50">
                        <div class="mb-3">
                            <label class="block text-text-primary mb-1 text-sm font-semibold">Sub-Kategori (Angka)</label>
                            <input type="text" class="w-full border rounded p-2 subcategory-name"
                                placeholder="Contoh: Pembongkaran" required>
                        </div>

                        <div class="space-y-3 mb-3">
                            <div>
                                <label class="block text-text-primary mb-1 text-sm font-semibold">Volume</label>
                                <input type="number" class="w-full border rounded p-2 volume" placeholder="0" min="0" step="0.01" required>
                            </div>
                            <div>
                                <label class="block text-text-primary mb-1 text-sm font-semibold">Satuan</label>
                                <input type="text" class="w-full border rounded p-2 unit" placeholder="m²" required>
                            </div>
                            <div>
                                <label class="block text-text-primary mb-1 text-sm font-semibold">Harga/Unit</label>
                                <input type="number" class="w-full border rounded p-2 unit-price" placeholder="0" min="0" step="0.01" required>
                            </div>
                            <div class="bg-blue-50 border border-blue-300 rounded p-3 mb-3">
                                <p class="text-sm text-blue-900"><strong>Total Harga:</strong> <span class="sub-total-price font-bold text-lg text-blue-600">Rp 0</span></p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="block text-text-primary mb-2 text-sm font-semibold">Item Pekerjaan (a, b, c...)</label>
                            <div class="items-container space-y-2">
                                <div class="item-block bg-white rounded border p-2 flex gap-2">
                                    <input type="text" class="flex-1 w-full border-0 p-1 item-description" placeholder="Masukkan item pekerjaan" required>
                                    <button type="button" onclick="removeItemBlock(this)" class="btn btn-sm btn-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" onclick="addItemBlock(this)" class="btn btn-sm btn-outline-secondary w-full mt-2">
                                <i class="fa-solid fa-plus"></i> Tambah Item
                            </button>
                        </div>

                        <button type="button" onclick="removeSubcategoryBlock(this)" class="btn btn-sm btn-outline-danger w-full">
                            <i class="fa-solid fa-trash"></i> Hapus Sub-Kategori
                        </button>
                    </div>
                </div>

                <button type="button" onclick="addSubcategoryBlock(this)" class="btn btn-sm btn-outline-secondary w-full">
                    <i class="fa-solid fa-plus"></i> Tambah Sub-Kategori
                </button>
            `;

            container.appendChild(categoryBlock);
            attachPriceListeners();
        }

        // Add Subcategory
        function addSubcategoryBlock(button) {
            const categoryBlock = button.closest('.category-block');
            const container = categoryBlock.querySelector('.subcategories-container');

            const subcategoryBlock = document.createElement('div');
            subcategoryBlock.className = 'subcategory-block border rounded p-3 bg-gray-50';
            subcategoryBlock.innerHTML = `
                <div class="mb-3">
                    <label class="block text-text-primary mb-1 text-sm font-semibold">Sub-Kategori (Angka)</label>
                    <input type="text" class="w-full border rounded p-2 subcategory-name"
                        placeholder="Contoh: Pembongkaran" required>
                </div>

                <div class="space-y-3 mb-3">
                    <div>
                        <label class="block text-text-primary mb-1 text-sm font-semibold">Volume</label>
                        <input type="number" class="w-full border rounded p-2 volume" placeholder="0" min="0" step="0.01" required>
                    </div>
                    <div>
                        <label class="block text-text-primary mb-1 text-sm font-semibold">Satuan</label>
                        <input type="text" class="w-full border rounded p-2 unit" placeholder="m²" required>
                    </div>
                    <div>
                        <label class="block text-text-primary mb-1 text-sm font-semibold">Harga/Unit</label>
                        <input type="number" class="w-full border rounded p-2 unit-price" placeholder="0" min="0" step="0.01" required>
                    </div>
                    <div class="bg-blue-50 border border-blue-300 rounded p-3 mb-3">
                        <p class="text-sm text-blue-900"><strong>Total Harga:</strong> <span class="sub-total-price font-bold text-lg text-blue-600">Rp 0</span></p>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-text-primary mb-2 text-sm font-semibold">Item Pekerjaan (a, b, c...)</label>
                    <div class="items-container space-y-2">
                        <div class="item-block bg-white rounded border p-2 flex gap-2">
                            <input type="text" class="flex-1 w-full border-0 p-1 item-description" placeholder="Masukkan item pekerjaan" required>
                            <button type="button" onclick="removeItemBlock(this)" class="btn btn-sm btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" onclick="addItemBlock(this)" class="btn btn-sm btn-outline-secondary w-full mt-2">
                        <i class="fa-solid fa-plus"></i> Tambah Item
                    </button>
                </div>

                <button type="button" onclick="removeSubcategoryBlock(this)" class="btn btn-sm btn-outline-danger w-full">
                    <i class="fa-solid fa-trash"></i> Hapus Sub-Kategori
                </button>
            `;

            container.appendChild(subcategoryBlock);
            attachPriceListeners();
        }

        // Add Item
        function addItemBlock(button) {
            const subcategoryBlock = button.closest('.subcategory-block');
            const container = subcategoryBlock.querySelector('.items-container');

            const itemBlock = document.createElement('div');
            itemBlock.className = 'item-block bg-white rounded border p-2 flex gap-2';
            itemBlock.innerHTML = `
                <input type="text" class="flex-1 w-full border-0 p-1 item-description" placeholder="Masukkan item pekerjaan" required>
                <button type="button" onclick="removeItemBlock(this)" class="btn btn-sm btn-danger">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;

            container.appendChild(itemBlock);
        }

        // Remove Category
        function removeCategoryBlock(button) {
            button.closest('.category-block').remove();
        }

        // Remove Subcategory
        function removeSubcategoryBlock(button) {
            button.closest('.subcategory-block').remove();
        }

        // Remove Item
        function removeItemBlock(button) {
            button.closest('.item-block').remove();
        }

        // ==========================================
        // PRICE CALCULATOR
        // ==========================================

        function attachPriceListeners() {
            const volumeInputs = document.querySelectorAll('.volume');
            const priceInputs = document.querySelectorAll('.unit-price');

            volumeInputs.forEach(input => {
                input.addEventListener('input', updatePrices);
            });

            priceInputs.forEach(input => {
                input.addEventListener('input', updatePrices);
            });
        }

        function updatePrices() {
            let grandTotal = 0;
            const subcategoryBlocks = document.querySelectorAll('.subcategory-block');

            subcategoryBlocks.forEach(block => {
                const volume = parseFloat(block.querySelector('.volume').value) || 0;
                const unitPrice = parseFloat(block.querySelector('.unit-price').value) || 0;
                const totalPrice = volume * unitPrice;
                const priceDisplay = block.querySelector('.sub-total-price');

                if (priceDisplay) {
                    priceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(totalPrice);
                }

                grandTotal += totalPrice;
            });

            // Update grand total if element exists
            const grandTotalElement = document.getElementById('grandTotalPrice');
            if (grandTotalElement) {
                grandTotalElement.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(grandTotal);
            }
        }

        // Initialize price listeners on document ready
        document.addEventListener('DOMContentLoaded', function() {
            attachPriceListeners();
            updatePrices();
        });

        // ==========================================
        // PREPARE DATA FOR SUBMISSION
        // ==========================================

        window.prepareRABSubmit = function() {
            const categories = [];

            document.querySelectorAll('#rabCategoriesContainer .category-block').forEach(function(categoryEl) {
                const categoryData = {
                    category_name: categoryEl.querySelector('.category-name').value,
                    subcategories: []
                };

                categoryEl.querySelectorAll('.subcategory-block').forEach(function(subEl) {
                    const volume = parseFloat(subEl.querySelector('.volume').value) || 0;
                    const unitPrice = parseFloat(subEl.querySelector('.unit-price').value) || 0;
                    const subHarga = volume * unitPrice;

                    const subcategoryData = {
                        subcategory_name: subEl.querySelector('.subcategory-name').value,
                        volume: volume,
                        unit: subEl.querySelector('.unit').value,
                        unit_price: unitPrice,
                        sub_harga: subHarga,
                        items: []
                    };

                    subEl.querySelectorAll('.item-block').forEach(function(itemEl) {
                        const itemData = {
                            item_description: itemEl.querySelector('.item-description')
                                .value
                        };
                        subcategoryData.items.push(itemData);
                    });

                    categoryData.subcategories.push(subcategoryData);
                });

                categories.push(categoryData);
            });

            document.getElementById('rabDataInput').value = JSON.stringify(categories);
            return true;
        };

        // ==========================================
        // FORM SUBMISSION HANDLER
        // ==========================================

        const addRABForm = document.getElementById('addRABForm');
        if (addRABForm) {
            addRABForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('[type="submit"]');
                if (submitBtn) {
                    handleFormSubmit(submitBtn);
                }
                prepareRABSubmit();
            });
        }

        const editForms = document.querySelectorAll('form[id^="editRABForm"]');
        editForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('[type="submit"]');
                if (submitBtn) {
                    handleFormSubmit(submitBtn);
                }
                prepareRABSubmit();
            });
        });
    </script>
@endpush
