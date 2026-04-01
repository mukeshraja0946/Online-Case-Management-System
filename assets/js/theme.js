document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const themeInputs = document.querySelectorAll('input[name="theme"]');
    const body = document.body;

    // Check for saved theme preference
    // If it was 'system', fallback to light-theme since system is removed
    let savedTheme = localStorage.getItem('theme') || 'light-theme';
    if (savedTheme === 'system') {
        savedTheme = 'light-theme';
        localStorage.setItem('theme', 'light-theme');
    }

    // Initial Load
    applyTheme(savedTheme);

    // Event Listener for Toggle Button (Header)
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = localStorage.getItem('theme');
            // Simple toggle logic now
            let newTheme = (currentTheme === 'dark-theme') ? 'light-theme' : 'dark-theme';
            setTheme(newTheme);
        });
    }

    // Event Listeners for Radio Inputs (Settings)
    if (themeInputs.length > 0) {
        themeInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                if (e.target.checked) {
                    let newTheme = 'light-theme';
                    if (e.target.value === 'dark') newTheme = 'dark-theme';
                    // System case removed

                    setTheme(newTheme);
                }
            });
        });
    }

    function setTheme(theme) {
        localStorage.setItem('theme', theme);
        applyTheme(theme);
        updateInputs(theme);
    }

    function applyTheme(theme) {
        // Direct application, no system check
        if (theme === 'dark-theme') {
            body.classList.add('dark-theme');
            body.classList.remove('light-theme');
        } else {
            body.classList.add('light-theme');
            body.classList.remove('dark-theme');
        }

        // Update Toggle Icon
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark-theme' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
    }

    function updateInputs(theme) {
        themeInputs.forEach(input => {
            if (input.value === 'dark' && theme === 'dark-theme') input.checked = true;
            else if (input.value === 'light' && theme === 'light-theme') input.checked = true;
        });
    }
});
