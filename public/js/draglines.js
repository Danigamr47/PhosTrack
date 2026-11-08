/**
 * PhosTrack — suivi des draglines : trajectoire d'une dragline sur une mini-carte.
 * Dépend de Leaflet et de window.PHOSTRACK_DRAGLINE = { nom, points: [{ id, date, x, y, lon, lat }] } (défini par la vue).
 */
/* Saisie d'une position : choix du système de coordonnées (UTM en mètres ou WGS84 en degrés).
   Les champs du système non choisi sont désactivés : ils ne sont ni envoyés ni exigés. */
(function () {
    var radios = document.querySelectorAll('input[name=systeme]');
    if (radios.length === 0) { return; }

    function appliquer() {
        var wgs84 = document.getElementById('sys-wgs84').checked;

        [['champs-utm', !wgs84], ['champs-wgs84', wgs84]].forEach(function (groupe) {
            var bloc = document.getElementById(groupe[0]);
            bloc.classList.toggle('d-none', !groupe[1]);
            bloc.querySelectorAll('input').forEach(function (champ) { champ.disabled = !groupe[1]; });
        });
    }

    radios.forEach(function (radio) { radio.addEventListener('change', appliquer); });
    appliquer();
})();

(function () {
    var conf = window.PHOSTRACK_DRAGLINE;
    var points = conf.points || [];

    var carte = L.map('carte-dragline');

    var satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 20, maxNativeZoom: 18, attribution: 'Imagerie © Esri, Maxar, Earthstar Geographics' }
    );
    var plan = L.tileLayer(
        'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        { maxZoom: 20, maxNativeZoom: 19, attribution: '© contributeurs OpenStreetMap' }
    );

    satellite.addTo(carte);
    L.control.layers({ 'Satellite': satellite, 'Plan': plan }, null, { collapsed: true }).addTo(carte);
    L.control.scale({ imperial: false, position: 'bottomright' }).addTo(carte);

    function formater(valeur) {
        return Number(valeur).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
    }

    if (points.length === 0) {
        carte.setView([15.07, -16.82], 11);
        return;
    }

    var positions = points.map(function (p) { return [p.lat, p.lon]; });
    var marqueurs = {};

    // Trajectoire en pointillés, du plus ancien au plus récent
    if (points.length > 1) {
        L.polyline(positions, { color: '#E07B1F', weight: 3, dashArray: '6 6' }).addTo(carte);
    }

    points.forEach(function (p, i) {
        var derniere = i === points.length - 1;

        var marqueur = L.circleMarker([p.lat, p.lon], {
            radius: derniere ? 9 : 6,
            color: '#FFFFFF',
            weight: 2,
            fillColor: '#E07B1F',
            fillOpacity: 1
        }).addTo(carte);

        // La position la plus récente porte une étiquette permanente
        marqueur.bindTooltip(
            derniere ? conf.nom + ' · ' + p.date : p.date + ' · X ' + formater(p.x) + ' · Y ' + formater(p.y),
            { permanent: derniere, direction: 'top' }
        );

        marqueurs[p.id] = marqueur;
    });

    carte.fitBounds(L.latLngBounds(positions).pad(0.4), { maxZoom: 17 });

    // Un clic sur une ligne de l'historique centre la carte sur la position
    document.querySelectorAll('[data-position]').forEach(function (ligne) {
        ligne.addEventListener('click', function (e) {
            if (e.target.closest('form, a, button')) { return; }

            var marqueur = marqueurs[ligne.dataset.position];
            if (!marqueur) { return; }

            carte.flyTo(marqueur.getLatLng(), 17);
            marqueur.openTooltip();
        });
    });
})();