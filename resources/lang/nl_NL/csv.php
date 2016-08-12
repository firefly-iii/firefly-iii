<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

return [

    'import_configure_title' => 'Import configureren',
    'import_configure_intro' => 'Hier zie je enkele opties voor jouw CSV bestand. Geef aan of je CSV bestand kolomtitels bevat, en hoe het datumveld is opgebouwd. Hier moet je wellicht wat experimenteren. Het scheidingsteken is meestal een ",", maar dat kan ook een ";" zijn. Controleer dit zorgvuldig.',
    'import_configure_form'  => 'Formulier',
    'header_help'            => 'Vink hier als de eerste rij kolomtitels bevat',
    'date_help'              => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'delimiter_help'         => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'config_file_help'       => 'Select your CSV import configuration here. If you do not know what this is, ignore it. It will be explained later.',
    'import_account_help'    => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'upload_not_writeable'   => 'The grey box contains a file path. It should be writeable. Please make sure it is.',

    // roles
    'column_roles_title'     => 'Bepaal de inhoud van elke kolom',
    'column_roles_text'      => '<p>Firefly III cannot guess what data each column contains. You must tell Firefly which kinds of data to expect. The example data can guide you into picking the correct type from the dropdown. If a column cannot be matched to a useful data type, please let me know <a href="https://github.com/JC5/firefly-iii/issues/new">by creating an issue</a>.</p><p>Some values in your CSV file, such as account names or categories, may already exist in your Firefly III database. If you select "map these values" Firefly will not attempt to search for matching values itself but allow you to match the CSV values against the values in your database. This allows you to fine-tune the import.</p>',
    'column_roles_table'     => 'Tabel',
    'column_name'            => 'Kolomnaam',
    'column_example'         => 'Voorbeeldgegevens',
    'column_role'            => 'Column data meaning',
    'do_map_value'           => 'Map these values',
    'column'                 => 'Column',
    'no_example_data'        => 'No example data available',
    'store_column_roles'     => 'Continue import',
    'do_not_map'             => '(do not map)',
    'map_title'              => 'Connect import data to Firefly III data',
    'map_text'               => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',

    'field_value'          => 'Field value',
    'field_mapped_to'      => 'Mapped to',
    'store_column_mapping' => 'Store mapping',

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
    'column_rabo-debet-credit'      => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'       => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                => 'SEPA Direct Debet',
    'column_tags-comma'             => 'Tags (comma separated)',
    'column_tags-space'             => 'Tags (space separated)',
    'column_account-number'         => 'Asset account (account number)',
    'column_opposing-number'        => 'Opposing account (account number)',
];