<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * maricare - budget - helper
 *
 * CSS fuer die Sparklines - einmal pro Seite ausgeben.
 */
if (!function_exists('budget_css')) {
    function budget_css()
    {
        return '<style>
.sparkline-months { display: inline-flex; gap: 2px; }
.sparkline-block { display: inline-block; width: 12px; height: 18px; border-radius: 2px; cursor: default; }
.sparkline-block:hover { outline: 1px solid #333; }
</style>';
    }
}

/**
 * 12-Monats-Sparkline. $amounts = ['YYYY-MM' => Betrag, ...]
 */
if (!function_exists('budget_sparkline')) {
    function budget_sparkline(array $amounts)
    {
        if (empty($amounts)) return '';
        $max = max($amounts) ?: 1;

        $html = '<div class="sparkline-months">';
        foreach ($amounts as $ym => $amount) {
            $month_label = date('M Y', strtotime($ym . '-01'));

            if ($amount == 0) {
                $style = 'background-color:#fff; border:1px solid #ccc;';
            } else {
                $lightness = 90 - round(($amount / $max) * 60);
                $style = "background-color:hsl(210, 70%, {$lightness}%);";
            }

            $html .= sprintf(
                '<span class="sparkline-block" style="%s" title="%s: %s"></span>',
                $style, $month_label, format_currency($amount)
            );
        }
        return $html . '</div>';
    }
}
