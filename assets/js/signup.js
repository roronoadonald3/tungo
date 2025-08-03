document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signup-form');
    if (!form) return;

    const passwordInput = document.getElementById('password');
    const passwordStrength = document.getElementById('password-strength');
    const passwordToggle = form.querySelectorAll('.password-toggle');

    // Password visibility toggle
    passwordToggle.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const input = toggle.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            toggle.querySelector('i').classList.toggle('fa-eye');
            toggle.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Password strength checker
    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            passwordStrength.className = 'password-strength';
            switch (strength) {
                case 1:
                case 2:
                    passwordStrength.classList.add('weak');
                    passwordStrength.textContent = 'Faible';
                    break;
                case 3:
                    passwordStrength.classList.add('medium');
                    passwordStrength.textContent = 'Moyen';
                    break;
                case 4:
                case 5:
                    passwordStrength.classList.add('strong');
                    passwordStrength.textContent = 'Fort';
                    break;
                default:
                    passwordStrength.textContent = '';
                    break;
            }
        });
    }

    // Form submission with client-side validation
    form.addEventListener('submit', (e) => {
        const password = form.password.value;
        const passwordConfirm = form.password_confirm.value;
        const terms = form.terms.checked;

        if (password !== passwordConfirm) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
            return;
        }

        if (!terms) {
            e.preventDefault();
            alert('Vous devez accepter les conditions d\'utilisation.');
            return;
        }
    });
});
