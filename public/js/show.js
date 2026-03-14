document.addEventListener('turbo:load', function() {
    const form = document.getElementById('offer-form');
    if (!form) return;

    const radioMensuel = document.getElementById('radio-mensuel');
    const radioAnnuel = document.getElementById('radio-annuel');
    const inputDisplay = document.getElementById('input-duree-display');
    const inputHidden = document.getElementById('input-duree-hidden');
    const addonDuree = document.getElementById('addon-duree');

    if (!radioMensuel || !radioAnnuel) return;

    function updateMode() {
        let val = parseInt(inputDisplay.value) || 1;
        const isMensuel = radioMensuel.checked;

        inputDisplay.classList.remove('is-invalid');
        const oldFeedback = inputDisplay.parentNode.querySelector('.invalid-feedback');
        if (oldFeedback) oldFeedback.remove();

        if (isMensuel) {
            addonDuree.innerText = 'moi(s)';
            if (val > 9) {
                inputDisplay.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.innerText = 'Au-delà de 9 mois, l\'offre annuelle est plus avantageuse !';
                inputDisplay.parentNode.appendChild(feedback);
                val = 9;
            }
            inputHidden.value = val;
        } else {
            addonDuree.innerText = 'an(s)';
            if (val > 5) val = 5;
            inputHidden.value = val * 12;
        }
        updateHidden();
    }

    function updateHidden() {
        let val = parseInt(inputDisplay.value) || 1;
        if (radioMensuel.checked) {
            if (val > 9) val = 9;
            inputHidden.value = val;
        } else {
            if (val > 5) val = 5;
            inputHidden.value = val * 12; // On convertit l'année en mois (ex: 2 ans = 24 mois)
        }
    }

    radioMensuel.addEventListener('change', updateMode);
    radioAnnuel.addEventListener('change', updateMode);
    inputDisplay.addEventListener('input', updateHidden);
    updateMode();
});
