/*!
* Uangmu App - Dark Mode Toggler
*/
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const toggleIcon = themeToggle.querySelector('i');
    const currentTheme = localStorage.getItem('theme');

    // Fungsi untuk menerapkan tema
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
            toggleIcon.classList.remove('fa-moon');
            toggleIcon.classList.add('fa-sun');
        } else {
            document.body.classList.remove('dark-mode');
            toggleIcon.classList.remove('fa-sun');
            toggleIcon.classList.add('fa-moon');
        }
    };

    // Terapkan tema yang tersimpan saat halaman dimuat
    if (currentTheme) {
        applyTheme(currentTheme);
    }

    // Event listener untuk tombol switcher
    themeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        let theme = 'light';
        if (!document.body.classList.contains('dark-mode')) {
            theme = 'dark';
        }
        localStorage.setItem('theme', theme);
        applyTheme(theme);
    });
});