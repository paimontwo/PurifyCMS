<?php defined('DIR_ACCESS') or exit('No direct script access allowed'); ?>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Theme Infrastructure ---
            const themeToggleBtn = document.getElementById('themeToggle');
            const htmlEl = document.documentElement;
            const toggleIcon = themeToggleBtn ? themeToggleBtn.querySelector('i') : null;

            function updateThemeIcon(theme) {
                if(toggleIcon) {
                    toggleIcon.className = (theme === 'dark') ? 'las la-sun' : 'las la-moon';
                }
            }

            const currentTheme = htmlEl.getAttribute('data-theme') || 'light';
            updateThemeIcon(currentTheme);

            if(themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function() {
                    let newTheme = htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    htmlEl.setAttribute('data-theme', newTheme);
                    localStorage.setItem('admin_theme', newTheme);
                    updateThemeIcon(newTheme);
                });
            }

            // Sidebar Dropdown Hub
            const dropdowns = document.querySelectorAll('.has-dropdown');
            const sidebarState = JSON.parse(localStorage.getItem('purify_sidebar_state') || '{}');

            // 1. Initial State Restoration
            dropdowns.forEach(dropdown => {
                const id = dropdown.dataset.id;
                if (sidebarState[id]) dropdown.classList.add('open');
            });

            // 2. Interactivity Orchestration
            document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
                trigger.addEventListener('click', e => {
                    const parent = trigger.closest('.has-dropdown');
                    const id = parent.dataset.id;
                    const isOpen = parent.classList.toggle('open');
                    
                    const states = JSON.parse(localStorage.getItem('purify_sidebar_state') || '{}');
                    states[id] = isOpen;
                    localStorage.setItem('purify_sidebar_state', JSON.stringify(states));
                });
            });

            // --- Mobile Navigation System ---
            const mobileBtn = document.getElementById('mobileToggle');
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if(mobileBtn && sidebar && overlay) {
                function toggleMobileSidebar() {
                    const isOpen = sidebar.classList.toggle('mobile-open');
                    overlay.classList.toggle('active', isOpen);
                }

                mobileBtn.addEventListener('click', toggleMobileSidebar);
                overlay.addEventListener('click', toggleMobileSidebar);
            }

            // --- Minimal Navigation Preloader ---
            const preloaderBar = document.getElementById('adminPreloaderBar');
            const contentCanvas = document.querySelector('.content-canvas');

            function finishPreloader() {
                if (preloaderBar) {
                    preloaderBar.classList.remove('is-loading');
                    preloaderBar.classList.add('is-done');
                    setTimeout(() => {
                        preloaderBar.classList.remove('is-done');
                    }, 220);
                }
                if (contentCanvas) {
                    contentCanvas.classList.remove('is-transitioning');
                }
            }

            function startPreloader() {
                if (preloaderBar) {
                    preloaderBar.classList.remove('is-done');
                    preloaderBar.classList.add('is-loading');
                }
                if (contentCanvas) {
                    contentCanvas.classList.add('is-transitioning');
                }
            }

            finishPreloader();
            window.addEventListener('pageshow', finishPreloader);

            document.addEventListener('click', function(e) {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const link = e.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
                if (link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
                if (link.hasAttribute('onclick')) return;
                try {
                    const targetUrl = new URL(link.href, window.location.origin);
                    if (targetUrl.origin !== window.location.origin) return;
                    if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search && targetUrl.hash) return;
                    startPreloader();
                } catch (err) {}
            });

            document.addEventListener('submit', function(e) {
                if (e.defaultPrevented) return;
                const form = e.target;
                if (form && !form.hasAttribute('data-no-preloader') && (!form.target || form.target === '_self')) {
                    startPreloader();
                }
            });
        });
    </script>
</body>
</html>
