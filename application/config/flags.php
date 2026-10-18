<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Das Paragraphenzeichen als Escape, damit die Datei encoding-unabhaengig bleibt (PHP >= 7.0)
$p = "\u{00A7}";

/**
 * Flag-Bits fu"r ip_clients.client_flags UND ip_invoices.invoice_type.
 * Andere Installation: nur diese Datei austauschen (z. B. Produktgruppen statt Paragraphen).
 *
 *  key     Kurzname; Formularfeld = flags_prefix . key (z. B. flag_45a); wird in budget_types als on/off benutzt
 *  bit     Bit-Wert (Zweierpotenzen; 128 ist für flag_carelevel_confirmation_bit reserviert)
 *  label   Beschriftung in Formularen und Filter
 *  short   Kurzform fu"r Listen/Reports
 *  client / invoice   false = in diesem Kontext nicht zur Auswahl anbieten (Standard: true)
 */
$config['flags_title']  = 'Paragraphen';   // U"erschrift der Gruppe in Formularen
$config['flags_prefix'] = 'flag_';

$config['flags'] = [
    // privat gibt es nicht in der Rechnung!
    ['key' => 'private', 'bit' => 1,  'label' => 'Privat',        'short' => 'privat',     'color' => '#660033', 
     'text'=>'', 'invoice' => false],

    // Reihenfolge: 45b, 39, 45a, 125 wurde festgelegt von Lidia 06.2026
    ['key' => '45b',     'bit' => 8,  'label' => 'Paragraph 45b', 'short' => "{$p}45b",    'color' => '#66ccff',
     'text' => "Entlastungshilfe nach {$p}45b SGB XI"],

    ['key' => '39',      'bit' => 2,  'label' => 'Paragraph 39',  'short' => "{$p}39",     'color' => '#ff66cc',
     'text' => "Stundenweise Verhinderungspflege nach {$p}39b SGB XI"],

    ['key' => '45a',     'bit' => 4,  'label' => 'Paragraph 45a', 'short' => "{$p}45a",    'color' => '#66ccff',
     'text' => "Entlastungshilfe mit Umwidmung nach {$p}45a SGB XI"],

    ['key' => '125',     'bit' => 16, 'label' => 'Paragraph 125', 'short' => "{$p}125",    'color' => '#777700',
     'text' => "Umwandlungsanspruch nach {$p}125 SGB XI"],
];
// nur ip_clients.client_flags: Pflegegrad-Besta"tigung (kein Eintrag in 'flags', also kein Filter)
$config['flag_carelevel_confirmation_bit'] = 128;

unset($p);
