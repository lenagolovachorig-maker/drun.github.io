// theme.js - Единая система тем для всех страниц

// Проверка и применение темы при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    applyTheme();
});

// Применение темы
function applyTheme() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-theme');
        updateThemeIcon(true);
    } else {
        document.body.classList.remove('dark-theme');
        updateThemeIcon(false);
    }
}

// Обновление иконки темы
function updateThemeIcon(isDark) {
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.textContent = isDark ? '☀️' : '🌙';
    }
    
    const text = document.getElementById('theme-text');
    if (text) {
        text.textContent = isDark ? 'Светлая' : 'Тёмная';
    }
}

// Переключение темы
function toggleTheme() {
    const isDark = document.body.classList.toggle('dark-theme');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeIcon(isDark);
}

// Слушаем изменения в localStorage (для синхронизации между вкладками)
window.addEventListener('storage', function(e) {
    if (e.key === 'theme') {
        applyTheme();
    }
});