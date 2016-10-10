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

    'import_configure_title' => '匯入設定',
    'import_configure_intro' => '這裡有一些 CSV 匯入選項。請檢查你的 CSV 檔的第一列是否包含欄位名稱，和你的日期格式是什麼。你可能需要嘗試幾次來調整正確。欄位分隔符號是通常 ","，但也可能是";"；仔細檢查這一點。',
    'import_configure_form'  => '表單',
    'header_help'            => 'CSV 檔的第一行是標題',
    'date_help'              => 'CSV 內的日期格式。請跟從<a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">這頁</a>內的格式來填寫。 系統預設能夠解析像這樣的日期： :dateExample 。',
    'delimiter_help'         => '請選擇你的檔案中所使用的欄位分隔符號。如果不肯定的話，逗號是最安全的選項。',
    'import_account_help'    => '如果你的 CSV 檔中沒有包含資產帳戶的資料，請選擇相關聯的帳戶。',
    'upload_not_writeable'   => '不能寫入檔案。灰色框內包含檔案的路徑，伺服器需要寫入該檔案的權限。請調整伺服器權限設定後再試。',

    // roles
    'column_roles_title'     => '定義欄的內容',
    'column_roles_text'      => '<p>Firefly III 猜不出每一欄中儲存了什麼資料。你必須告訴 Firefly 每一欄中有什麼資料。 下列的示範資料可以幫助你從列表中選擇正確類型。如果有欄位不能配對到有用的類型，請<a href="https://github.com/JC5/firefly-iii/issues/new">告訴我 (只有英語版本)</a>。</p><p>你的 CSV 檔中某些欄位可能已經存在於 Firefly III 的資料庫內，例如帳號名稱，或類別。如果你選擇「配對這些資料」， Firefly 會請你手動配對 CSV 檔和資料庫內的資料。這容許你微調你的匯入設定。</p>',
    'column_roles_table'     => '表格',
    'column_name'            => '欄位名稱',
    'column_example'         => '欄的示例資料',
    'column_role'            => '欄內資料的含義',
    'do_map_value'           => '配對這些資料',
    'column'                 => '欄',
    'no_example_data'        => '沒有可用的示例資料',
    'store_column_roles'     => '繼續匯入',
    'do_not_map'             => '（不要配對）',
    'map_title'              => '配對匯入了的資料到 Firefly III 的資料',
    'map_text'               => '在下表中，左邊的是在你的CSV 檔中的資料。而你現在要把這些資料配對到資料庫中的資料（如有的話）。如果沒有資料能夠進行配對，或者你不想進行配對，請選擇不進行配對。',

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
    'column_rabo-debet-credit'      => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'       => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA 貸記劃撥抵銷的帳戶',
    'column_sepa-db'                => 'SEPA Direct Debet',
    'column_tags-comma'             => 'Tags (comma separated)',
    'column_tags-space'             => 'Tags (space separated)',
    'column_account-number'         => '資產帳戶 （帳號號碼）',
    'column_opposing-number'        => '抵銷的帳戶 （帳號號碼）',
];
