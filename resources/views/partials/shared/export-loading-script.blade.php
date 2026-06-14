<script>
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('a[data-loading]');
        if (!btn || btn.classList.contains('loading')) return;
        e.preventDefault();

        var url = btn.href;
        var icon = btn.querySelector('i');
        var label = btn.querySelector('span');

        // Store original state on the element itself (per-instance, no global state)
        btn.dataset.origIcon = icon ? icon.className : '';
        btn.dataset.origText = label ? label.textContent : '';

        // Enter loading state
        btn.classList.add('loading', 'pointer-events-none', 'opacity-70');
        if (icon) icon.className = 'fa-solid fa-spinner fa-spin w-3 h-3';
        if (label) label.textContent = 'Mendownload...';

        function reset() {
            if (!btn.classList.contains('loading')) return;
            if (icon) icon.className = btn.dataset.origIcon || '';
            if (label) label.textContent = btn.dataset.origText || '';
            btn.classList.remove('loading', 'pointer-events-none', 'opacity-70');

            clearTimeout(timer);
            document.removeEventListener('visibilitychange', onVisible);
        }

        // Strategy 1: Reset when user returns to this tab after download dialog
        function onVisible() {
            if (!document.hidden) {
                reset();
            }
        }
        document.addEventListener('visibilitychange', onVisible);

        // Strategy 2: Fallback timeout — reset after 3 seconds regardless
        var timer = setTimeout(reset, 3000);

        // Strategy 3: Iframe-based download (triggers reset on server response)
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.onload = function () {
            reset();
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        };
        iframe.src = url;
        document.body.appendChild(iframe);
    });
</script>
