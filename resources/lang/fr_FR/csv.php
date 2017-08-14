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
    'initial_title'                 => 'Importer la configuration (1/3) - Configuration de l\'importation CSV de base',
    'initial_text'                  => 'Pour pouvoir importer votre fichier correctement, veuillez validez les options ci-dessous.',
    'initial_box'                   => 'Options d’importation CSV basique',
    'initial_box_title'             => 'Options d’importation CSV basique',
    'initial_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'initial_date_help'             => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'initial_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'initial_import_account_help'   => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'initial_submit'                => 'Continue with step 2/3',

    // roles config
    'roles_title'                   => 'Import setup (2/3) - Define each column\'s role',
    'roles_text'                    => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'roles_table'                   => 'Table',
    'roles_column_name'             => 'Name of column',
    'roles_column_example'          => 'Column example data',
    'roles_column_role'             => 'Column data meaning',
    'roles_do_map_value'            => 'Map these values',
    'roles_column'                  => 'Column',
    'roles_no_example_data'         => 'No example data available',
    'roles_submit'                  => 'Continue with step 3/3',
    'roles_warning'                 => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',

    // map data
    'map_title'                     => 'Import setup (3/3) - Connect import data to Firefly III data',
    'map_text'                      => 'Dans les tableaux suivants, la valeur gauche vous montre des informations trouvées dans votre fichier CSV téléchargé. C’est votre rôle de mapper cette valeur, si possible, une valeur déjà présente dans votre base de données. Firefly s’en tiendra à ce mappage. Si il n’y a pas de valeur correspondante, ou vous ne souhaitez pas la valeur spécifique de la carte, ne sélectionnez rien.',
    'map_field_value'               => 'Field value',
    'map_field_mapped_to'           => 'Mapped to',
    'map_do_not_map'                => '(do not map)',
    'map_submit'                    => 'Start the import',

    // map things.
    'column__ignore'                => '(ignorer cette colonne)',
    'column_account-iban'           => 'Compte d’actif (IBAN)',
    'column_account-id'             => 'Compte d\'actif (ID correspondant à Firefly)',
    'column_account-name'           => 'Compte d’actif (nom)',
    'column_amount'                 => 'Montant',
    'column_amount-comma-separated' => 'Montant (virgule comme séparateur décimal)',
    'column_bill-id'                => 'Facture (ID correspondant à Firefly)',
    'column_bill-name'              => 'Nom de la facture',
    'column_budget-id'              => 'Budget (ID correspondant à Firefly)',
    'column_budget-name'            => 'Nom du budget',
    'column_category-id'            => 'Catégorie (ID correspondant à Firefly)',
    'column_category-name'          => 'Nom de la catégorie',
    'column_currency-code'          => 'Code des monnaies (<a href="https://fr. wikipedia. org/wiki/ISO_4217">ISO 4217</a>)',
    'column_currency-id'            => 'Devise (ID correspondant à Firefly)',
    'column_currency-name'          => 'Nom de la devise (correspondant à Firefly)',
    'column_currency-symbol'        => 'Symbole de la devise (correspondant à Firefly)',
    'column_date-interest'          => 'Date de calcul des intérêts',
    'column_date-book'              => 'Date de valeur de la transaction',
    'column_date-process'           => 'Date de traitement de la transaction',
    'column_date-transaction'       => 'Date',
    'column_description'            => 'Description',
    'column_opposing-iban'          => 'Compte destination(IBAN)',
    'column_opposing-id'            => 'Compte destination(ID correspondant Firefly)',
    'column_external-id'            => 'Identifiant externe',
    'column_opposing-name'          => 'Compte destination (nom)',
    'column_rabo-debet-credit'      => 'Indicateur spécifique débit/crédit à Rabobank',
    'column_ing-debet-credit'       => 'Indicateur spécifique débit/crédit à ING',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                => 'SEPA débit immédiat',
    'column_tags-comma'             => 'Tags (séparé par des virgules)',
    'column_tags-space'             => 'Tags(séparé par des espaces)',
    'column_account-number'         => 'Compte d’actif (numéro de compte)',
    'column_opposing-number'        => 'Compte destination (numéro de compte)',
];