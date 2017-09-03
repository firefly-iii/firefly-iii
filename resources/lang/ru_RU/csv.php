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
    'initial_title'                 => 'Импорт данных (1/3) - Подготовка к импорту CSV',
    'initial_text'                  => 'Чтобы импорт данных прошёл успешно, пожалуйста проверьте несколько параметров.',
    'initial_box'                   => 'Основные параметры импорта CSV',
    'initial_box_title'             => 'Basic CSV import setup options',
    'initial_header_help'           => 'Установите этот флажок, если первая строка CSV-файла содержит заголовки столбцов.',
    'initial_date_help'             => 'Формат даты и времени в вашем CSV-файле. Придерживайтесь формата, описанного <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">на этой</a> странице. По умолчанию дату будут анализироваться на соответствие такому формату: :dateExample.',
    'initial_delimiter_help'        => 'Выберите разделитель полей, который используется в вашем файле. Если вы не уверены, помните, что запятая - это самый безопасный вариант.',
    'initial_import_account_help'   => 'Если ваш CSV-файл НЕ СОДЕРЖИТ информацию о ваших счётах, укажите счета для всех транзакций, выбрав подходящие из выпадающего списка.',
    'initial_submit'                => 'Перейти к шагу 2/3',

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
    'map_text'                      => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'map_field_value'               => 'Field value',
    'map_field_mapped_to'           => 'Mapped to',
    'map_do_not_map'                => '(do not map)',
    'map_submit'                    => 'Start the import',

    // map things.
    'column__ignore'                => '(ignore this column)',
    'column_account-iban'           => 'Asset account (IBAN)',
    'column_account-id'             => 'Asset account  ID (matching Firefly)',
    'column_account-name'           => 'Asset account (name)',
    'column_amount'                 => 'Amount',
    'column_amount-comma-separated' => 'Amount (comma as decimal separator)',
    'column_bill-id'                => 'Bill ID (matching Firefly)',
    'column_bill-name'              => 'Bill name',
    'column_budget-id'              => 'Budget ID (matching Firefly)',
    'column_budget-name'            => 'Budget name',
    'column_category-id'            => 'Category ID (matching Firefly)',
    'column_category-name'          => 'Category name',
    'column_currency-code'          => 'Currency code (ISO 4217)',
    'column_currency-id'            => 'Currency ID (matching Firefly)',
    'column_currency-name'          => 'Currency name (matching Firefly)',
    'column_currency-symbol'        => 'Currency symbol (matching Firefly)',
    'column_date-interest'          => 'Interest calculation date',
    'column_date-book'              => 'Transaction booking date',
    'column_date-process'           => 'Transaction process date',
    'column_date-transaction'       => 'Date',
    'column_description'            => 'Description',
    'column_opposing-iban'          => 'Opposing account (IBAN)',
    'column_opposing-id'            => 'Opposing account ID (matching Firefly)',
    'column_external-id'            => 'External ID',
    'column_opposing-name'          => 'Opposing account (name)',
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
