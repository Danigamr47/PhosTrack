/**
 * PhosTrack — petits comportements communs à toutes les pages.
 * JavaScript « vanille » (sans framework), uniquement ce qui est utile.
 */
(function () {
    'use strict';

    // 1) Les messages de succès se ferment seuls après 5 secondes
    document.querySelectorAll('.alert-success').forEach(function (alerte) {
        setTimeout(function () {
            if (window.bootstrap) {
                window.bootstrap.Alert.getOrCreateInstance(alerte).close();
            } else {
                alerte.remove();
            }
        }, 3000);
    });

    // 2) Anti double-clic : un bouton portant data-chargement="Texte…" est verrouillé
    //    pendant l'envoi du formulaire (calcul du volume, validation, connexion…)
    function verrouiller(bouton) {
        bouton.dataset.libelleOrigine = bouton.innerHTML;
        bouton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>'
            + bouton.dataset.chargement;
        bouton.disabled = true;
    }

    function restaurer(bouton) {
        bouton.innerHTML = bouton.dataset.libelleOrigine;
        bouton.disabled = false;
        delete bouton.dataset.libelleOrigine;
    }

    document.addEventListener('submit', function (e) {
        var bouton = e.submitter;
        if (!bouton || bouton.dataset.chargement === undefined || e.defaultPrevented) { return; }
        // On attend la fin de l'événement : le formulaire part avec ses valeurs, puis le bouton se verrouille
        setTimeout(function () { verrouiller(bouton); }, 0);
    });

    // Retour arrière du navigateur : les boutons verrouillés redeviennent utilisables
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            document.querySelectorAll('button[data-libelle-origine]').forEach(restaurer);
        }
    });

    // 3) Afficher / masquer un mot de passe 
    document.querySelectorAll('[data-voir-mot-de-passe]').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            var champ = document.querySelector(bouton.dataset.voirMotDePasse);
            var icone = bouton.querySelector('i');
            if (!champ) { return; }

            var visible = champ.type === 'text';
            champ.type = visible ? 'password' : 'text';
            if (icone) { icone.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash'; }
        });
    });
})();