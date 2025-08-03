document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    if (!form) return;

    const passwordToggle = form.querySelector('.password-toggle');

    // Password visibility toggle
    if (passwordToggle) {
        passwordToggle.addEventListener('click', () => {
            const input = passwordToggle.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            passwordToggle.querySelector('i').classList.toggle('fa-eye');
            passwordToggle.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
});
