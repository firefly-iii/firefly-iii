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
    'initial_config_title'        => 'Import configuration (1/3)',
    'initial_config_text'         => 'To be able to import your file correctly, please validate the options below.',
    'initial_config_box'          => 'Basic CSV import configuration',
    'initial_header_help'         => 'Check this box if the first row of your CSV file are the column titles.',
    'initial_date_help'           => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'initial_delimiter_help'      => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'initial_import_account_help' => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',

    // roles config
    'roles_title'                 => 'Define each column\'s role',
    'roles_text'                  => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'roles_table'                 => 'Table',
    'roles_column_name'           => 'Name of column',
    'roles_column_example'        => 'Column example data',
    'roles_column_role'           => 'Column data meaning',
    'roles_do_map_value'          => 'Map these values',
    'roles_column'                => 'Column',
    'roles_no_example_data'       => 'No example data available',

    'roles_store' => 'Continue import',
    'roles_do_not_map'         => '(do not map)',

    // map data
    'map_title'                => 'Verbind importdata met Firefly III data',
    'map_text'                 => 'In deze tabellen is de linkerwaarde een waarde uit je CSV bestand. Jij moet de link leggen, als mogelijk, met een waarde uit jouw database. Firefly houdt zich hier aan. Als er geen waarde is, selecteer dan ook niets.',

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