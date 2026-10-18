<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Flag-Definitionen aus config/flags.php.
 * $for = 'invoice' oder 'client': ohne Einträge, die dort mit false ausgeschlossen sind.
 */
function flag_items(string $for = ''): array
{
    $CI = &get_instance();
    $CI->config->load('flags');

    $items = $CI->config->item('flags');
    if ($for !== '') {
        $items = array_filter($items, static fn($f) => $f[$for] ?? true);
    }

    return array_values($items);
}

/** Erlaubte Bit-Werte, z. B. [1, 2, 4, 8, 16] */
function flag_bits(string $for = ''): array
{
    return array_column(flag_items($for), 'bit');
}

/** Formularfeld-Name, z. B. flag_45a */
function flag_field(array $flag): string
{
    return config_item('flags_prefix') . $flag['key'];
}

/** Bitmaske aus den POST-Checkboxen */
function flag_mask_from_post(string $for = ''): int
{
    $CI   = &get_instance();
    $mask = 0;

    foreach (flag_items($for) as $flag) {
        if ($CI->input->post(flag_field($flag))) {
            $mask |= $flag['bit'];
        }
    }

    return $mask;
}
