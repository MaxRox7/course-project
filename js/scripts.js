// js/scripts.js

function showForm(formId, clickedTab) {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    clickedTab.classList.add('active');

    const forms = document.querySelectorAll('.form-container form');
    forms.forEach(form => form.classList.remove('active'));

    const activeForm = document.getElementById(formId);
    activeForm.classList.add('active');
}

// Добавим обработку сообщений из PHP
document.addEventListener('DOMContentLoaded', () => {
    const message = document.getElementById('message');
    if (message) {
        setTimeout(() => {
            message.style.display = 'none';
        }, 5000); // Скрыть сообщение через 5 секунд
    }
});
