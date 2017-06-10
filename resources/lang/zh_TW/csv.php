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
    'map_title'                => '配對匯入了的資料到 Firefly III 的資料',
    'map_text'                 => '在下表中，左邊的是在你的CSV 檔中的資料。而你現在要把這些資料配對到資料庫中的資料（如有的話）。如果沒有資料能夠進行配對，或者你不想進行配對，請選擇不進行配對。',

    'field_value'          => '欄位值',
    'field_mapped_to'      => '配對到',
    'store_column_mapping' => '存儲配對',

    // map things.


    'column__ignore'                => '（忽略此欄）',
    'column_account-iban'           => '資產帳戶 (IBAN)',
    'column_account-id'             => '資產帳戶 ID （與 Firefly 匹配）',
    'column_account-name'           => '資產帳戶 （名稱）',
    'column_amount'                 => '金額',
    'column_amount-comma-separated' => '金額 （逗號作為小數分隔符號）',
    'column_bill-id'                => '帳單 ID （與 Firefly 匹配）',
    'column_bill-name'              => '帳單名稱',
    'column_budget-id'              => '預算 ID （與 Firefly 匹配）',
    'column_budget-name'            => '預算名稱',
    'column_category-id'            => '類別 ID （與 Firefly 匹配）',
    'column_category-name'          => '類別名稱',
    'column_currency-code'          => '貨幣代碼 （ISO 4217）',
    'column_currency-id'            => '貨幣 ID （與 Firefly 匹配）',
    'column_currency-name'          => '貨幣名稱（與 Firefly 匹配）',
    'column_currency-symbol'        => '貨幣符號 （與 Firefly 匹配）',
    'column_date-interest'          => '利息計算日',
    'column_date-book'              => 'Transaction booking date',
    'column_date-process'           => '交易處理日期',
    'column_date-transaction'       => '日期',
    'column_description'            => '描述',
    'column_opposing-iban'          => '抵銷的帳戶 (IBAN)',
    'column_opposing-id'            => '抵銷的帳戶 ID （與 Firefly 匹配）',
    'column_external-id'            => '外部 ID',
    'column_opposing-name'          => '抵銷的帳戶 （名稱）',
    'column_rabo-debet-credit'      => '荷蘭合作銀行獨有的借記/貸記指標',
    'column_ing-debet-credit'       => 'ING 集團獨有的借記/貸記指標',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA 貸記劃撥抵銷的帳戶',
    'column_sepa-db'                => 'SEPA 直接付款',
    'column_tags-comma'             => '標籤 （逗號分隔）',
    'column_tags-space'             => '標籤 （空格分隔）',
    'column_account-number'         => '資產帳戶 （帳號號碼）',
    'column_opposing-number'        => '抵銷的帳戶 （帳號號碼）',
];