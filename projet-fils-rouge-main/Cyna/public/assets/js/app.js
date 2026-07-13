/* ===========================================================================
   CYNA — Interactions front-office (JavaScript natif, sans dépendance).
   Menu burger accessible et carrousel de la page d'accueil.
   =========================================================================== */
(function () {
    'use strict';

    // --- Menu burger (mobile) ---------------------------------------------
    var burger = document.querySelector('.burger');
    var nav = document.getElementById('main-nav');
    if (burger && nav) {
        burger.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // --- Carrousel ---------------------------------------------------------
    document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
        var track = carousel.querySelector('.carousel__track');
        var slides = carousel.querySelectorAll('.carousel__slide');
        if (!track || slides.length <= 1) {
            return;
        }

        var index = 0;
        var timer = null;

        function go(to) {
            index = (to + slides.length) % slides.length;
            // Décalage cohérent en lecture LTR comme RTL.
            var sign = getComputedStyle(carousel).direction === 'rtl' ? 1 : -1;
            track.style.transform = 'translateX(' + (sign * index * 100) + '%)';
        }

        function start() { timer = window.setInterval(function () { go(index + 1); }, 5000); }
        function stop() { if (timer) { window.clearInterval(timer); timer = null; } }

        carousel.querySelector('.carousel__btn--next').addEventListener('click', function () { go(index + 1); });
        carousel.querySelector('.carousel__btn--prev').addEventListener('click', function () { go(index - 1); });

        // Pause au survol / focus (exigence d'accessibilité WCAG 2.1).
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);

        go(0);
        start();
    });
})();
