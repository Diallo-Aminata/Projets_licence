   // calculer de fraix de transport
   document.addEventListener("DOMContentLoaded", function () {
    var valeurInput = document.getElementById("valeur");
    var fraisInput = document.getElementById("fraix_transaction");
    var modifierFraisCheck = document.getElementById("modifierFraisCheck");

    function calculerFrais() {
      var valeur = parseFloat(valeurInput.value);
      var frais = 0;

      if (!isNaN(valeur) && valeur >= 0) {
        frais = Math.floor(valeur / 5000) * 1000;
        if (valeur % 5000 > 0) {
          frais += 1000;
        }
      }

      fraisInput.value = frais;
    }

    valeurInput.addEventListener("input", function () {
      // Tant que l'agent n'a pas explicitement demandé à modifier les frais,
      // le calcul automatique (1000 FCFA par tranche de 5000 FCFA de valeur) fait foi.
      if (!modifierFraisCheck || !modifierFraisCheck.checked) {
        calculerFrais();
      }
    });

    // Case "Modifier manuellement les frais" : deverrouille le champ pour une saisie
    // libre (ex: tarif negocie) ; en la decochant, on revient au calcul automatique.
    if (modifierFraisCheck) {
      modifierFraisCheck.addEventListener("change", function () {
        fraisInput.readOnly = !modifierFraisCheck.checked;
        if (!modifierFraisCheck.checked) {
          calculerFrais();
        } else {
          fraisInput.focus();
        }
      });
    }
  });
 // fin du scrip 
 // scrip pour numero de telephone
 function formaterNumero(numero) {
            return numero.replace(/\D/g, '').replace(/(\d{2})(?=\d)/g, '$1 ').trim();
        }

        function verifierNumero(input, idErreur) {
            let numero = input.value.replace(/\D/g, '');
            input.value = formaterNumero(numero);

            let messageErreur = document.getElementById(idErreur);

            // Validation
            if (numero.length !== 8) {
                messageErreur.textContent = "Le numéro doit contenir exactement 8 chiffres.";
                input.style.borderColor = "red";
                return false;
            } else if (/^[1-4]/.test(numero)) {
                messageErreur.textContent = "Le numéro ne doit pas commencer par 1, 2, 3 ou 4.";
                input.style.borderColor = "red";
                return false;
            }

            // OK
            messageErreur.textContent = "";
            input.style.borderColor = "";
            return true;
        }

        // Si tu veux bloquer la soumission :
        function validerFormulaire() {
            const validExp = verifierNumero(document.getElementById('numero_exp'), 'erreur_exp');
            const validDest = verifierNumero(document.getElementById('numero_dest'), 'erreur_dest');
            return validExp && validDest;
        }

        // fin du scrip
 // Empêcher les nombres négatifs
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value < 0) this.value = 0;
            });
        });

        function showError(input, message) {
            let error = input.parentElement.querySelector(".text-danger");
            if (!error) {
                error = document.createElement("small");
                error.classList.add("text-danger");
                input.parentElement.appendChild(error);
            }
            error.textContent = message;
        }

        function clearError(input) {
            let error = input.parentElement.querySelector(".text-danger");
            if (error) error.remove();
        }

        function validateStep1() {
            const expediteur = document.getElementById("expediteur");
            const numeroExp = document.getElementById("numero_exp");

            let isValid = true;

            if (!expediteur.value.trim()) {
                showError(expediteur, "Ce champ est requis");
                isValid = false;
            } else clearError(expediteur);

            if (!numeroExp.value.trim() || parseInt(numeroExp.value) < 0) {
                showError(numeroExp, "Numéro invalide");
                isValid = false;
            } else clearError(numeroExp);

            if (isValid) stepper1.next();
        }

        function validateStep2() {
            const destinataire = document.getElementById("destinataire");
            const numeroDest = document.getElementById("numero_dest");

            let isValid = true;

            if (!destinataire.value.trim()) {
                showError(destinataire, "Ce champ est requis");
                isValid = false;
            } else clearError(destinataire);

            if (!numeroDest.value.trim() || parseInt(numeroDest.value) < 0) {
                showError(numeroDest, "Numéro invalide");
                isValid = false;
            } else clearError(numeroDest);

            if (isValid) stepper1.next();
        }