(function () {
    const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Mobile Sidebar Drawer + Escape Key Close
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    function setSidebar(open) {
        if (!sidebar || !backdrop) return;
        sidebar.classList.toggle('open', open);
        backdrop.classList.toggle('open', open);
    }

    if (toggleBtn) toggleBtn.addEventListener('click', () => setSidebar(true));
    if (closeBtn) closeBtn.addEventListener('click', () => setSidebar(false));
    if (backdrop) backdrop.addEventListener('click', () => setSidebar(false));

    // Global Keyboard Shortcuts: Escape closes sidebar; '/' focuses report search
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (sidebar && sidebar.classList.contains('open')) {
                setSidebar(false);
            }
            return;
        }

        if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            const active = document.activeElement;
            const tag = active ? active.tagName : '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (active && active.isContentEditable)) {
                return;
            }
            const searchEl = document.getElementById('searchInput') || document.getElementById('topbarSearchMirror');
            if (searchEl) {
                e.preventDefault();
                searchEl.focus();
                searchEl.select();
            }
        }
    });

    // 2. Toast Notification Helper (Clean Inline SVG)
    window.showToast = function (title, message, type) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast ' + (type === 'error' ? 'error' : 'success');

        const iconHtml = type === 'error'
            ? '<div class="toast-icon"><svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg></div>'
            : '<div class="toast-icon"><svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg></div>';

        toast.innerHTML =
            iconHtml +
            '<div class="toast-body"><strong></strong><p></p></div>' +
            '<button type="button" class="toast-close" aria-label="Dismiss">&times;</button>';

        toast.querySelector('strong').textContent = title;
        toast.querySelector('p').textContent = message;
        toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());

        container.appendChild(toast);
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 5000);
    };

    // Trigger toast from server message if present
    const serverAlert = document.getElementById('serverAlert');
    if (serverAlert && serverAlert.dataset.message) {
        const type = serverAlert.dataset.type || 'success';
        const title = type === 'error' ? 'Action Required' : 'Sale Recorded';
        window.showToast(title, serverAlert.dataset.message, type);
    }

    // 3. Animated Number Counters (Report KPIs)
    const counters = document.querySelectorAll('[data-countup]');
    counters.forEach((el) => {
        const target = parseFloat(el.dataset.countup || '0');
        const prefix = el.dataset.prefix || '';
        const decimals = parseInt(el.dataset.decimals || '0', 10);

        if (reducedMotion) {
            el.textContent = prefix + target.toLocaleString('en-IN', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
            return;
        }

        const duration = 650;
        const start = performance.now();
        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            el.textContent = prefix + current.toLocaleString('en-IN', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        }
        requestAnimationFrame(tick);
    });

    // 4. Animate Report Bar Chart widths on load
    const barFills = document.querySelectorAll('.chart-bar-fill[data-width]');
    if (barFills.length) {
        requestAnimationFrame(() => {
            barFills.forEach((bar) => {
                bar.style.width = bar.dataset.width + '%';
            });
        });
    }

    // 5. Sync Topbar quick search with Report table search
    const topSearch = document.getElementById('topbarSearchMirror');
    const reportSearch = document.getElementById('searchInput');
    if (topSearch && reportSearch) {
        topSearch.addEventListener('input', () => {
            reportSearch.value = topSearch.value;
            if (typeof window.filterTable === 'function') {
                window.filterTable();
            }
        });
        reportSearch.addEventListener('input', () => {
            topSearch.value = reportSearch.value;
        });
    }
})();
