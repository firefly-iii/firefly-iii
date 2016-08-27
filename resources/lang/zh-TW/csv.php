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

    'import_configure_title' => '匯入設定',
    'import_configure_intro' => '這裡有一些 CSV 匯入選項。請檢查你的 CSV 檔的第一列是否包含欄位名稱，和你的日期格式是什麼。你可能需要嘗試幾次來調整正確。欄位分隔符號是通常 ","，但也可能是";"；仔細檢查這一點。',
    'import_configure_form'  => '表單',
    'header_help'            => 'CSV 檔的第一行是標題',
    'date_help'              => 'CSV 內的日期格式。請跟從<a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">這頁</a>內的格式來填寫。 系統預設能夠解析像這樣的日期： :dateExample 。',
    'delimiter_help'         => '請選擇你的檔案中所使用的欄位分隔符號。如果不肯定的話，逗號是最安全的選項。',
    'config_file_help'       => '請在這裡選擇你的 CSV 導入設定。如果你不知道這是什麼，請不要填寫。隨後會有説明指導。',
    'import_account_help'    => '如果你的 CSV 檔中沒有包含資產帳戶的資料，請選擇相關聯的帳戶。',
    'upload_not_writeable'   => '不能寫入檔案。灰色框內包含檔案的路徑，伺服器需要寫入該檔案的權限。請調整伺服器權限設定後再試。',

    // roles
    'column_roles_title'     => 'Define column roles',
    'column_roles_text'      => '<p>Firefly III cannot guess what data each column contains. You must tell Firefly which kinds of data to expect. The example data can guide you into picking the correct type from the dropdown. If a column cannot be matched to a useful data type, please let me know <a href="https://github.com/JC5/firefly-iii/issues/new">by creating an issue</a>.</p><p>Some values in your CSV file, such as account names or categories, may already exist in your Firefly III database. If you select "map these values" Firefly will not attempt to search for matching values itself but allow you to match the CSV values against the values in your database. This allows you to fine-tune the import.</p>',
    'column_roles_table'     => '表格',
    'column_name'            => 'Name of column',
    'column_example'         => 'Column example data',
    'column_role'            => '欄內資料的含義',
    'do_map_value'           => '對應這些值',
    'column'                 => '欄',
    'no_example_data'        => '沒有可用的示例資料',
    'store_column_roles'     => '繼續匯入',
    'do_not_map'             => '(do not map)',
    'map_title'              => '連接匯入資料到 Firefly III',
    'map_text'               => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',

    'field_value'          => 'Field value',
    'field_mapped_to'      => 'Mapped to',
    'store_column_mapping' => 'Store mapping',

    // map things.


    'column__ignore'                => '(ignore this column)',
    'column_account-iban'           => 'Asset account (IBAN)',
    'column_account-id'             => 'Asset account  ID (matching Firefly)',
    'column_account-name'           => '資產帳戶 （名稱）',
    'column_amount'                 => '金額',
    'column_amount-comma-separated' => '金額 （逗號作為小數分隔符號）',
    'column_bill-id'                => '帳單 ID （與 Firefly 匹配）',
    'column_bill-name'              => '帳單名稱',
    'column_budget-id'              => '預算 ID （與 Firefly 匹配）',
    'column_budget-name'            => '預算名稱',
    'column_category-id'            => '類別 ID （與 Firefly 匹配）',
    'column_category-name'          => 'Category name',
    'column_currency-code'          => 'Currency code (ISO 4217)',
    'column_currency-id'            => 'Currency ID (matching Firefly)',
    'column_currency-name'          => 'Currency name (matching Firefly)',
    'column_currency-symbol'        => 'Currency symbol (matching Firefly)',
    'column_date-interest'          => 'Interest calculation date',
    'column_date-book'              => 'Transaction booking date',
    'column_date-process'           => '交易處理日期',
    'column_date-transaction'       => '日期',
    'column_description'            => '描述',
    'column_opposing-iban'          => 'Opposing account (IBAN)',
    'column_opposing-id'            => 'Opposing account ID (matching Firefly)',
    'column_external-id'            => '外部 ID',
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
