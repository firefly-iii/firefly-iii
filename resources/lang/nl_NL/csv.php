<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Importinstellingen (1/3) - Algemene CVS importinstellingen',
    'initial_text'                  => 'Om je bestand goed te kunnen importeren moet je deze opties verifiëren.',
    'initial_box'                   => 'Algemene CVS importinstellingen',
    'initial_box_title'             => 'Algemene CVS importinstellingen',
    'initial_header_help'           => 'Vink hier als de eerste rij kolomtitels bevat.',
    'initial_date_help'             => 'Datum/tijd formaat in jouw CSV bestand. Volg het formaat zoals ze het <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">op deze pagina</a> uitleggen. Het standaardformaat ziet er zo uit: :dateExample.',
    'initial_delimiter_help'        => 'Kies het veldscheidingsteken dat in jouw bestand wordt gebruikt. Als je het niet zeker weet, is de komma de beste optie.',
    'initial_import_account_help'   => 'Als jouw CSV bestand geen referenties bevat naar jouw rekening(en), geef dan hier aan om welke rekening het gaat.',
    'initial_submit'                => 'Ga verder met stap 2/3',

    // roles config
    'roles_title'                   => 'Importinstellingen (2/3) - rol van elke kolom definiëren',
    'roles_text'                    => 'Elke kolom in je CSV-bestand bevat bepaalde gegevens. Gelieve aan te geven wat voor soort gegevens de import-routine kan verwachten. De optie "maak een link" betekent dat u elke vermelding in die kolom linkt aan een waarde uit je database. Een vaak gelinkte kolom is die met de IBAN-code van de tegenrekening. Die kan je dan linken aan de IBAN in jouw database.',
    'roles_table'                   => 'Tabel',
    'roles_column_name'             => 'Kolomnaam',
    'roles_column_example'          => 'Voorbeeldgegevens',
    'roles_column_role'             => 'Kolomrol',
    'roles_do_map_value'            => 'Maak een link',
    'roles_column'                  => 'Kolom',
    'roles_no_example_data'         => 'Geen voorbeeldgegevens',
    'roles_submit'                  => 'Ga verder met stap 3/3',
    'roles_warning'                 => 'Geef minstens de kolom aan waar het bedrag in staat. Als het even kan, ook een kolom voor de omschrijving, datum en de andere rekening.',

    // map data
    'map_title'                     => 'Importinstellingen (3/3) - Link importgegevens aan Firefly III-gegevens',
    'map_text'                      => 'In deze tabellen is de linkerwaarde een waarde uit je CSV bestand. Jij moet de link leggen, als mogelijk, met een waarde uit jouw database. Firefly houdt zich hier aan. Als er geen waarde is, selecteer dan ook niets.',
    'map_field_value'               => 'Veldwaarde',
    'map_field_mapped_to'           => 'Gelinkt aan',
    'map_do_not_map'                => '(niet linken)',
    'map_submit'                    => 'Start importeren',

    // map things.
    'column__ignore'                => '(negeer deze kolom)',
    'column_account-iban'           => 'Betaalrekening (IBAN)',
    'column_account-id'             => 'Betaalrekening (ID gelijk aan Firefly)',
    'column_account-name'           => 'Betaalrekeningnaam',
    'column_amount'                 => 'Bedrag',
    'column_amount-comma-separated' => 'Bedrag (komma as decimaalscheidingsteken)',
    'column_bill-id'                => 'Contract (ID gelijk aan Firefly)',
    'column_bill-name'              => 'Contractnaam',
    'column_budget-id'              => 'Budget (ID gelijk aan Firefly)',
    'column_budget-name'            => 'Budgetnaam',
    'column_category-id'            => 'Categorie (ID gelijk aan Firefly)',
    'column_category-name'          => 'Categorienaam',
    'column_currency-code'          => 'Valutacode (ISO 4217)',
    'column_currency-id'            => 'Valuta (ID gelijk aan Firefly)',
    'column_currency-name'          => 'Valutanaam',
    'column_currency-symbol'        => 'Valutasymbool',
    'column_date-interest'          => 'Datum (renteberekening)',
    'column_date-book'              => 'Datum (boeking)',
    'column_date-process'           => 'Datum (verwerking)',
    'column_date-transaction'       => 'Datum',
    'column_description'            => 'Omschrijving',
    'column_opposing-iban'          => 'Tegenrekening (IBAN)',
    'column_opposing-id'            => 'Tegenrekening (ID gelijk aan Firefly)',
    'column_external-id'            => 'Externe ID',
    'column_opposing-name'          => 'Tegenrekeningnaam',
    'column_rabo-debet-credit'      => 'Rabobankspecifiek bij/af indicator',
    'column_ing-debet-credit'       => 'ING-specifieke bij/af indicator',
    'column_sepa-ct-id'             => 'SEPA transactienummer',
    'column_sepa-ct-op'             => 'SEPA tegenrekeningnummer',
    'column_sepa-db'                => 'SEPA "direct debet"-nummer',
    'column_tags-comma'             => 'Tags (kommagescheiden)',
    'column_tags-space'             => 'Tags (spatiegescheiden)',
    'column_account-number'         => 'Betaalrekening (rekeningnummer)',
    'column_opposing-number'        => 'Tegenrekening (rekeningnummer)',
];