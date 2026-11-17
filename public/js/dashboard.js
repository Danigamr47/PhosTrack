/**
 * PhosTrack — tableau de bord : deux graphiques (Chart.js).
 * Dépend de window.PHOSTRACK_DASH = { materiaux: {labels, volumes, couleurs}, campagnes: {labels, volumes} }.
 */
(function () {
    var g = window.PHOSTRACK_DASH;
    if (!g || !window.Chart) { return; }

    function nombre(valeur) {
        return Number(valeur).toLocaleString('fr-FR', { maximumFractionDigits: 1 });
    }

    // Répartition du volume par matériau
    var materiaux = document.getElementById('graph-materiaux');
    if (materiaux && g.materiaux.labels.length > 0) {
        new Chart(materiaux, {
            type: 'doughnut',
            data: {
                labels: g.materiaux.labels,
                datasets: [{
                    data: g.materiaux.volumes,
                    backgroundColor: g.materiaux.couleurs,
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return c.label + ' : ' + nombre(c.parsed) + ' m³'; } } }
                }
            }
        });
    }

    // Volume cumulé par campagne : la dernière campagne est la plus foncée
    var campagnes = document.getElementById('graph-campagnes');
    if (campagnes && g.campagnes.labels.length > 0) {
        var dernier = g.campagnes.labels.length - 1;

        new Chart(campagnes, {
            type: 'bar',
            data: {
                labels: g.campagnes.labels,
                datasets: [{
                    data: g.campagnes.volumes,
                    backgroundColor: g.campagnes.volumes.map(function (v, i) { return i === dernier ? '#1B5E4B' : '#8AB7A9'; }),
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
})();