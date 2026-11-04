/**
 * PhosTrack — carte interactive (Leaflet).
 * Dépend de : Leaflet, proj4 et de window.PHOSTRACK = { urlDonnees, urlLeve } (défini dans la vue).
 */
(function () {
    // UTM 28N (EPSG:32628) : pour afficher les coordonnées en mètres, comme dans la maquette
    proj4.defs('EPSG:32628', '+proj=utm +zone=28 +datum=WGS84 +units=m +no_defs');

    // URL fournies par la vue Blade (un fichier .js ne peut pas appeler route())
    var URL_DONNEES = window.PHOSTRACK.urlDonnees;
    var URL_LEVE = window.PHOSTRACK.urlLeve;

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

    var carte = L.map('carte').setView([15.07, -16.82], 11);

    // Fonds de carte
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

    // Coordonnées UTM sous le curseur
    var zoneCoords = document.getElementById('carte-coords');
    carte.on('mousemove', function (e) {
        var xy = proj4('EPSG:4326', 'EPSG:32628', [e.latlng.lng, e.latlng.lat]);
        zoneCoords.textContent = 'X : ' + Math.round(xy[0]).toLocaleString('fr-FR')
            + '   Y : ' + Math.round(xy[1]).toLocaleString('fr-FR') + '   (UTM 28N)';
    });

    // Utilitaire : une case à cocher dans l'arbre des couches
    function ajouterCase(conteneur, libelle, pastille, auChangement) {
        var div = document.createElement('div');
        div.className = 'form-check';

        var input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'form-check-input';
        input.checked = true;
        input.addEventListener('change', function () { auChangement(input.checked); });

        var label = document.createElement('label');
        label.className = 'form-check-label';
        label.appendChild(pastille);
        label.appendChild(document.createTextNode(' ' + libelle));

        div.appendChild(input);
        div.appendChild(label);
        conteneur.appendChild(div);

        return input;
    }

    function pastille(couleur, carre) {
        var s = document.createElement('span');
        s.className = carre ? 'pastille-zone' : 'pastille-couleur';
        s.style.background = couleur;
        return s;
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

    // Panneau de détail : n'apparaît qu'au clic sur un stock
    var page = document.querySelector('.carte-page');
    var selection = null;

    function nombre(valeur) {
        return Number(valeur).toLocaleString('fr-FR', { maximumFractionDigits: 1 });
    }

    // La grille change de largeur : Leaflet doit recalculer la taille de la carte
    function redimensionner(apres) {
        setTimeout(function () {
            carte.invalidateSize();
            if (apres) { apres(); }
        }, 0);
    }

    function afficherDetail(p) {
        document.getElementById('d-nom').textContent = p.nom;
        document.getElementById('d-type').textContent = p.type;
        document.getElementById('d-date').textContent = p.date_leve;
        document.getElementById('d-nb').textContent = p.nb_leves;
        document.getElementById('d-volume').textContent = nombre(p.volume) + ' m³';
        document.getElementById('d-lien').href = URL_LEVE.replace('__ID__', p.leve_id);

        page.classList.add('avec-detail');
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

    // Chargement des données
    function afficher(donnees) {
        var tous = L.featureGroup();

        // --- Zones (ajoutées d'abord : les stocks passent par-dessus) ---
        var arbreZones = document.getElementById('arbre-zones');
        var couchesZones = {};

        Object.keys(ZONES).forEach(function (statut) {
            var def = ZONES[statut];
            couchesZones[def.cle] = L.layerGroup().addTo(carte);
        });

        donnees.zones.features.forEach(function (f) {
            var def = ZONES[f.properties.statut];
            if (!def) { return; }

            var couche = L.geoJSON(f, { style: def.style });
            var info = f.properties.nom + ' — ' + def.libelle;
            if (f.properties.periode) { info += ' (' + f.properties.periode + ')'; }
            if (f.properties.surface_ha) { info += ' · ' + nombre(f.properties.surface_ha) + ' ha'; }
            couche.bindTooltip(info, { sticky: true });
            couche.addTo(couchesZones[def.cle]);
            tous.addLayer(couche);
        });

        Object.keys(ZONES).forEach(function (statut) {
            var def = ZONES[statut];
            ajouterCase(arbreZones, def.libelle, pastille(def.couleur, true), function (visible) {
                basculer(couchesZones[def.cle], visible);
            });
        });
        lierParent('c-zones', 'arbre-zones');

        // --- Stocks ---
        var arbreStocks = document.getElementById('arbre-stocks');
        var couchesStocks = {};

        donnees.stocks.features.forEach(function (f) {
            var p = f.properties;
            var couleur = COULEURS_MATERIAUX[p.type] || COULEUR_PAR_DEFAUT;

            if (!couchesStocks[p.type]) {
                couchesStocks[p.type] = L.layerGroup().addTo(carte);
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

            tous.addLayer(polygone);
        });

        var types = Object.keys(couchesStocks).sort();

        if (types.length === 0) {
            var vide = document.createElement('div');
            vide.className = 'text-muted';
            vide.textContent = 'Aucun stock validé à afficher.';
            arbreStocks.appendChild(vide);
        }

        types.forEach(function (type) {
            var couleur = COULEURS_MATERIAUX[type] || COULEUR_PAR_DEFAUT;
            ajouterCase(arbreStocks, type, pastille(couleur, false), function (visible) {
                basculer(couchesStocks[type], visible);
            });
        });
        lierParent('c-stocks', 'arbre-stocks');

        // Cadrage initial sur l'ensemble des données
        if (tous.getLayers().length > 0) {
            carte.fitBounds(tous.getBounds().pad(0.1));
        }
    }

    fetch(URL_DONNEES, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (reponse) {
            if (!reponse.ok) { throw new Error('HTTP ' + reponse.status); }
            return reponse.json();
        })
        .then(afficher)
        .catch(function () {
            document.getElementById('carte-erreur').classList.remove('d-none');
        });
})();