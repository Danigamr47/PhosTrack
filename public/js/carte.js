/**
 * PhosTrack — carte interactive (Leaflet).
 * Dépend de : Leaflet, proj4, Chart.js et de window.PHOSTRACK (défini dans la vue) :
 *   { urlDonnees, urlLeve, urlLeves, urlHistorique, urlDraglines }
 */
(function () {
    // UTM 28N (EPSG:32628) : coordonnées en mètres, comme sur le terrain et dans la maquette
    proj4.defs('EPSG:32628', '+proj=utm +zone=28 +datum=WGS84 +units=m +no_defs');

    var URL_DONNEES = window.PHOSTRACK.urlDonnees;
    var URL_LEVE = window.PHOSTRACK.urlLeve;
    var URL_LEVES = window.PHOSTRACK.urlLeves;
    var URL_HISTORIQUE = window.PHOSTRACK.urlHistorique;
    var URL_DRAGLINES = window.PHOSTRACK.urlDraglines;

    var COULEURS_MATERIAUX = { 'Phosphate': '#C9A24B', 'Stérile': '#8A7F72', 'Charbon': '#2B2B2E' };
    var COULEUR_PAR_DEFAUT = '#2F7DB5';

    var ZONES = {
        'exploité': {
            cle: 'exploitee', libelle: 'Exploitée', couleur: '#DFE3E7',
            style: { color: '#7B8794', weight: 1.5, fillColor: '#DFE3E7', fillOpacity: 0.45 }
        },
        'en exploitation': {
            cle: 'exploitation', libelle: 'En exploitation', couleur: '#FBE8D3',
            style: { color: '#E07B1F', weight: 2, fillColor: '#FBE8D3', fillOpacity: 0.45 }
        },
        'extension': {
            cle: 'expansion', libelle: "Zone d'expansion", couleur: '#DCEEE7',
            style: { color: '#2A7B63', weight: 2, dashArray: '6 4', fillColor: '#DCEEE7', fillOpacity: 0.45 }
        }
    };

    var page = document.querySelector('.carte-page');
    var carte = L.map('carte').setView([15.07, -16.82], 11);

    /* ------------------------------------------------------------------ */
    /* Fonds de carte, échelle, coordonnées sous le curseur                 */
    /* ------------------------------------------------------------------ */
    var fonds = {
        satellite: L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            { maxZoom: 20, maxNativeZoom: 18, attribution: 'Imagerie © Esri, Maxar, Earthstar Geographics' }
        ),
        plan: L.tileLayer(
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            { maxZoom: 20, maxNativeZoom: 19, attribution: '© contributeurs OpenStreetMap' }
        )
    };
    var fondActif = fonds.satellite.addTo(carte);

    document.querySelectorAll('input[name=fond]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            carte.removeLayer(fondActif);
            fondActif = fonds[radio.value].addTo(carte);
        });
    });

    L.control.scale({ imperial: false, position: 'bottomright' }).addTo(carte);

    var zoneCoords = document.getElementById('carte-coords');
    carte.on('mousemove', function (e) {
        var xy = proj4('EPSG:4326', 'EPSG:32628', [e.latlng.lng, e.latlng.lat]);
        zoneCoords.textContent = 'X : ' + Math.round(xy[0]).toLocaleString('fr-FR')
            + '   Y : ' + Math.round(xy[1]).toLocaleString('fr-FR') + '   (UTM 28N) · clic droit : copier';
    });

    /* ------------------------------------------------------------------ */
    /* Utilitaires                                                          */
    /* ------------------------------------------------------------------ */
    function nombre(valeur) {
        return Number(valeur).toLocaleString('fr-FR', { maximumFractionDigits: 1 });
    }

    function pastille(couleur, carre) {
        var s = document.createElement('span');
        s.className = carre ? 'pastille-zone' : 'pastille-couleur';
        s.style.background = couleur;
        return s;
    }

    // Une case à cocher dans l'arbre des couches
    function ajouterCase(conteneur, libelle, pastilleEl, coche, auChangement) {
        var div = document.createElement('div');
        div.className = 'form-check';

        var input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'form-check-input';
        input.checked = coche;
        input.addEventListener('change', function () { auChangement(input.checked); });

        var label = document.createElement('label');
        label.className = 'form-check-label';
        label.appendChild(pastilleEl);
        label.appendChild(document.createTextNode(' ' + libelle));

        div.appendChild(input);
        div.appendChild(label);
        conteneur.appendChild(div);

        return input;
    }

    function basculer(couche, visible) {
        if (visible) { carte.addLayer(couche); } else { carte.removeLayer(couche); }
    }

    // Le parent coche ou décoche tous ses enfants
    function lierParent(idParent, idConteneur) {
        document.getElementById(idParent).addEventListener('change', function (e) {
            document.querySelectorAll('#' + idConteneur + ' input').forEach(function (c) {
                if (c.checked !== e.target.checked) {
                    c.checked = e.target.checked;
                    c.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    // La grille change de largeur : Leaflet doit recalculer la taille de la carte
    function redimensionner(apres) {
        setTimeout(function () {
            carte.invalidateSize();
            if (apres) { apres(); }
        }, 0);
    }

    /* ------------------------------------------------------------------ */
    /* Panneau « Détail du stock » (affiché seulement au clic sur un stock) */
    /* ------------------------------------------------------------------ */
    var selection = null;
    var leveExport = null;
    var graphique = null;
    var campagneActive = '';

    // La dernière barre (le levé affiché) est la plus foncée
    function dessinerGraphique(points) {
        if (graphique) { graphique.destroy(); graphique = null; }
        if (!window.Chart) { return; }

        var dernier = points.length - 1;

        graphique = new Chart(document.getElementById('d-graphique'), {
            type: 'bar',
            data: {
                labels: points.map(function (pt) { return pt.label; }),
                datasets: [{
                    data: points.map(function (pt) { return pt.volume; }),
                    backgroundColor: points.map(function (pt, i) { return i === dernier ? '#1B5E4B' : '#8AB7A9'; }),
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return nombre(c.parsed.y) + ' m³'; } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { callback: function (v) { return nombre(v); } } }
                }
            }
        });
    }

    function afficherVariation(variation) {
        var zone = document.getElementById('d-variation');

        if (variation === null || variation === undefined) {
            zone.textContent = 'Premier levé validé de ce stock';
            zone.className = 'small text-muted';
            return;
        }

        var positif = variation >= 0;
        var pourcent = Math.abs(variation).toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        zone.textContent = (positif ? '▲ +' : '▼ −') + pourcent + ' % par rapport au levé précédent';
        zone.className = 'small fw-semibold ' + (positif ? 'text-success' : 'text-warning');
    }

    // Liens de téléchargement : le système de coordonnées choisi s'applique aux fichiers de points
    function majLiensExport() {
        if (leveExport === null) { return; }
        var choisi = document.querySelector('input[name=srs]:checked');
        var srs = choisi ? choisi.value : 'utm';

        document.querySelectorAll('#d-exports [data-export]').forEach(function (lien) {
            var type = lien.dataset.export;
            lien.href = URL_LEVES + '/' + leveExport + '/export/' + type
                + (type === 'contour' ? '' : '?srs=' + srs);   // le contour GeoJSON est toujours en WGS84
        });
    }

    document.querySelectorAll('input[name=srs]').forEach(function (radio) {
        radio.addEventListener('change', majLiensExport);
    });

    function afficherDetail(p) {
        document.getElementById('d-nom').textContent = p.nom;
        document.getElementById('d-type').textContent = p.type;
        document.getElementById('d-zone').textContent = p.zone || "Hors zones d'exploitation";
        document.getElementById('d-nb').textContent = p.nb_leves;
        document.getElementById('d-volume').textContent = nombre(p.volume) + ' m³';
        document.getElementById('d-etiq-date').textContent = campagneActive ? 'Levé de la campagne' : 'Dernier levé';
        document.getElementById('d-etiq-volume').textContent = campagneActive ? 'Volume de la campagne' : 'Volume actuel';
        afficherVariation(p.evolution.variation);

        var lienLeve = document.getElementById('d-date');
        lienLeve.textContent = p.date_leve;
        lienLeve.href = URL_LEVE.replace('__ID__', p.leve_id);

        document.getElementById('d-historique').href = URL_HISTORIQUE + '?stock=' + p.id;

        leveExport = p.leve_id;
        majLiensExport();

        // Le panneau doit être visible avant de dessiner le graphique (calcul de sa taille)
        page.classList.add('avec-detail');
        dessinerGraphique(p.evolution.points);

        redimensionner(function () {
            carte.panInside([p.lat, p.lon], { padding: [60, 60] });
        });
    }

    function effacerDetail() {
        if (selection) { selection.setStyle({ weight: 2 }); selection = null; }
        if (page.classList.contains('avec-detail')) {
            page.classList.remove('avec-detail');
            redimensionner();
        }
    }

    function selectionner(p, polygone) {
        if (selection) { selection.setStyle({ weight: 2 }); }
        selection = polygone;
        polygone.setStyle({ weight: 4 });
        afficherDetail(p);
    }

    document.getElementById('d-fermer').addEventListener('click', effacerDetail);
    carte.on('click', effacerDetail);

    /* ------------------------------------------------------------------ */
    /* Zones (chargées une seule fois)                                      */
    /* ------------------------------------------------------------------ */
    function afficherZones(collection) {
        var groupe = L.featureGroup();
        var arbre = document.getElementById('arbre-zones');
        var couches = {};

        Object.keys(ZONES).forEach(function (statut) {
            couches[ZONES[statut].cle] = L.layerGroup().addTo(carte);
        });

        collection.features.forEach(function (f) {
            var def = ZONES[f.properties.statut];
            if (!def) { return; }

            var couche = L.geoJSON(f, { style: def.style });
            var info = f.properties.nom + ' — ' + def.libelle;
            if (f.properties.periode) { info += ' (' + f.properties.periode + ')'; }
            if (f.properties.surface_ha) { info += ' · ' + nombre(f.properties.surface_ha) + ' ha'; }
            couche.bindTooltip(info, { sticky: true });
            couche.addTo(couches[def.cle]);
            groupe.addLayer(couche);
        });

        Object.keys(ZONES).forEach(function (statut) {
            var def = ZONES[statut];
            ajouterCase(arbre, def.libelle, pastille(def.couleur, true), true, function (visible) {
                basculer(couches[def.cle], visible);
            });
        });

        return groupe;
    }

    /* ------------------------------------------------------------------ */
    /* Draglines : dernière position + trajectoire en pointillés            */
    /* ------------------------------------------------------------------ */
    function contenuPopupDragline(p) {
        var div = document.createElement('div');
        div.className = 'small';

        var titre = document.createElement('div');
        titre.className = 'fw-semibold mb-1';
        titre.textContent = 'Dragline ' + p.nom;

        var info = document.createElement('div');
        info.textContent = 'Dernière position : ' + p.date;

        var coord = document.createElement('div');
        coord.className = 'text-muted';
        coord.textContent = 'X ' + nombre(p.x) + ' · Y ' + nombre(p.y) + ' (UTM 28N)';

        var lien = document.createElement('a');
        lien.className = 'd-inline-block mt-2';
        lien.href = URL_DRAGLINES + '?dragline=' + p.id;
        lien.textContent = 'Voir le suivi';

        div.appendChild(titre);
        div.appendChild(info);
        div.appendChild(coord);
        div.appendChild(lien);

        return div;
    }

    // Étiquette affichée au survol : nom de la dragline et date de sa dernière position
    function etiquetteDragline(p) {
        var el = document.createElement('div');

        var nom = document.createElement('strong');
        nom.textContent = p.nom;

        var info = document.createElement('span');
        info.className = 'opacity-75';
        info.textContent = ' · dernière position le ' + p.date;

        el.appendChild(nom);
        el.appendChild(info);

        return el;
    }

    function afficherDraglines(collection) {
        var groupe = L.layerGroup().addTo(carte);
        var limites = L.featureGroup();

        collection.features.forEach(function (f) {
            var p = f.properties;
            var coord = f.geometry.coordinates;   // [longitude, latitude]

            if (p.trajectoire) {
                L.geoJSON({ type: 'Feature', geometry: p.trajectoire, properties: {} }, {
                    style: { color: '#E07B1F', weight: 2.5, dashArray: '6 6', opacity: 0.9 },
                    interactive: false
                }).addTo(groupe);
            }

            var repere = L.circleMarker([coord[1], coord[0]], {
                radius: 8, color: '#FFFFFF', weight: 2, fillColor: '#E07B1F', fillOpacity: 1
            });
            repere.bindTooltip(etiquetteDragline(p), { direction: 'right', offset: [10, 0], className: 'etiquette-dragline' });
            repere.bindPopup(contenuPopupDragline(p));
            repere.addTo(groupe);

            limites.addLayer(repere);
        });

        document.getElementById('c-draglines').addEventListener('change', function (e) {
            basculer(groupe, e.target.checked);
        });

        return limites;
    }

    /* ------------------------------------------------------------------ */
    /* Stocks (rechargés quand on change de campagne)                       */
    /* ------------------------------------------------------------------ */
    var couchesStocks = {};   // type de matériau -> couche
    var casesTypes = {};      // type de matériau -> case à cocher
    var typesMasques = {};    // types décochés par l'utilisateur (conservés entre deux chargements)
    var registre = {};        // nom du stock en minuscules -> { p, polygone }

    function viderStocks() {
        Object.keys(couchesStocks).forEach(function (type) { carte.removeLayer(couchesStocks[type]); });
        couchesStocks = {};
        casesTypes = {};
        registre = {};
        document.getElementById('arbre-stocks').textContent = '';
        document.getElementById('liste-stocks').textContent = '';
        effacerDetail();
    }

    function afficherStocks(collection) {
        viderStocks();

        var groupe = L.featureGroup();
        var arbre = document.getElementById('arbre-stocks');
        var liste = document.getElementById('liste-stocks');

        collection.features.forEach(function (f) {
            var p = f.properties;
            var couleur = COULEURS_MATERIAUX[p.type] || COULEUR_PAR_DEFAUT;

            if (!couchesStocks[p.type]) {
                couchesStocks[p.type] = L.layerGroup();
                if (!typesMasques[p.type]) { couchesStocks[p.type].addTo(carte); }
            }

            // Emprise du tas + repère visible même à petite échelle
            var polygone = L.geoJSON(f, { style: { color: couleur, weight: 2, fillColor: couleur, fillOpacity: 0.55 } });
            var repere = L.circleMarker([p.lat, p.lon], { radius: 9, color: '#FFFFFF', weight: 2, fillColor: couleur, fillOpacity: 1 });

            [polygone, repere].forEach(function (couche) {
                couche.bindTooltip(p.nom, { direction: 'top' });
                couche.on('click', function (e) {
                    L.DomEvent.stopPropagation(e);
                    selectionner(p, polygone);
                });
                couche.addTo(couchesStocks[p.type]);
            });

            groupe.addLayer(polygone);
            registre[p.nom.toLowerCase()] = { p: p, polygone: polygone };

            var option = document.createElement('option');
            option.value = p.nom;
            liste.appendChild(option);
        });

        var types = Object.keys(couchesStocks).sort();

        if (types.length === 0) {
            var vide = document.createElement('div');
            vide.className = 'text-muted';
            vide.textContent = 'Aucun stock validé à afficher.';
            arbre.appendChild(vide);
        }

        types.forEach(function (type) {
            var couleur = COULEURS_MATERIAUX[type] || COULEUR_PAR_DEFAUT;
            casesTypes[type] = ajouterCase(arbre, type, pastille(couleur, false), !typesMasques[type], function (visible) {
                typesMasques[type] = !visible;
                basculer(couchesStocks[type], visible);
            });
        });

        return groupe;
    }

    lierParent('c-stocks', 'arbre-stocks');
    lierParent('c-zones', 'arbre-zones');

    /* ------------------------------------------------------------------ */
    /* Recherche d'un stock                                                 */
    /* ------------------------------------------------------------------ */
    var champRecherche = document.getElementById('recherche');
    var messageRecherche = document.getElementById('recherche-msg');

    champRecherche.addEventListener('change', function () {
        var cle = champRecherche.value.trim().toLowerCase();
        messageRecherche.textContent = '';
        if (cle === '') { return; }

        var trouve = registre[cle];
        if (!trouve) {
            messageRecherche.textContent = 'Aucun stock de ce nom.';
            return;
        }

        // Si le type de matériau est masqué dans les couches, on le réaffiche
        var caseType = casesTypes[trouve.p.type];
        if (caseType && !caseType.checked) {
            caseType.checked = true;
            caseType.dispatchEvent(new Event('change'));
        }

        selectionner(trouve.p, trouve.polygone);
        setTimeout(function () { carte.flyTo([trouve.p.lat, trouve.p.lon], 18); }, 50);
    });

    /* ------------------------------------------------------------------ */
    /* Filtre « Campagne »                                                  */
    /* ------------------------------------------------------------------ */
    var selectCampagne = document.getElementById('filtre-campagne');

    function remplirCampagnes(campagnes) {
        campagnes.forEach(function (c) {
            var option = document.createElement('option');
            option.value = c.id;
            option.textContent = c.libelle;
            selectCampagne.appendChild(option);
        });
    }

    selectCampagne.addEventListener('change', function () {
        campagneActive = selectCampagne.value;
        charger(campagneActive)
            .then(function (donnees) { afficherStocks(donnees.stocks); })
            .catch(afficherErreur);
    });

    /* ------------------------------------------------------------------ */
    /* Clic droit : coordonnées du point, avec boutons « Copier »           */
    /* ------------------------------------------------------------------ */
    function copier(texte, bouton) {
        function termine() { bouton.textContent = 'Copié ✓'; }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texte).then(termine);
            return;
        }

        // Réseau interne en http (contexte non sécurisé) : méthode de secours
        var zone = document.createElement('textarea');
        zone.value = texte;
        zone.style.position = 'fixed';
        zone.style.opacity = '0';
        document.body.appendChild(zone);
        zone.select();
        try { document.execCommand('copy'); termine(); } catch (e) { /* copie impossible */ }
        document.body.removeChild(zone);
    }

    function ligneCopie(titre, affiche, aCopier) {
        var ligne = document.createElement('div');
        ligne.className = 'mb-2';

        var t = document.createElement('div');
        t.className = 'text-muted';
        t.textContent = titre;

        var contenu = document.createElement('div');
        contenu.className = 'd-flex align-items-center justify-content-between gap-2';

        var valeur = document.createElement('span');
        valeur.className = 'fw-semibold';
        valeur.textContent = affiche;

        var bouton = document.createElement('button');
        bouton.type = 'button';
        bouton.className = 'btn btn-outline-primary btn-sm py-0';
        bouton.textContent = 'Copier';
        bouton.addEventListener('click', function () { copier(aCopier, bouton); });

        contenu.appendChild(valeur);
        contenu.appendChild(bouton);
        ligne.appendChild(t);
        ligne.appendChild(contenu);

        return ligne;
    }

    carte.on('contextmenu', function (e) {
        var xy = proj4('EPSG:4326', 'EPSG:32628', [e.latlng.lng, e.latlng.lat]);
        var utm = xy[0].toFixed(2) + ';' + xy[1].toFixed(2);
        var degres = e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6);

        var contenu = document.createElement('div');
        contenu.className = 'small';
        contenu.appendChild(ligneCopie('UTM 28N (X ; Y, en mètres)', utm.replace(';', ' ; '), utm));
        contenu.appendChild(ligneCopie('WGS84 (latitude, longitude)', degres, degres));

        L.popup({ minWidth: 240 }).setLatLng(e.latlng).setContent(contenu).openOn(carte);
    });

    /* ------------------------------------------------------------------ */
    /* Chargement des données                                               */
    /* ------------------------------------------------------------------ */
    function afficherErreur() {
        document.getElementById('carte-erreur').classList.remove('d-none');
    }

    function charger(campagne) {
        var url = URL_DONNEES + (campagne ? '?campagne=' + encodeURIComponent(campagne) : '');

        return fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (reponse) {
                if (!reponse.ok) { throw new Error('HTTP ' + reponse.status); }
                return reponse.json();
            });
    }

    charger('')
        .then(function (donnees) {
            var tout = L.featureGroup();

            tout.addLayer(afficherZones(donnees.zones));
            if (donnees.draglines) { tout.addLayer(afficherDraglines(donnees.draglines)); }
            tout.addLayer(afficherStocks(donnees.stocks));
            remplirCampagnes(donnees.campagnes);

            // Cadrage initial sur l'ensemble des données
            if (tout.getLayers().length > 0 && tout.getBounds().isValid()) {
                carte.fitBounds(tout.getBounds().pad(0.1));
            }
        })
        .catch(afficherErreur);
})();