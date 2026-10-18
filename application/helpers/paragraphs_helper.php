<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Paragraphen-Definitionen aus config/paragraphs.php.
 * $for = 'invoice' liefert nur die in Rechnungen wählbaren.
 */
function paragraphs(string $for = ''): array
{
    $CI = &get_instance();
    $CI->config->load('paragraphs'); // lädt nur einmal

    $items = $CI->config->item('paragraphs');

    return $for === 'invoice'
        ? array_filter($items, static fn($p) => $p['invoice'])
        : $items;
}

/** Alle erlaubten Bit-Werte, z. B. [1, 2, 4, 8, 16] */
function paragraph_bits(string $for = ''): array
{
    return array_values(array_column(paragraphs($for), 'bit'));
}

/** Bitmaske aus den POST-Checkboxen (flag_39, flag_45a, ...) */
function paragraph_mask_from_post(string $for = ''): int
{
    $CI   = &get_instance();
    $mask = 0;

    foreach (paragraphs($for) as $name => $p) {
        if ($CI->input->post($name)) {
            $mask |= $p['bit'];
        }
    }

    return $mask;
}
