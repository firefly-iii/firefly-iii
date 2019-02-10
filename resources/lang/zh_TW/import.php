<?php

/**
 * import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => '匯入資料到 Firefly III',
    'prerequisites_breadcrumb_fake'       => '假匯入供應商的先決條件',
    'prerequisites_breadcrumb_spectre'    => 'Spectre 的先決條件',
    'prerequisites_breadcrumb_bunq'       => 'bunq 的先決條件',
    'prerequisites_breadcrumb_ynab'       => 'YNAB 的先決條件',
    'job_configuration_breadcrumb'        => '":key" 設定',
    'job_status_breadcrumb'               => '":key" 匯入狀態',
    'disabled_for_demo_user'              => '在展示中不啟用',

    // index page:
    'general_index_intro'                 => '歡迎來到 Firefly III 的匯入例行。有幾種方法可以將資料匯入 Firefly III 中，在此以按鈕表示。',

    // import provider strings (index):
    'button_fake'                         => '假造匯入',
    'button_file'                         => '匯入檔案',
    'button_bunq'                         => '自 bunq 匯入',
    'button_spectre'                      => '自 Spectre 匯入',
    'button_plaid'                        => '使用 Plait 匯入',
    'button_yodlee'                       => '使用 Yodlee 匯入',
    'button_quovo'                        => '使用 Quovo 匯入',
    'button_ynab'                         => '自 You Need A Budget 匯入',
    'button_fints'                        => '使用 FinTS 匯入',


    // prerequisites box (index)
    'need_prereq_title'                   => '匯入先決條件',
    'need_prereq_intro'                   => '部分匯入方式您得先在使用前注意一下。比方說，他們可能需要特別的串接秘鑰或應用程式金鑰，您可在此設定。此圖示表示所屬的先決條件已經媒合。',
    'do_prereq_fake'                      => '假匯入供應商的先決條件',
    'do_prereq_file'                      => '檔案匯入的先決條件',
    'do_prereq_bunq'                      => '從 bunq 匯入的先決條件',
    'do_prereq_spectre'                   => '使用 Spectre 匯入的先決條件',
    'do_prereq_plaid'                     => '使用 Plaid 匯入的先決條件',
    'do_prereq_yodlee'                    => '使用 Yodlee 匯入的先決條件',
    'do_prereq_quovo'                     => '使用 Quovo 匯入的先決條件',
    'do_prereq_ynab'                      => '從 YNAB 匯入的先決條件',

    // prerequisites:
    'prereq_fake_title'                   => '自假的匯入供應商匯入的先決條件',
    'prereq_fake_text'                    => '這個假的供應商需要一個假的 API 金鑰，必須是32個字元長。您可以使用此：12446809901236890123690124444466990aa',
    'prereq_spectre_title'                => '使用 Spectre API 匯入的先決條件',
    'prereq_spectre_text'                 => '為使用 Spectre API (v4) 匯入資料，您必須提供 Firefly III 兩個秘密數值，可於 <a href="https://www.saltedge.com/clients/profile/secrets">密鑰頁面</a> 找到。',
    'prereq_spectre_pub'                  => '同理，Spectre API 也會需要您下方看見的公鑰。若無此公鑰，服務供應商無法辨認您，請於您的 <a href="https://www.saltedge.com/clients/profile/secrets">密鑰頁面</a> 鍵入您的公鑰。',
    'prereq_bunq_title'                   => '從 bunq 匯入的先決條件',
    'prereq_bunq_text'                    => '為自 bunq 匯入，您需要獲得一組 API 金鑰，您可以從應用程式著手。請注意自 bunq 匯入的功能仍是測試版本，僅在沙盒 API 內完成測試而已。',
    'prereq_bunq_ip'                      => 'bunq 需要您的對外 IP 位址。Firefly III 已嘗試使用 <a href="https://www.ipify.org/">ipify 服務</a> 自動填入，請確認此 IP 係正確的，否則匯入將失敗。',
    'prereq_ynab_title'                   => '從 YNAB 匯入的先決條件',
    'prereq_ynab_text'                    => '為了能夠從 YNAB 下載交易，請在您的 <a href="https://app.youneedabudget.com/settings/developer"> 開發人員設置頁 </a> 上建立一個新的應用程式，並在此頁面上輸入客戶端 ID 和密碼。',
    'prereq_ynab_redirect'                => '若要完成設定，前往以下位於 <a href="https://app.youneedabudget.com/settings/developer">開發者設定頁面</a> 中 "Redirect URI(s)" 的網址。',
    'callback_not_tls'                    => 'Firefly III 偵測到以下回呼 URI。您的伺服器似乎沒有設定成 TLS-連接 (HTTP)。YNAB 不會接受此 URI，你可以繼續匯入 (因為 Firefly III 可能是錯的)，但請記住這一點。',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => '假 API 金鑰存儲成功！',
    'prerequisites_saved_for_spectre'     => '應用程式 ID 與密鑰已儲存！',
    'prerequisites_saved_for_bunq'        => 'API 金鑰與 IP 已儲存！',
    'prerequisites_saved_for_ynab'        => 'YNAB 客戶 ID 與密鑰已儲存！',

    // job configuration:
    'job_config_apply_rules_title'        => '工作設定 - 套用您的規則？',
    'job_config_apply_rules_text'         => '一旦假供應商執行，您的規則可用於交易。這將為匯入增加時間。',
    'job_config_input'                    => '您的輸入',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => '輸入專輯名稱',
    'job_config_fake_artist_text'         => '許多匯入慣常程序都有幾個必須經過的配置步驟。在假匯入供應商的情況下，你必須回答一些奇怪的問題。在這種情況下，請輸入 "David Bowie" 繼續。',
    'job_config_fake_song_title'          => '輸入歌曲名稱',
    'job_config_fake_song_text'           => '請鍵入 "Golden years" 以繼續假匯入。',
    'job_config_fake_album_title'         => '輸入專輯名稱',
    'job_config_fake_album_text'          => '某些匯入慣常程序在匯入過程中需要額外的資料。在假匯入供應商的情況下，你必須回答一些奇怪的問題。請輸入 "Station to station" 繼續。',
    // job configuration form the file provider
    'job_config_file_upload_title'        => '匯入設定 (1/4) - 上傳您的檔案',
    'job_config_file_upload_text'         => '此慣常程序將協助您從您銀行將檔案匯入 Firefly III。',
    'job_config_file_upload_help'         => '選擇您的檔案，請確定檔案是 UTF-8 編碼。',
    'job_config_file_upload_config_help'  => '如果您之前已匯入過檔案至 Firefly III，您可能已有可提供預設值的設定檔案。就部分銀行，其他使用者業已慷慨地提供了他們的 <a href="https://github.com/firefly-iii/import-configurations/wiki">設定檔</a>。',
    'job_config_file_upload_type_help'    => '選擇要上傳的檔案類型',
    'job_config_file_upload_submit'       => '上傳檔案',
    'import_file_type_csv'                => 'CSV (以逗號分隔值)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => '您上傳的檔案並非以 UTF-8 或 ASCII 編碼，Firefly III 無法處理此類檔案，請使用 Notepad++ 或 Sublime 轉換您的檔案成 UTF-8 格式。',
    'job_config_uc_title'                 => '匯入設定 (2/4) - 基本檔案設定',
    'job_config_uc_text'                  => '若要正確匯入您的檔案，請驗證以下選項。',
    'job_config_uc_header_help'           => '若您的 CSV 檔案第一列均為欄位標題，請核選此選項。',
    'job_config_uc_date_help'             => '您檔案內的日期格式。請依循 <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">本頁</a> 所示的格式，預設值將以 :dateExample 形式呈現日期。',
    'job_config_uc_delimiter_help'        => '選擇您檔案所使用的欄位分隔符號，若不確定，逗號係最為安全的選項。',
    'job_config_uc_account_help'          => '若您的檔案不包含資產帳戶的資訊，使用此下拉式選單選擇此檔案內交易所屬的帳戶。',
    'job_config_uc_apply_rules_title'     => '套用規則',
    'job_config_uc_apply_rules_text'      => '套用規則至每一個匯入的交易，請注意此功能會顯著地降低匯入速度。',
    'job_config_uc_specifics_title'       => '特定銀行選項',
    'job_config_uc_specifics_txt'         => '部分銀行提供格式殘不佳的檔案，Firefly III 可以自動修復這個問題。如果銀行提供了不佳的檔案，又沒有列在這邊，請至 GitHub 開啟新的討論。',
    'job_config_uc_submit'                => '繼續',
    'invalid_import_account'              => '您選擇了一個無效帳號來匯入。',
    'import_liability_select'             => '債務',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => '選擇您的登入',
    'job_config_spectre_login_text'       => 'Firefly III 已在您的 Spectre 帳戶找到 :count 筆既存登入，哪一個是您想要匯入的呢？',
    'spectre_login_status_active'         => '啟用',
    'spectre_login_status_inactive'       => '未啟用',
    'spectre_login_status_disabled'       => '停用',
    'spectre_login_new_login'             => '使用其他銀行登入，或其中一間具有不同憑證的銀行。',
    'job_config_spectre_accounts_title'   => '選擇欲匯入的帳戶',
    'job_config_spectre_accounts_text'    => '您以選擇 ":name" (:country)。您在這個供應商有 :count 個可用帳戶，請在 Firefly III 的資產帳戶中選擇這些交易應被儲存的帳戶。請記得，若要匯入資料，Firefly III 與 ":name"-帳戶兩者均需使用相同貨幣。',
    'spectre_do_not_import'               => '(不匯入)',
    'spectre_no_mapping'                  => '您似乎沒有選擇任何欲匯入的帳戶。',
    'imported_from_account'               => '已自 ":account" 匯入',
    'spectre_account_with_number'         => '帳戶 :number',
    'job_config_spectre_apply_rules'      => '套用規則',
    'job_config_spectre_apply_rules_text' => '預設下，您的規則會被套用至此次匯入慣常程序中所建立的交易。若您不希望如此，請取消選取此核選方塊。',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'bunq 帳戶',
    'job_config_bunq_accounts_text'       => '這些是與您 bunq 帳戶關聯的帳戶，請選擇您所欲匯入的帳戶以及其必須匯入的交易。',
    'bunq_no_mapping'                     => '您似乎沒有選擇任何帳戶。',
    'should_download_config'              => '您應該為此工作下載 <a href=":route">設定檔</a>，可更俾利為來匯入。',
    'share_config_file'                   => '如果您已自公有銀行匯入資料，您應該 <a href="https://github.com/firefly-iii/import-configurations/wiki">分享您的設定檔</a> 俾利其他使用者匯入他們的資料。分享您的設定檔並不會暴露您的財務細節。',
    'job_config_bunq_apply_rules'         => '套用規則',
    'job_config_bunq_apply_rules_text'    => '預設下，您的規則會被套用至此次匯入慣常程序中所建立的交易。若您不希望如此，請取消選取此核選方塊。',
    'bunq_savings_goal'                   => '儲蓄目標：:amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => '已關閉 bunq 帳號',

    'ynab_account_closed'                  => '帳戶已關閉！',
    'ynab_account_deleted'                 => '帳戶已刪除！',
    'ynab_account_type_savings'            => '儲蓄帳戶',
    'ynab_account_type_checking'           => '支票帳戶',
    'ynab_account_type_cash'               => '現金帳戶',
    'ynab_account_type_creditCard'         => '信用卡',
    'ynab_account_type_lineOfCredit'       => '信用額度',
    'ynab_account_type_otherAsset'         => '其他資產帳戶',
    'ynab_account_type_otherLiability'     => '其他債務',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => '商業帳戶',
    'ynab_account_type_investmentAccount'  => '投資帳戶',
    'ynab_account_type_mortgage'           => '抵押',
    'ynab_do_not_import'                   => '(不導入)',
    'job_config_ynab_apply_rules'          => '套用規則',
    'job_config_ynab_apply_rules_text'     => '預設下，您的規則會被套用至此次匯入慣常程序中所建立的交易。若您不希望如此，請取消選取此核選方塊。',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => '選擇您的預算',
    'job_config_ynab_select_budgets_text'  => '您有 :count 筆儲存於 YNAB 的預算，請選擇以供 Firefly III 匯入其中交易紀錄。',
    'job_config_ynab_no_budgets'           => '沒有可被匯入的預算。',
    'ynab_no_mapping'                      => '您似乎沒有選擇任何欲匯入的帳戶。',
    'job_config_ynab_bad_currency'         => '您無法自以下預算匯入，因為您沒有與這些預算使用相同貨幣的帳戶。',
    'job_config_ynab_accounts_title'       => '選擇帳戶',
    'job_config_ynab_accounts_text'        => '以下有您可用於此預算的帳戶，請選擇您欲匯入的帳戶以及交易資料儲存的地方。',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => '國際銀行帳戶號碼 (IBAN)',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => '狀態',
    'spectre_extra_key_card_type'          => '卡片種類',
    'spectre_extra_key_account_name'       => '帳戶名稱',
    'spectre_extra_key_client_name'        => '客戶名稱',
    'spectre_extra_key_account_number'     => '帳戶號碼',
    'spectre_extra_key_blocked_amount'     => '封鎖的金額',
    'spectre_extra_key_available_amount'   => '可用金額',
    'spectre_extra_key_credit_limit'       => '信用額度',
    'spectre_extra_key_interest_rate'      => '利率',
    'spectre_extra_key_expiry_date'        => '到期日',
    'spectre_extra_key_open_date'          => '開始日期',
    'spectre_extra_key_current_time'       => '目前時間',
    'spectre_extra_key_current_date'       => '目前日期',
    'spectre_extra_key_cards'              => '卡片',
    'spectre_extra_key_units'              => '單位',
    'spectre_extra_key_unit_price'         => '單價',
    'spectre_extra_key_transactions_count' => '交易數',

    //job configuration for finTS
    'fints_connection_failed'              => '嘗試連接至您的銀行時發生1個錯誤，請確定您所有鍵入的資料均正確。原始錯誤訊息：:originalError',

    'job_config_fints_url_help'       => '例如 https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => '對多數銀行而言，這是你的帳號。',
    'job_config_fints_port_help'      => '預設埠號為 443。',
    'job_config_fints_account_help'   => '選擇您欲匯入交易的銀行帳戶。',
    'job_config_local_account_help'   => '選擇對應您上方所選銀行帳戶的 Firefly III 帳戶。',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => '在 ING 匯出中建立更好的描述',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => '刪除 SNS / Volksbank 匯出檔案中的英文引號',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => '修正 ABN AMRO 檔案中的潛在問題',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => '修正 Rabobank 檔案中的潛在問題',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => '修正 PC 檔案中的潛在問題',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => '匯入設定 (3/4) - 定義每個欄的角色',
    'job_config_roles_text'           => '在您 CSV 檔案中的每個欄均含某些資料，請說明係核種資料供匯入器參照。用以「映射」資料的選項，即您將連結每個欄中的資料至您資料庫的一個值。一個常見的「已映射」的欄，是包含 IBAN 相對帳戶的欄，這便可輕易地媒合至您資料庫既存的 IBAN 帳戶。',
    'job_config_roles_submit'         => '繼續',
    'job_config_roles_column_name'    => '欄名',
    'job_config_roles_column_example' => '欄的範例資料',
    'job_config_roles_column_role'    => '欄資料涵義',
    'job_config_roles_do_map_value'   => '映射這些數值',
    'job_config_roles_no_example'     => '無範例資料可用',
    'job_config_roles_fa_warning'     => '如果您將一個欄標記為外幣金額、您亦須設定該欄外幣為何。',
    'job_config_roles_rwarning'       => '請至少將一個欄標示為金額-欄，亦建議為描述、日期與對應帳戶選擇欄。',
    'job_config_roles_colum_count'    => '欄',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => '匯入設定 (4/) - 連接匯入資料與 Firefly III 資料',
    'job_config_map_text'             => '在下方的表格中，左邊值顯示在您上傳的檔案中所找到的資訊，而映射這些值是您當前的任務。如果可能，請映射至呈現在您資料庫既有的值，Firefly III 會依此映射。若無可供映射的值，或您不希望映射特定值，請別選取。',
    'job_config_map_nothing'          => '您檔案中並無可映射至既有數值的資料，請點選 "開始匯入" 以繼續。',
    'job_config_field_value'          => '欄位數值',
    'job_config_field_mapped'         => '映射至',
    'map_do_not_map'                  => '(不映射)',
    'job_config_map_submit'           => '開始匯入',


    // import status page:
    'import_with_key'                 => '以鍵 \':key\' 匯入',
    'status_wait_title'               => '請稍待……',
    'status_wait_text'                => '此方塊過一會兒會消失。',
    'status_running_title'            => '匯入正在執行',
    'status_job_running'              => '請稍候，執行匯入…',
    'status_job_storing'              => '請稍候，儲存資料…',
    'status_job_rules'                => '請稍候，執行規則…',
    'status_fatal_title'              => '重大錯誤',
    'status_fatal_text'               => '此次匯入遇到了無法回復的錯誤，抱歉！',
    'status_fatal_more'               => '此一訊息 (可能非常加密) 是由日誌檔所補完，您可在硬碟內或您運行的 Firefly III 空間中找到日誌檔。',
    'status_finished_title'           => '匯入完成',
    'status_finished_text'            => '匯入已完成',
    'finished_with_errors'            => '執行匯入時有些錯誤，請仔細複審。',
    'unknown_import_result'           => '未知的匯入結果',
    'result_no_transactions'          => '沒有匯入任何交易紀錄，或許是因為均為重複記錄或無紀錄可供匯入。日誌檔也許能告訴你來龍去脈，若您定期匯入資料，這是正常的。',
    'result_one_transaction'          => '僅有1筆交易被匯入，並儲存在 <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>，您之後可進一步檢視。',
    'result_many_transactions'        => 'Firefly III 已匯入 :count 筆交易，並儲存 <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> 標籤下，您之後可進一步檢視。',


    // general errors and warnings:
    'bad_job_status'                  => '欲通行此頁，您的匯入工作不能有 ":status" 狀態。',

    // column roles for CSV import:
    'column__ignore'                  => '(忽略此欄)',
    'column_account-iban'             => '資產帳戶 (IBAN)',
    'column_account-id'               => '資產帳戶 ID (與 FF3 相符)',
    'column_account-name'             => '資產帳戶 (名稱)',
    'column_account-bic'              => '資產帳戶 (BIC)',
    'column_amount'                   => '金額',
    'column_amount_foreign'           => '金額 (以外幣計)',
    'column_amount_debit'             => '金額 (債務欄)',
    'column_amount_credit'            => '金額 (信用欄)',
    'column_amount_negated'           => '金額 (正負交換欄)',
    'column_amount-comma-separated'   => '金額 (以逗號作為進位分隔符號)',
    'column_bill-id'                  => '帳單 ID (與 FF3 相符)',
    'column_bill-name'                => '帳單名稱',
    'column_budget-id'                => '預算 ID (與 FF3 相符)',
    'column_budget-name'              => '預算名稱',
    'column_category-id'              => '分類 ID (與 FF3 相符)',
    'column_category-name'            => '分類名稱',
    'column_currency-code'            => '貨幣代碼 (ISO 4217)',
    'column_foreign-currency-code'    => '外幣代碼 (ISO 4217)',
    'column_currency-id'              => '貨幣 ID (與 FF3 相符)',
    'column_currency-name'            => '貨幣名稱 (與 FF3 相符)',
    'column_currency-symbol'          => '貨幣符號 (與 FF3 相符)',
    'column_date-interest'            => '利率計算日期',
    'column_date-book'                => '交易預訂日期',
    'column_date-process'             => '交易處理日期',
    'column_date-transaction'         => '日期',
    'column_date-due'                 => '交易到期日期',
    'column_date-payment'             => '交易付款日期',
    'column_date-invoice'             => '交易收據日期',
    'column_description'              => '描述',
    'column_opposing-iban'            => '對應帳戶 (IBAN)',
    'column_opposing-bic'             => '對應帳戶 (BIC)',
    'column_opposing-id'              => '對應帳戶 ID (與 FF3 相符)',
    'column_external-id'              => '外部 ID',
    'column_opposing-name'            => '對應帳戶 (名稱)',
    'column_rabo-debit-credit'        => 'Rabobank 獨有現金/信用卡指標',
    'column_ing-debit-credit'         => 'ING 獨有 現金/信用卡 指標',
    'column_generic-debit-credit'     => '通用銀行債務/信用指標',
    'column_sepa-ct-id'               => 'SEPA end-to-end Identifier',
    'column_sepa-ct-op'               => 'SEPA Opposing Account Identifier',
    'column_sepa-db'                  => 'SEPA Mandate Identifier',
    'column_sepa-cc'                  => 'SEPA Clearing Code',
    'column_sepa-ci'                  => 'SEPA Creditor Identifier',
    'column_sepa-ep'                  => 'SEPA External Purpose',
    'column_sepa-country'             => 'SEPA Country',
    'column_sepa-batch-id'            => 'SEPA Batch ID',
    'column_tags-comma'               => '標籤 (以逗號分隔)',
    'column_tags-space'               => '標籤 (以空白鍵分隔)',
    'column_account-number'           => '資產帳戶 (帳戶號碼)',
    'column_opposing-number'          => '相對帳戶 (帳戶號碼)',
    'column_note'                     => '備註',
    'column_internal-reference'       => '內部參考',

];
