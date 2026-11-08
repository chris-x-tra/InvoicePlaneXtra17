<?php
if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Preview- und Dokumentablage-Helper (gemeinsam für documents, expenses, payments/receipts).
 *
 * Laden: $this->load->helper('preview');   (lädt file_security selbst nach)
 *
 * --- Vorschau (JPEG) -------------------------------------------------------------------------
 *   preview_kind($pfad)                                   'image' | 'pdf' | null
 *   generate_preview($quelle, $ziel, $max_w, $max_h, $q)  Bild bzw. erste PDF-Seite -> JPEG (kein Hochskalieren)
 *     Reihenfolge: Imagick (wie bisher in Expenses/Payments) -> GD (Bilder) -> pdftoppm -> ImageMagick-CLI
 *
 * --- Ablage mit Hash-Verzeichnissen (do_hash_dir / return_hash_dir aus file_security_helper) ---
 *   $base = Verzeichnis des Moduls, z. B. UPLOADS_FOLDER . 'expenses_documents/'
 *   Original:  $base/<xx>/<datei>                       Vorschau: $base/previews/<xx>/<datei-ohne-ext>_preview.jpg
 *   doc_store_uploaded($flach, $name, $base)   Upload aus dem flachen Verzeichnis ins Hash-Verzeichnis verschieben
 *   doc_source_path($base, $name)              Pfad des Originals (Hash-Verzeichnis, sonst alter flacher Pfad) oder null
 *   doc_preview_path($base, $name)             Ziel-Pfad der Vorschau (legt nichts an)
 *   doc_ensure_preview($base, $name, $max)     Vorschau-Pfad; erzeugt sie bei Bedarf nach (alte Dateien!) oder null
 *   doc_has_preview($base, $name)              true, wenn Vorschau existiert oder erzeugt werden kann
 *   doc_delete_files($base, $name)             Original + Vorschau löschen (auch alte flache Pfade)
 */

// ===================================================================== Vorschau

/**
 * @return string|null 'image' | 'pdf' | null (keine Vorschau möglich)
 */
function preview_kind(string $path): ?string
{
    $ext = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
        return 'image';
    }
    if (in_array($ext, ['tif', 'tiff'], true)) {
        return class_exists('Imagick') ? 'image' : null; // GD kann kein TIFF
    }

    return $ext === 'pdf' ? 'pdf' : null;
}

/**
 * Erzeugt eine Vorschau (JPEG) in $preview_path, passt das Bild in max_w x max_h ein.
 *
 * @return bool true bei Erfolg
 */
function generate_preview(string $source_path, string $preview_path, int $max_w = 480, int $max_h = 480, int $quality = 80): bool
{
    $kind = preview_kind($source_path);
    if ($kind === null || ! is_file($source_path)) {
        return false;
    }

    $dir = dirname($preview_path);
    if ( ! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
        log_message('error', 'preview: Zielverzeichnis kann nicht angelegt werden');

        return false;
    }

    // 1. Imagick (alle Bildformate und PDF), wie bisher in Expenses/Payments
    if (_preview_imagick($source_path, $preview_path, $max_w, $max_h, $quality, $kind === 'pdf')) {
        return true;
    }

    // 2. Bilder: GD
    if ($kind === 'image') {
        return _preview_gd_fit($source_path, $preview_path, $max_w, $max_h, $quality);
    }

    // 3. PDF ohne Imagick: pdftoppm bzw. ImageMagick-CLI, danach mit GD einpassen
    $rendered = _preview_pdf_to_jpg($source_path, max($max_w, $max_h));
    if ($rendered === null) {
        log_message('error', 'preview: PDF-Seite konnte nicht gerendert werden (Imagick/pdftoppm/convert fehlt?)');

        return false;
    }

    $ok = function_exists('imagecreatetruecolor')
        ? _preview_gd_fit($rendered, $preview_path, $max_w, $max_h, $quality)
        : @copy($rendered, $preview_path); // ohne GD: unverkleinert übernehmen
    @unlink($rendered);

    return $ok;
}

