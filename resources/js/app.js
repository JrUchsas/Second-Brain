import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global Dark Mode Toggle Handler
window.toggleDarkMode = function () {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
};

window.isDarkMode = function () {
    return document.documentElement.classList.contains('dark');
};

Alpine.start();
