@push('scripts')
    <script>
        (function() {
            const btn = document.getElementById('itemDropdownBtn');
            const menu = document.getElementById('itemDropdownMenu');
            const list = document.getElementById('itemDropdownList');
            const searchInput = document.getElementById('itemSearchInput');
            const label = document.getElementById('itemDropdownLabel');
            const hiddenInput = document.getElementById('item_id');
            const clearBtn = document.getElementById('clearItemBtn');

            if (!btn || !menu || !list) return;

            let page = 1;
            const limit = 10;
            let loading = false;
            let hasMore = true;
            let searchTimeout = null;

            function getCurrentDates() {
                const start = document.getElementById('start_date')?.value;
                const end = document.getElementById('end_date')?.value;
                return {
                    start,
                    end
                };
            }

            function autoFilterNow() {
                const {
                    start,
                    end
                } = getCurrentDates();

                const url = new URL('{{ route('stock-report.index') }}', window.location.origin);

                if (start) url.searchParams.set('start_date', start);
                if (end) url.searchParams.set('end_date', end);

                // item_id boleh kosong untuk "semua barang"
                url.searchParams.set('item_id', hiddenInput.value || '');

                // reset pagination ke page 1 (kalau ada)
                url.searchParams.delete('page');

                window.location.href = url.toString();
            }

            function setSelected(id, text) {
                hiddenInput.value = id || '';
                label.textContent = text || '- Semua Barang -';
                menu.classList.add('hidden');

                // auto submit/filter saat user memilih barang
                autoFilterNow();
            }

            function fetchItems(append = false) {
                if (loading || (!hasMore && append)) return;
                loading = true;

                const query = searchInput ? searchInput.value : '';

                const params = new URLSearchParams({
                    search: query,
                    page: String(page),
                    limit: String(limit),
                });

                if (!append) {
                    list.innerHTML = '';
                    const loadingEl = document.createElement('div');
                    loadingEl.className = 'p-2 flex items-center gap-2 text-sm text-text-secondary';
                    loadingEl.innerHTML = `
                        <span class="inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
                        <span>Loading...</span>
                    `;
                    list.appendChild(loadingEl);
                }

                fetch('{{ route('stock-report.items-dropdown') }}?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        const data = res.data || [];

                        // remove loading spinner when not appending
                        if (!append) {
                            list.innerHTML = '';
                        }

                        if (data.length === 0 && !append) {
                            const el = document.createElement('div');
                            el.className = 'p-2 text-sm text-text-secondary text-center py-4';
                            el.textContent = 'Tidak ada data barang';
                            list.appendChild(el);
                        } else {
                            data.forEach(item => {
                                const row = document.createElement('button');
                                row.type = 'button';
                                row.className =
                                    'w-full text-left px-3 py-2 hover:bg-surface-secondary text-sm text-text-primary transition-colors';
                                row.textContent = `${item.id_item} - ${item.name_item}`;
                                row.dataset.id = item.id_item;

                                row.addEventListener('click', () => {
                                    setSelected(item.id_item, row.textContent);
                                });

                                list.appendChild(row);
                            });
                        }

                        hasMore = !!res.hasMore;
                        if (hasMore) {
                            page += 1;
                        }
                    })
                    .finally(() => {
                        loading = false;
                    });
            }

            // Handle Search Input
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        page = 1;
                        hasMore = true;
                        fetchItems(false);
                    }, 300);
                });
            }

            // Toggle menu
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');

                // Always reset pagination & fetch when opening dropdown for the first visible time
                if (!menu.classList.contains('hidden')) {
                    if (searchInput) {
                        searchInput.focus();
                    }
                    
                    if (list.children.length <= 1) { // 1 because of the initial placeholder
                        page = 1;
                        hasMore = true;
                        fetchItems(false);
                    }
                }
            });

            // Clear selection
            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    setSelected('', '- Semua Barang -');
                });
            }

            // Infinite scroll
            list.addEventListener('scroll', () => {
                const nearBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 20;
                if (nearBottom) fetchItems(true);
            });

            // Close on outside click
            document.addEventListener('click', () => {
                menu.classList.add('hidden');
            });

            // Prevent menu close when clicking inside
            menu.addEventListener('click', (e) => e.stopPropagation());
        })();
    </script>
@endpush