/** Imagick-Variante (Bilder: erstes Bild, PDF: erste Seite). false, wenn Imagick fehlt oder scheitert. */
function _preview_imagick(string $src, string $dst, int $max_w, int $max_h, int $quality, bool $is_pdf): bool
{
    if ( ! class_exists('Imagick')) {
        return false;
    }

    try {
        $im = new Imagick();
        if ($is_pdf) {
            $im->setResolution(150, 150);
        }
        $im->readImage($src . '[0]'); // [0]: nur erste Seite / erstes Bild

        $im->setImageBackgroundColor('white');
        $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        if (method_exists($im, 'autoOrient')) {
            $im->autoOrient(); // Handyfotos
        }
        if ($im->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
            $im->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }
        if ($im->getImageWidth() > $max_w || $im->getImageHeight() > $max_h) {
            $im->thumbnailImage($max_w, $max_h, true); // einpassen, nie hochskalieren
        }

        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality($quality);
        $im->stripImage();

        $tmp = $dst . '.tmp';
        $ok  = $im->writeImage($tmp) && @rename($tmp, $dst);
        if ( ! $ok) {
            @unlink($tmp);
        }
        $im->clear();

        return $ok;
    } catch (Throwable $e) {
        log_message('error', 'preview: Imagick fehlgeschlagen: ' . $e->getMessage());

        return false;
    }
}

