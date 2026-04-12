/**
 * Bacera Theme — main.js
 * Global UI interactions: mobile menu, sticky header, smooth scroll.
 */
(function ($) {
    'use strict';

    /* ── Mobile Nav Toggle ─────────────────────────────────────── */
    $(document).on('click', '#mobile-menu-toggle, .mobile-menu-toggle', function (e) {
        e.preventDefault();
        const $nav    = $('#site-navigation, .main-navigation');
        const $toggle = $(this);
        const isOpen  = $toggle.attr('aria-expanded') === 'true';

        $toggle.attr('aria-expanded', !isOpen);
        $nav.toggleClass('is-open', !isOpen);
        $('body').toggleClass('menu-open', !isOpen);
    });

    /* ── Sticky Header ─────────────────────────────────────────── */
    const $header = $('#masthead, .site-header');
    if ($header.length) {
        $(window).on('scroll.stickyHeader', function () {
            $header.toggleClass('is-sticky', $(this).scrollTop() > 60);
        });
    }

    /* ── Smooth Scroll for anchor links ────────────────────────── */
    $(document).on('click', 'a[href^="#"]', function (e) {
        const target = $(this).attr('href');
        if (target === '#' || !$(target).length) return;
        e.preventDefault();
        const offset = $header.outerHeight() || 80;
        $('html, body').animate({
            scrollTop: $(target).offset().top - offset
        }, 500, 'swing');
    });

    /* ── Swiper fallback (if Swiper not loaded) ─────────────────── */
    if (typeof Swiper !== 'undefined') {
        // Swiper already initialised by the sections that need it.
    }

})(jQuery);
