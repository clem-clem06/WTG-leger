(function() {

    if (window.hasShowJsInitialized) return;
    window.hasShowJsInitialized = true;

    function initOfferForm() {

        // On récupère tous nos éléments
        const ccInput = document.getElementById('checkout_cardNumber');
        const expInput = document.getElementById('checkout_expDate');
        const cvvInput = document.getElementById('checkout_cvv');
        const submitBtn = document.getElementById('submit-btn');
        const hiddenInput = document.getElementById('hidden-selected-card');
        const radios = document.querySelectorAll('.card-selector');
        const paymentForm = document.getElementById('payment-form');

        function checkFormValidity() {
            if (!submitBtn) return;

            // 1. Si une ancienne carte est sélectionnée ok
            if (hiddenInput && hiddenInput.value !== '') {
                submitBtn.disabled = false;
                return;
            }

            // 2. vérifie que la nouvelle carte est saisie EN ENTIER
            let isCardValid = ccInput && ccInput.value.length === 19; // 16 chiffres + 3 espaces
            let isExpValid = expInput && expInput.value.length === 5; // MM/AA
            let isCvvValid = cvvInput && cvvInput.value.length === 3; // 3 chiffres

            // Si les 3 sont vrais, on active
            submitBtn.disabled = !(isCardValid && isExpValid && isCvvValid);
        }

        checkFormValidity();

        // change de carte sauvegardée
        if (radios.length > 0) {
            radios.forEach(radio => {
                radio.addEventListener('change', checkFormValidity);
            });
        }

        // 1. FORMATTAGE DU NUMÉRO DE CARTE
        if (ccInput) {
            ccInput.addEventListener('input', function (e) {
                let val = e.target.value.replace(/\D/g, ''); // Garde uniquement les chiffres
                let formatted = val.match(/.{1,4}/g)?.join(' ') || ''; // Espace tous les 4 chiffres
                e.target.value = formatted.substring(0, 19); // Limite à 19 caractères

                checkFormValidity();
            });
        }

        // 2. FORMATTAGE DE LA DATE
        if (expInput) {
            expInput.addEventListener('input', function (e) {
                // Force pas le /
                if (e.inputType !== 'deleteContentBackward') {
                    let val = e.target.value.replace(/\D/g, '');
                    if (val.length >= 3) {
                        val = val.substring(0, 2) + '/' + val.substring(2, 4);
                    }
                    e.target.value = val.substring(0, 5);
                }

                checkFormValidity(); // On vérifie si on peut allumer le bouton
            });
        }

        // ==============================================================
        // 3. VÉRIFICATION DU CVV (Que des chiffres)
        // ==============================================================
        if (cvvInput) {
            cvvInput.addEventListener('input', function (e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3); // Max 3 chiffres

                checkFormValidity(); // On vérifie si on peut allumer le bouton !
            });
        }

        // 4. EFFETS DE CHARGEMENT SUR LE BOUTON PENDANT LE PAIEMENT
        if (paymentForm) {
            paymentForm.addEventListener('submit', function () {
                const textSpan = document.getElementById('submit-text');
                const spinner = document.getElementById('submit-spinner');

                if (submitBtn && textSpan && spinner) {
                    // Bloque le bouton pour éviter le double-débit
                    setTimeout(() => {
                        submitBtn.disabled = true;
                        textSpan.innerHTML = '<i class="fas fa-lock me-2"></i>Traitement...';
                        spinner.classList.remove('d-none');
                    }, 10);
                }
            });
        }
    }

        initOfferForm();

        document.addEventListener('turbo:load', initOfferForm);
    })();