/** Bild mit GD in die Box einpassen und als JPEG schreiben (atomar über .tmp) */
function _preview_gd_fit(string $src, string $dst, int $max_w, int $max_h, int $quality): bool
{
    if ( ! function_exists('imagecreatetruecolor')) {
        log_message('error', 'preview: PHP-GD fehlt');

        return false;
    }

    $info = @getimagesize($src);
    if ( ! $info) {
        return false;
    }
    [$w, $h, $type] = $info;
    if ($w < 1 || $h < 1 || ($w * $h) > 50000000) { // Schutz vor Speicherbomben
        return false;
    }

    switch ($type) {
        case IMAGETYPE_JPEG:
            $im = @imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $im = @imagecreatefrompng($src);
            break;
        case IMAGETYPE_GIF:
            $im = @imagecreatefromgif($src);
            break;
        case IMAGETYPE_WEBP:
            $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false;
            break;
        case IMAGETYPE_BMP:
            $im = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($src) : false;
            break;
        default:
            $im = false;
    }
    if ( ! $im) {
        return false;
    }

    // EXIF-Orientierung (Handyfotos)
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif        = @exif_read_data($src);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angle       = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle !== 0) {
            $rotated = imagerotate($im, $angle, 0);
            if ($rotated) {
                $im = $rotated;
                $w  = imagesx($im);
                $h  = imagesy($im);
            }
        }
    }

    $scale = min($max_w / $w, $max_h / $h, 1);
    $nw    = max(1, (int) round($w * $scale));
    $nh    = max(1, (int) round($h * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $tmp = $dst . '.tmp';
    $ok  = imagejpeg($out, $tmp, $quality) && @rename($tmp, $dst);
    if ( ! $ok) {
        @unlink($tmp);
    }

    imagedestroy($im);
    imagedestroy($out);

    return $ok;
}

/** Programm suchen: PHP-FPM hat oft einen kleinen PATH (FreeBSD: /usr/local/bin) */
function _preview_find_bin(string $name): ?string
{
    foreach (['/usr/local/bin', '/usr/bin', '/opt/bin', '/bin'] as $dir) {
        if (is_executable($dir . '/' . $name)) {
            return $dir . '/' . $name;
        }
    }

    return null;
}

function _preview_can_exec(): bool
{
    if ( ! function_exists('exec')) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

    return ! in_array('exec', $disabled, true);
}

/** Erste PDF-Seite als temporäres JPEG rendern (ohne Imagick). Gibt den Pfad oder null zurück. */
function _preview_pdf_to_jpg(string $pdf, int $px): ?string
{
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pv_' . bin2hex(random_bytes(6));

    // pdftoppm (poppler) - schnell und zuverlässig
    if (_preview_can_exec() && ($bin = _preview_find_bin('pdftoppm'))) {
        $cmd = escapeshellarg($bin) . ' -jpeg -f 1 -l 1 -singlefile -scale-to ' . (int) $px
            . ' ' . escapeshellarg($pdf) . ' ' . escapeshellarg($base) . ' 2>&1';
        exec($cmd, $out, $rc);
        if ($rc === 0 && is_file($base . '.jpg')) {
            return $base . '.jpg';
        }
    }

    // ImageMagick Kommandozeile (braucht Ghostscript und erlaubte PDF-Policy)
    if (_preview_can_exec()) {
        foreach (['magick', 'convert'] as $name) {
            if ($bin = _preview_find_bin($name)) {
                $cmd = escapeshellarg($bin) . ' -density 150 ' . escapeshellarg($pdf . '[0]')
                    . ' -background white -alpha remove ' . escapeshellarg($base . '.jpg') . ' 2>&1';
                exec($cmd, $out, $rc);
                if ($rc === 0 && is_file($base . '.jpg')) {
                    return $base . '.jpg';
                }
            }
        }
    }

    return null;
}

// ===================================================================== Ablage (Hash-Verzeichnisse)

/** do_hash_dir / return_hash_dir stammen aus file_security_helper */
function _doc_load_deps(): void
{
    if ( ! function_exists('do_hash_dir') || ! function_exists('return_hash_dir')) {
        get_instance()->load->helper('file_security');
    }
}

function _doc_join(string $base_dir, string $rel): string
{
    return rtrim($base_dir, '/\\') . DIRECTORY_SEPARATOR . $rel;
}

/** Dateiname der Vorschau: <name-ohne-ext>_preview.jpg */
function doc_preview_name(string $filename): string
{
    return pathinfo(basename($filename), PATHINFO_FILENAME) . '_preview.jpg';
}

/** Ziel-Pfad der Vorschau im Hash-Verzeichnis (legt nichts an) */
function doc_preview_path(string $base_dir, string $filename): string
{
    _doc_load_deps();

    return _doc_join($base_dir, 'previews' . DIRECTORY_SEPARATOR . return_hash_dir(doc_preview_name($filename)));
}

/**
 * Upload aus dem flachen Verzeichnis ins Hash-Verzeichnis verschieben.
 *
 * @return string|null Zielpfad oder null bei Fehler
 */
function doc_store_uploaded(string $flat_path, string $filename, string $base_dir): ?string
{
    _doc_load_deps();

    $target = do_hash_dir(basename($filename), $base_dir);

    if (realpath($flat_path) !== false && realpath($flat_path) === realpath($target)) {
        return $target;
    }

    return @rename($flat_path, $target) ? $target : null;
}

/**
 * Echter Pfad des Originals: erst Hash-Verzeichnis, dann (Übergang) der alte flache Pfad.
 * Mit Schutz gegen Path-Traversal; null, wenn nicht vorhanden.
 */
function doc_source_path(string $base_dir, string $filename): ?string
{
    _doc_load_deps();

    $name = basename($filename);
    $base = realpath($base_dir);
    if ($base === false || $name === '') {
        return null;
    }

    foreach ([_doc_join($base_dir, return_hash_dir($name)), _doc_join($base_dir, $name)] as $candidate) {
        $p = realpath($candidate);
        if ($p !== false && is_file($p) && strpos($p, $base . DIRECTORY_SEPARATOR) === 0) {
            return $p;
        }
    }

    return null;
}

/**
 * Vorschau-Pfad; erzeugt die Vorschau bei Bedarf (z. B. für Dateien aus der Zeit vor den Hash-Verzeichnissen).
 *
 * @return string|null null, wenn keine Vorschau möglich ist
 */
function doc_ensure_preview(string $base_dir, string $filename, int $max = 480): ?string
{
    $dst = doc_preview_path($base_dir, $filename);
    if (is_file($dst)) {
        return $dst;
    }

    $src = doc_source_path($base_dir, $filename);
    if ($src === null || preview_kind($src) === null) {
        return null;
    }

    return generate_preview($src, $dst, $max, $max) ? $dst : null;
}

/** true, wenn eine Vorschau existiert oder (bei Bildern/PDF) erzeugt werden kann */
function doc_has_preview(string $base_dir, string $filename): bool
{
    if (is_file(doc_preview_path($base_dir, $filename))) {
        return true;
    }

    $src = doc_source_path($base_dir, $filename);

    return $src !== null && preview_kind($src) !== null;
}

/** Original und Vorschau löschen, auch an den alten flachen Pfaden */
function doc_delete_files(string $base_dir, string $filename): void
{
    _doc_load_deps();

    $name = basename($filename);

    $paths = [
        _doc_join($base_dir, return_hash_dir($name)),
        _doc_join($base_dir, $name),
        doc_preview_path($base_dir, $name),
        _doc_join($base_dir, 'previews' . DIRECTORY_SEPARATOR . doc_preview_name($name)), // alt, flach
    ];

    foreach ($paths as $p) {
        if (is_file($p)) {
            @unlink($p);
        }
    }
}
