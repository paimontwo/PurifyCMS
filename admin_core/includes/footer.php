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

            // --- Sidebar Dropdown Hub (v5.5.0) ---
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
        });
    </script>
</body>
</html>
