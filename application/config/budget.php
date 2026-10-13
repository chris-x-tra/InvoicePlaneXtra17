<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Budget-Konfiguration (maricare)
 *
 * NEUEN PARAGRAPHEN HINZUFUEGEN:
 *   1. Bit in 'budget_flags' ergaenzen (naechste freie Zweierpotenz)
 *   2. Eintrag in 'budget_types' ergaenzen
 * Mehr nicht: Clients-Ansicht und Reports-Ansicht ziehen sich die Spalten aus dieser Liste.
 * (Das Setzen des Bits an der Rechnung selbst muss natuerlich weiterhin im Rechnungs-Formular passieren.)
 */

// Erster Monat des Abrechnungsjahres: 1 ... 12 (2 = Februar bis Januar)
$config['budget_start_month'] = 2;

// Bits in invoice_type
$config['budget_flags'] = [
    'private' => 1,
    '39'      => 2,
    '45a'     => 4,
    '45b'     => 8,
    '125'     => 16,
    // Beispiel: '42' => 32,
];

// Das Paragraphenzeichen als Escape, damit die Datei encoding-unabhaengig bleibt (PHP >= 7.0)
$p = "\u{00A7}";

/**
 * Jede Position zaehlt eine Rechnung, wenn
 *   - alle Flags aus 'on'  gesetzt sind und
 *   - alle Flags aus 'off' NICHT gesetzt sind.
 * Alle anderen Bits sind egal.
 */
$config['budget_types'] = [
    'budget_39' => [
        'label' => "1) Stundenweise Verhinderungspflege nach {$p}39b SGB XI",
        'short' => "{$p}39b",
        'on'    => ['39'],
    ],
    'budget_45a' => [
        'label' => "2) Entlastungshilfe mit Umwidmung nach {$p}45a SGB XI",
        'short' => "{$p}45a",
        'on'    => ['45a'],
        'off'   => ['45b'],
    ],
    'budget_45b' => [
        'label' => "3) Entlastungshilfe nach {$p}45b SGB XI",
        'short' => "{$p}45b",
        'on'    => ['45b'],
        'off'   => ['45a'],
    ],
    'budget_45a_45b' => [
        'label' => "4) {$p}45a + {$p}45b kombiniert",
        'short' => "{$p}45a + {$p}45b",
        'on'    => ['45a', '45b'],
    ],
    'budget_125' => [
        'label' => "5) Umwandlungsanspruch nach {$p}125 SGB XI",
        'short' => "{$p}125",
        'on'    => ['125'],
    ],
];

unset($p);
