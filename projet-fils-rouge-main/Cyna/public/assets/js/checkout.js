/**
 * Tunnel de paiement Stripe (côté client).
 *
 * La carte est saisie dans le Payment Element (iframe Stripe) puis confirmée
 * directement auprès de Stripe avec la clé publique. Le numéro de carte ne
 * transite jamais par le serveur Cyna : seul le PaymentIntent (déjà confirmé)
 * est ensuite envoyé au serveur, qui le revérifie avant d'enregistrer la
 * commande.
 */
(function () {
    'use strict';

    var form = document.getElementById('checkout-form');
    if (!form || typeof Stripe === 'undefined') {
        return;
    }

    var publishableKey = form.dataset.stripeKey;
    var clientSecret = form.dataset.clientSecret;
    var returnUrl = form.dataset.returnUrl;
    if (!publishableKey || !clientSecret) {
        return;
    }

    var stripe = Stripe(publishableKey);
    var elements = stripe.elements({ clientSecret: clientSecret });
    var paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    var submitButton = document.getElementById('checkout-submit');
    var errorBox = document.getElementById('payment-errors');
    var submitting = false;

    function showError(message) {
        if (errorBox) {
            errorBox.textContent = message || '';
        }
    }

    function setBusy(busy) {
        submitting = busy;
        if (submitButton) {
            submitButton.disabled = busy;
        }
    }

    form.addEventListener('submit', function (event) {
        // On confirme d'abord la carte auprès de Stripe avant de laisser la
        // commande partir vers le serveur.
        event.preventDefault();
        if (submitting) {
            return;
        }
        setBusy(true);
        showError('');

        stripe.confirmPayment({
            elements: elements,
            confirmParams: { return_url: returnUrl },
            redirect: 'if_required'
        }).then(function (result) {
            if (result.error) {
                showError(result.error.message || 'Le paiement a échoué.');
                setBusy(false);
                return;
            }

            if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                // Paiement réglé : on soumet réellement le formulaire pour
                // enregistrer la commande côté serveur.
                form.submit();
            } else {
                showError('Le paiement n’a pas pu être confirmé.');
                setBusy(false);
            }
        }).catch(function () {
            showError('Une erreur est survenue lors du paiement.');
            setBusy(false);
        });
    });
})();
