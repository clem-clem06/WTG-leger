//(function() {
//    function initOfferForm() {
document.addEventListener('turbo:load', function() {
    const form = document.getElementById('offer-form');
    if (!form) return;

    const radioMensuel = document.getElementById('radio-mensuel');
    const radioAnnuel = document.getElementById('radio-annuel');
    const inputDisplay = document.getElementById('input-duree-display');
    const inputHidden = document.getElementById('input-duree-hidden');
    const addonDuree = document.getElementById('addon-duree');

    // On cible le bouton d'ajout au panier
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
        let hasError = false; // Variable pour savoir si on doit bloquer le bouton

        // On nettoie les anciennes alertes rouges
        inputDisplay.classList.remove('is-invalid');
        const oldFeedback = inputDisplay.parentNode.querySelector('.invalid-feedback');
        if (oldFeedback) oldFeedback.remove();

        if (isMensuel) {
            if (val > 9) {
                showError('Au-delà de 9 mois, passez en annuel !');
                hasError = true;
                val = 9; // valeur de secours pour le PHP
            }
            inputHidden.value = val;
        } else {
            if (val > 5) {
                showError('L\'engagement maximum est de 5 ans.');
                hasError = true;
                val = 5; // valeur de secours pour le PHP
            }
            inputHidden.value = val * 12; // On envoie toujours en mois au PHP (ex: 2 ans = 24)
        }

        // ON BLOQUE OU DÉBLOQUE LE BOUTON ICI
        submitBtn.disabled = hasError;
    }

    // Petite fonction pour générer le message d'erreur rouge proprement
    function showError(message) {
        inputDisplay.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.innerText = message;
        inputDisplay.parentNode.appendChild(feedback);
    }

    // Écouteurs d'événements
    radioMensuel.addEventListener('change', updateMode);
    radioAnnuel.addEventListener('change', updateMode);

    // Quand on tape au clavier
    inputDisplay.addEventListener('input', updateHidden);

    // Quand on clique en dehors du champ
    inputDisplay.addEventListener('blur', function() {
        if (radioMensuel.checked && parseInt(inputDisplay.value) > 9) inputDisplay.value = 9;
        if (!radioMensuel.checked && parseInt(inputDisplay.value) > 5) inputDisplay.value = 5;
        if (parseInt(inputDisplay.value) < 1 || isNaN(parseInt(inputDisplay.value))) inputDisplay.value = 1;
        updateHidden(); // On relance la fonction pour retirer l'erreur et réactiver le bouton
    });

    // Initialisation au chargement de la page
    updateMode();
//    }
//    initOfferForm();
//    document.addEventListener('turbo:load', initOfferForm);
});
