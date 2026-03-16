(function() {

    if (window.hasShowJsInitialized) return;
    window.hasShowJsInitialized = true;

    function initOfferForm() {
        const form = document.getElementById('offer-form');
        if (!form) return;

        const radioMensuel = document.getElementById('radio-mensuel');
        const radioAnnuel = document.getElementById('radio-annuel');
        const inputDisplay = document.getElementById('input-duree-display');
        const inputHidden = document.getElementById('input-duree-hidden');
        const addonDuree = document.getElementById('addon-duree');
        const btnQtyMinus = document.getElementById('btn-qty-minus');
        const btnQtyPlus = document.getElementById('btn-qty-plus');
        const inputQty = document.getElementById('input-quantite');

        const submitBtn = form.querySelector('button[type="submit"]');

        if (!radioMensuel || !radioAnnuel || !inputDisplay || !submitBtn) return;

        function updateMode(e) {
            if (e && e.type === 'change') {
                inputDisplay.value = 1;
            }

            if (radioMensuel.checked) {
                addonDuree.innerText = 'mois';
            } else {
                addonDuree.innerText = 'an(s)';
            }
            updateHidden();
        }

        function updateHidden() {
            let val = parseInt(inputDisplay.value) || 1;
            const isMensuel = radioMensuel.checked;
            let hasError = false;

            inputDisplay.classList.remove('is-invalid');
            const oldFeedback = inputDisplay.parentNode.querySelector('.invalid-feedback');
            if (oldFeedback) oldFeedback.remove();

            if (isMensuel) {
                if (val > 9) {
                    showError('Au-delà de 9 mois, passez en annuel !');
                    hasError = true;
                    val = 9;
                }
                inputHidden.value = val;
            } else {
                if (val > 5) {
                    showError('L\'engagement maximum est de 5 ans.');
                    hasError = true;
                    val = 5;
                }
                inputHidden.value = val * 12;
            }

            submitBtn.disabled = hasError;
        }

        function showError(message) {
            inputDisplay.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.innerText = message;
            inputDisplay.parentNode.appendChild(feedback);
        }

        if (btnQtyMinus && btnQtyPlus && inputQty) {
            btnQtyMinus.addEventListener('click', function() {
                inputQty.stepDown();
            });

            btnQtyPlus.addEventListener('click', function() {
                inputQty.stepUp();
            });
        }

        radioMensuel.addEventListener('change', updateMode);
        radioAnnuel.addEventListener('change', updateMode);
        inputDisplay.addEventListener('input', updateHidden);

        inputDisplay.addEventListener('blur', function() {
            if (radioMensuel.checked && parseInt(inputDisplay.value) > 9) inputDisplay.value = 9;
            if (!radioMensuel.checked && parseInt(inputDisplay.value) > 5) inputDisplay.value = 5;
            if (parseInt(inputDisplay.value) < 1 || isNaN(parseInt(inputDisplay.value))) inputDisplay.value = 1;
            updateHidden();
        });

        updateMode();
    }

    initOfferForm();

    document.addEventListener('turbo:load', initOfferForm);
})();
