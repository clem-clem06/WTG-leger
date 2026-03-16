//(function() {
//    function initOfferForm() {
document.addEventListener('turbo:load', function() {
    // 1. FORMATTAGE DU NUMÉRO DE CARTE (espaces auto)
    const ccInput = document.querySelector('.cc-input');
    if (ccInput) {
        ccInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/\D/g, ''); // Garde que les chiffres
            val = val.match(/.{1,4}/g)?.join(' ') || ''; // Espace tous les 4 chiffres
            e.target.value = val;
        });
    }

    // 2. FORMATTAGE DE LA DATE (slash auto)
    const expInput = document.querySelector('.exp-input');
    if (expInput) {
        expInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length >= 3) {
                val = val.substring(0, 2) + '/' + val.substring(2, 4);
            }
            e.target.value = val;
        });
    }

    // 3. EFFETS DE CHARGEMENT
    const newForm = document.getElementById('new-payment-form');
    if(newForm) {
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.querySelector('.btn-new-pay');
            btn.disabled = true;
            document.querySelector('.new-pay-text').innerText = 'Traitement...';
            document.querySelector('.new-pay-spinner').classList.remove('d-none');
            setTimeout(() => newForm.submit(), 1500);
        });
    }

    const savedForm = document.querySelector('.saved-payment-form');
    if(savedForm) {
        savedForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.querySelector('.btn-saved-pay');
            btn.disabled = true;
            document.querySelector('.saved-pay-text').innerText = 'Traitement...';
            document.querySelector('.saved-pay-spinner').classList.remove('d-none');
            setTimeout(() => savedForm.submit(), 1500);
        });}
//    }
//    initOfferForm();
//    document.addEventListener('turbo:load', initOfferForm);
});
