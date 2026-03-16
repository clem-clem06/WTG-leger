//(function() {
//    function initOfferForm() {
document.addEventListener('turbo:load', function() {
    // On déclare nos Regex une seule fois tout en haut, elles servent pour les 2 formulaires
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    // =========================================================
    // 1. LOGIQUE DE LA PAGE D'INSCRIPTION (REGISTER)
    // =========================================================
    const registerEmailInput = document.querySelector('.email-field');

    // Si registerEmailInput existe, ça veut dire qu'on est sur la page d'inscription !
    if (registerEmailInput) {
        const passInput = document.querySelector('.password-field');
        const confirmInput = document.querySelector('.password-confirm-field');
        const termsCheckbox = document.querySelector('input[type="checkbox"]');
        const emailFeedback = document.getElementById('email-feedback');
        const strengthText = document.getElementById('password-strength');
        const matchText = document.getElementById('password-match');
        const btnSubmit = document.getElementById('btn-submit');

        let isEmailValid = false;
        let isPasswordStrong = false;
        let doPasswordsMatch = false;

        function checkRegisterFormValidity() {
            if (isEmailValid && isPasswordStrong && doPasswordsMatch && termsCheckbox.checked) {
                btnSubmit.removeAttribute('disabled');
            } else {
                btnSubmit.setAttribute('disabled', 'true');
            }
        }

        registerEmailInput.addEventListener('input', function() {
            if (registerEmailInput.value === '') {
                emailFeedback.innerHTML = '';
                isEmailValid = false;
            } else if (emailRegex.test(registerEmailInput.value)) {
                emailFeedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Format valide</span>';
                isEmailValid = true;
            } else {
                emailFeedback.innerHTML = '<span class="text-danger">Veuillez entrer une adresse email valide.</span>';
                isEmailValid = false;
            }
            checkRegisterFormValidity();
        });

        passInput.addEventListener('input', function() {
            if (passRegex.test(passInput.value)) {
                strengthText.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle"></i> Mot de passe fort !</span>';
                isPasswordStrong = true;
            } else {
                strengthText.innerHTML = '<span class="text-danger">Doit contenir au moins 8 caractères, 1 maj, 1 min, 1 chiffre, 1 car spécial.</span>';
                isPasswordStrong = false;
            }
            checkMatch();
        });

        confirmInput.addEventListener('input', checkMatch);

        function checkMatch() {
            if (confirmInput.value === '') {
                matchText.innerHTML = '';
                doPasswordsMatch = false;
            } else if (passInput.value === confirmInput.value) {
                matchText.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle"></i> Les mots de passe correspondent.</span>';
                doPasswordsMatch = true;
            } else {
                matchText.innerHTML = '<span class="text-danger">Les mots de passe ne correspondent pas.</span>';
                doPasswordsMatch = false;
            }
            checkRegisterFormValidity();
        }

        termsCheckbox.addEventListener('change', checkRegisterFormValidity);
    }

    // =========================================================
    // 2. LOGIQUE DE LA PAGE DE CONNEXION (LOGIN)
    // =========================================================
    const loginEmailInput = document.getElementById('username');

        // Si loginEmailInput existe, ça veut dire qu'on est sur la page de connexion !
        if (loginEmailInput) {
            const passwordInput = document.getElementById('password');
            const emailFeedback = document.getElementById('email-feedback');
            const btnSubmit = document.getElementById('btn-submit');

            function validateLoginForm() {
                const isEmailValid = emailRegex.test(loginEmailInput.value);
                const isPasswordValid = passwordInput.value.trim() !== '';

                if (loginEmailInput.value === '') {
                    emailFeedback.innerHTML = '';
                    loginEmailInput.classList.remove('is-valid', 'is-invalid');
                } else if (isEmailValid) {
                    emailFeedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Format valide</span>';
                    loginEmailInput.classList.remove('is-invalid');
                    loginEmailInput.classList.add('is-valid');
                } else {
                    emailFeedback.innerHTML = '<span class="text-danger">Veuillez entrer une adresse email valide.</span>';
                    loginEmailInput.classList.remove('is-valid');
                    loginEmailInput.classList.add('is-invalid');
                }

                if (isEmailValid && isPasswordValid) {
                    btnSubmit.removeAttribute('disabled');
                } else {
                    btnSubmit.setAttribute('disabled', 'true');
                }
            }

            loginEmailInput.addEventListener('input', validateLoginForm);
            passwordInput.addEventListener('input', validateLoginForm);

            if(loginEmailInput.value !== '') {
                validateLoginForm();
            }
        }
//    }
//initOfferForm();
//document.addEventListener('turbo:load', initOfferForm);
});
