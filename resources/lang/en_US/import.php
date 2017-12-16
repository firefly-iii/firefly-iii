<?php
declare(strict_types=1);


return [

    // general strings for file upload
    //    'import_index_intro'                    => 'This routine will help you import files from your bank into Firefly III. Please check out the help pages in the top right corner.',
    //    'import_index_file'                     => 'Select your file',
    //    'import_index_config'                   => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>.',
    //
    //    'import_index_start'                    => 'Start importing',
    //    'import_file'                           => 'Import a file',
    //
    //    // supported file types:
    //
    //
    //    // import configuration routine:


    // file: upload something:
    'file_upload_title'               => 'Import setup (1/4) - Upload your file',
    'file_upload_text'                => 'This routine will help you import files from your bank into Firefly III. Please check out the help pages in the top right corner.',
    'file_upload_fields'              => 'Fields',
    'file_upload_help'                => 'Select your file',
    'file_upload_config_help'         => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'file_upload_type_help'           => 'Select the type of file you will upload',
    'file_upload_submit'              => 'Upload files',

    // file: upload types
    'import_file_type_csv'            => 'CSV (comma separated values)',

    // file: initial config for CSV
    'csv_initial_title'               => 'Import setup (2/4) - Basic CSV import setup',
    'csv_initial_text'                => 'To be able to import your file correctly, please validate the options below.',
    'csv_initial_box'                 => 'Basic CSV import setup',
    'csv_initial_box_title'           => 'Basic CSV import setup options',
    'csv_initial_header_help'         => 'Check this box if the first row of your CSV file are the column titles.',
    'csv_initial_date_help'           => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'csv_initial_delimiter_help'      => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'csv_initial_import_account_help' => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'csv_initial_submit'              => 'Continue with step 3/4',

    // file: new options:
    'file_apply_rules_title'          => 'Apply rules',
    'file_apply_rules_description'    => 'Apply your rules. Note that this slows the import significantly.',
    'file_match_bills_title'          => 'Match bills',
    'file_match_bills_description'    => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // file: roles config
    'csv_roles_title'                 => 'Import setup (3/4) - Define each column\'s role',
    'csv_roles_text'                  => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'csv_roles_table'                 => 'Table',
    'csv_roles_column_name'           => 'Name of column',
    'csv_roles_column_example'        => 'Column example data',
    'csv_roles_column_role'           => 'Column data meaning',
    'csv_roles_do_map_value'          => 'Map these values',
    'csv_roles_column'                => 'Column',
    'csv_roles_no_example_data'       => 'No example data available',
    'csv_roles_submit'                => 'Continue with step 4/4',
    'csv_roles_warning'               => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',


    // map data
    'file_map_title'                  => 'Import setup (4/4) - Connect import data to Firefly III data',
    'file_map_text'                   => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'file_map_field_value'            => 'Field value',
    'file_map_field_mapped_to'        => 'Mapped to',
    'map_do_not_map'             => '(do not map)',
    'file_map_submit'                 => 'Start the import',

    // map things.
    'column__ignore'                  => '(ignore this column)',
    'column_account-iban'             => 'Asset account (IBAN)',
    'column_account-id'               => 'Asset account  ID (matching Firefly)',
    'column_account-name'             => 'Asset account (name)',
    'column_amount'                   => 'Amount',
    'column_amount_debet'             => 'Amount (debet column)',
    'column_amount_credit'            => 'Amount (credit column)',
    'column_amount-comma-separated'   => 'Amount (comma as decimal separator)',
    'column_bill-id'                  => 'Bill ID (matching Firefly)',
    'column_bill-name'                => 'Bill name',
    'column_budget-id'                => 'Budget ID (matching Firefly)',
    'column_budget-name'              => 'Budget name',
    'column_category-id'              => 'Category ID (matching Firefly)',
    'column_category-name'            => 'Category name',
    'column_currency-code'            => 'Currency code (ISO 4217)',
    'column_currency-id'              => 'Currency ID (matching Firefly)',
    'column_currency-name'            => 'Currency name (matching Firefly)',
    'column_currency-symbol'          => 'Currency symbol (matching Firefly)',
    'column_date-interest'            => 'Interest calculation date',
    'column_date-book'                => 'Transaction booking date',
    'column_date-process'             => 'Transaction process date',
    'column_date-transaction'         => 'Date',
    'column_description'              => 'Description',
    'column_opposing-iban'            => 'Opposing account (IBAN)',
    'column_opposing-id'              => 'Opposing account ID (matching Firefly)',
    'column_external-id'              => 'External ID',
    'column_opposing-name'            => 'Opposing account (name)',
    'column_rabo-debet-credit'        => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'         => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'               => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'               => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                  => 'SEPA Direct Debet',
    'column_tags-comma'               => 'Tags (comma separated)',
    'column_tags-space'               => 'Tags (space separated)',
    'column_account-number'           => 'Asset account (account number)',
    'column_opposing-number'          => 'Opposing account (account number)',

    // bunq
    'bunq_prerequisites_title'        => 'Prerequisites for an import from bunq',
    'bunq_prerequisites_text'         => 'In order to import from bunq, you need to obtain an API key. You can do this through the app.',

    // Spectre:
    'spectre_title'                   => 'Import using Spectre',
    'spectre_prerequisites_title'     => 'Prerequisites for an import using Spectre',
    'spectre_prerequisites_text'      => 'In order to import data using the Spectre API, you need to prove some secrets. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'           => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/security/edit">security page</a>.',
    'spectre_select_country_title'    => 'Select a country',
    'spectre_select_country_text'     => 'Firefly III has a large selection of banks and sites from which Spectre can download transactional data. These banks are sorted by country. Please not that there is a "Fake Country" for when you wish to test something. If you wish to import from other financial tools, please use the imaginary country called "Other financial applications". By default, Spectre only allows you to download data from fake banks. Make sure your status is "Live" on your <a href="https://www.saltedge.com/clients/dashboard">Dashboard</a> if you wish to download from real banks.',
    'spectre_select_provider_title'   => 'Select a bank',
    'spectre_select_provider_text'    => 'Spectre supports the following banks or financial services grouped under <em>:country</em>. Please pick the one you wish to import from.',
    'spectre_input_fields_title'      => 'Input mandatory fields',
    'spectre_input_fields_text'       => 'The following fields are mandated by ":provider" (from :country).',
    'spectre_instructions_english'    => 'These instructions are provided by Spectre for your convencience. They are in English:',
];