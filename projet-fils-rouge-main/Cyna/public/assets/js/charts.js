/* ===========================================================================
   CYNA — Graphiques du tableau de bord (Canvas natif, sans bibliothèque).
   Récupère les données JSON exposées par le back-office puis trace un
   histogramme (ventes/jour) et un camembert (ventes/catégorie).
   =========================================================================== */
(function () {
    'use strict';

    var palette = ['#25d0c0', '#7c8cff', '#fbbf24', '#ff6b6b', '#4ade80', '#c084fc'];

    function euros(cents) { return (cents / 100).toFixed(0) + ' €'; }

    function drawBar(canvas, data) {
        var ctx = canvas.getContext('2d');
        var w = canvas.width = canvas.clientWidth;
        var h = canvas.height;
        ctx.clearRect(0, 0, w, h);
        if (!data.length) { return placeholder(ctx, w, h); }

        var pad = 30;
        var max = Math.max.apply(null, data.map(function (d) { return d.total; })) || 1;
        var barW = (w - pad * 2) / data.length * 0.6;
        var gap = (w - pad * 2) / data.length;

        data.forEach(function (d, i) {
            var bh = (h - pad * 2) * (d.total / max);
            var x = pad + i * gap + (gap - barW) / 2;
            var y = h - pad - bh;
            ctx.fillStyle = palette[0];
            ctx.fillRect(x, y, barW, bh);
            ctx.fillStyle = '#a7b0cf';
            ctx.font = '10px system-ui';
            ctx.textAlign = 'center';
            ctx.fillText(d.label.slice(5), x + barW / 2, h - pad + 14);
        });
    }

    function drawPie(canvas, data) {
        var ctx = canvas.getContext('2d');
        var w = canvas.width = canvas.clientWidth;
        var h = canvas.height;
        ctx.clearRect(0, 0, w, h);
        if (!data.length) { return placeholder(ctx, w, h); }

        var total = data.reduce(function (s, d) { return s + d.total; }, 0) || 1;
        var cx = h / 2, cy = h / 2, r = h / 2 - 20;
        var start = -Math.PI / 2;

        data.forEach(function (d, i) {
            var slice = (d.total / total) * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, start, start + slice);
            ctx.closePath();
            ctx.fillStyle = palette[i % palette.length];
            ctx.fill();
            start += slice;

            // Légende
            ctx.fillStyle = '#eef2ff';
            ctx.font = '12px system-ui';
            ctx.textAlign = 'left';
            ctx.fillText(d.label + ' — ' + euros(d.total), h + 10, 24 + i * 20);
        });
    }

    function placeholder(ctx, w, h) {
        ctx.fillStyle = '#a7b0cf';
        ctx.font = '13px system-ui';
        ctx.textAlign = 'center';
        ctx.fillText('Aucune donnée sur la période', w / 2, h / 2);
    }

    var container = document.querySelector('[data-stats]');
    if (!container) { return; }

    fetch(container.getAttribute('data-endpoint'), { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var bar = container.querySelector('[data-chart="bar"]');
            var pie = container.querySelector('[data-chart="pie"]');
            if (bar) { drawBar(bar, data.salesByDay || []); }
            if (pie) { drawPie(pie, data.salesByCategory || []); }
        })
        .catch(function () { /* silencieux : le tableau reste utilisable sans graphiques */ });
})();
