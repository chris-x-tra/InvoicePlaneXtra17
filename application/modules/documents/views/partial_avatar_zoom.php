<?php
/*
 * Klick auf ein Profilbild (img.js-avatar-zoom) tauscht die kleine Vorschau (240 px) gegen die große Version
 * (gespeichertes Original, max. 1536 px breit, Anzeige max. 640 px) direkt in der Seite; ein weiterer Klick wechselt zurück.
 *
 * Markup:  <img class="js-avatar-zoom" src="<klein>" data-small="<klein>" data-full="<groß>">
 * Wird von partial_avatar.php und users/view.php eingebunden, CSS/JS erscheinen nur einmal pro Seite.
 */
if (defined('AVATAR_ZOOM_LOADED')) {
    return;
}
define('AVATAR_ZOOM_LOADED', true);
?>
<style>
    img.js-avatar-zoom { cursor: zoom-in; }
    img.js-avatar-zoom.is-big {
        cursor: zoom-out;
        width: auto !important;
        height: auto !important;
        max-width: 640px !important;
        max-width: min(640px, 100%) !important;
        border-radius: 8px !important;
        object-fit: contain !important;
    }
</style>
<script>
    document.addEventListener('click', function (e) {
        var img = e.target.closest ? e.target.closest('img.js-avatar-zoom') : null;
        if (!img) { return; }
        var big = !img.classList.contains('is-big');
        img.src = big ? img.getAttribute('data-full') : img.getAttribute('data-small');
        img.classList.toggle('is-big', big);
        img.style.visibility = 'visible';
        img.title = big ? 'Zum Verkleinern klicken' : 'Zum Vergrößern klicken';
    });
</script>
