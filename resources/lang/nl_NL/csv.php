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

declare(strict_types = 1);

return [

    'import_configure_title' => 'Import configureren',
    'import_configure_intro' => 'Hier zie je enkele opties voor jouw CSV bestand. Geef aan of je CSV bestand kolomtitels bevat, en hoe het datumveld is opgebouwd. Hier moet je wellicht wat experimenteren. Het scheidingsteken is meestal een ",", maar dat kan ook een ";" zijn. Controleer dit zorgvuldig.',
    'import_configure_form'  => 'Basic CSV import options',
    'header_help'            => 'Vink hier als de eerste rij kolomtitels bevat',
    'date_help'              => 'Datum/tijd formaat in jouw CSV bestand. Volg het formaat zoals ze het <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">op deze pagina</a> uitleggen. Het standaardformaat ziet er zo uit: :dateExample.',
    'delimiter_help'         => 'Kies het veldscheidingsteken dat in jouw bestand wordt gebruikt. Als je het niet zeker weet, is de komma de beste optie.',
    'import_account_help'    => 'Als jouw CSV bestand geen referenties bevat naar jouw rekening(en), geef dan hier aan om welke rekening het gaat.',
    'upload_not_writeable'   => 'Het grijze vlak bevat een bestandspad. Dit pad moet schrijfbaar zijn.',

    // roles
    'column_roles_title'     => 'Bepaal de inhoud van elke kolom',
    'column_roles_table'     => 'Tabel',
    'column_name'            => 'Kolomnaam',
    'column_example'         => 'Voorbeeldgegevens',
    'column_role'            => 'Kolomrol',
    'do_map_value'           => 'Maak een mapping',
    'column'                 => 'Kolom',
    'no_example_data'        => 'Geen voorbeeldgegevens',
    'store_column_roles'     => 'Ga verder met import',
    'do_not_map'             => '(niet mappen)',
    'map_title'              => 'Verbind importdata met Firefly III data',
    'map_text'               => 'In deze tabellen is de linkerwaarde een waarde uit je CSV bestand. Jij moet de link leggen, als mogelijk, met een waarde uit jouw database. Firefly houdt zich hier aan. Als er geen waarde is, selecteer dan ook niets.',

    'field_value'          => 'Veldwaarde',
    'field_mapped_to'      => 'Gelinkt aan',
    'store_column_mapping' => 'Mapping opslaan',

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