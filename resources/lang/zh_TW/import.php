<?php

/**
 * import.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => '匯入資料到 Firefly III',
    'prerequisites_breadcrumb_fake'       => '虛擬匯入提供者的先決條件',
    'prerequisites_breadcrumb_spectre'    => 'Spectre 的先決條件',
    'job_configuration_breadcrumb'        => '":key" 設定',
    'job_status_breadcrumb'               => '":key" 匯入狀態',
    'disabled_for_demo_user'              => '在展示中不啟用',

    // index page:
    'general_index_intro'                 => '歡迎使用 Firefly III 的匯入流程。資料匯入 Firefly III 有幾種途徑，各以按鈕表示。',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',
    'final_csv_import'     => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that this is the last version of Firefly III that will feature a CSV importer. A separated tool is available that you should try for yourself: <a href="https://github.com/firefly-iii/csv-importer">the Firefly III CSV importer</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => '模擬匯入',
    'button_file'                         => '匯入檔案',
    'button_spectre'                      => '使用 Spectre 匯入',

    // prerequisites box (index)
    'need_prereq_title'                   => '匯入先決條件',
    'need_prereq_intro'                   => '部分匯入方式在使用前需要您的注意。例如，可能需要特別的 API 金鑰或應用程式秘鑰，您可在此設定。圖示表示是否已滿足這些先決條件。',
    'do_prereq_fake'                      => '虛擬提供者的先決條件',
    'do_prereq_file'                      => '檔案匯入的先決條件',
    'do_prereq_spectre'                   => '使用 Spectre 匯入的先決條件',

    // prerequisites:
    'prereq_fake_title'                   => '自虛擬匯入提供者匯入的先決條件',
    'prereq_fake_text'                    => '虛擬提供者需要一個虛構 API 金鑰，長度須為 32 個字元。可選用此範例：123456789012345678901234567890AA',
    'prereq_spectre_title'                => '使用 Spectre API 匯入的先決條件',
    'prereq_spectre_text'                 => '為使用 Spectre API (v4) 匯入資料，您必須提供 Firefly III 兩個秘密數值，可於 <a href="https://www.saltedge.com/clients/profile/secrets">密鑰頁面</a> 找到。',
    'prereq_spectre_pub'                  => '同理，Spectre API 也會需要您下方看見的公鑰。若無此公鑰，服務供應商無法辨認您，請於您的 <a href="https://www.saltedge.com/clients/profile/secrets">密鑰頁面</a> 鍵入您的公鑰。',
    'callback_not_tls'                    => 'Firefly III 偵測到以下回呼 URI。您的伺服器設定似乎未接受 TLS 連接 (https)。YNAB 不會接受此 URI，您可以繼續匯入 (也許 Firefly III 是錯的)，但請留意此點。',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => '虛構 API 金鑰儲存成功！',
    'prerequisites_saved_for_spectre'     => '應用程式 ID 與密鑰已儲存！',

    // job configuration:
    'job_config_apply_rules_title'        => '工作設定 - 套用您的規則？',
    'job_config_apply_rules_text'         => '虛擬提供者一旦執行，交易就會相應套用您的規則。匯入時間或會因而延長。',
    'job_config_input'                    => '請輸入',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => '輸入專輯名稱',
    'job_config_fake_artist_text'         => '許多匯入流程都有數個必經的配置步驟。至於虛擬匯入提供者，就準備了一些古怪問題須要您回答。這條請輸入 "David Bowie" 繼續。',
    'job_config_fake_song_title'          => '輸入歌曲名稱',
    'job_config_fake_song_text'           => '請鍵入 "Golden years" 繼續虛擬匯入。',
    'job_config_fake_album_title'         => '輸入專輯名稱',
    'job_config_fake_album_text'          => '一些匯入流程半路需要額外的資料。至於虛擬匯入提供者，就準備了一些古怪問題須要您回答。請輸入 "Station to station" 繼續。',
    // job configuration form the file provider
    'job_config_file_upload_title'        => '匯入設定 (1/4) - 上傳您的檔案',
    'job_config_file_upload_text'         => '此流程協助您匯入來自銀行的檔案到 Firefly III。',
    'job_config_file_upload_help'         => '選擇您的檔案，請確定檔案使用 UTF-8 編碼。',
    'job_config_file_upload_config_help'  => '如果您先前曾匯入資料到 Firefly III，您可能已有相關的設定檔，可預設組態值。至於一些銀行的檔案，其他使用者已慷慨提供他們的 <a href="https://github.com/firefly-iii/import-configurations/wiki">設定檔</a>。',
    'job_config_file_upload_type_help'    => '選擇要上傳的檔案類型',
    'job_config_file_upload_submit'       => '上傳檔案',
    'import_file_type_csv'                => 'CSV (以逗號分隔值)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => '您上傳的檔案並非以 UTF-8 或 ASCII 編碼，Firefly III 無法處理此類檔案，請使用 Notepad++ 或 Sublime 轉換您的檔案成 UTF-8 格式。',
    'job_config_uc_title'                 => '匯入設定 (2/4) - 基本檔案設定',
    'job_config_uc_text'                  => '若要正確匯入您的檔案，請驗證以下選項。',
    'job_config_uc_header_help'           => '若您的 CSV 檔案第一列為欄標題，請核選此選項。',
    'job_config_uc_date_help'             => '檔案內的日期時間格式。請依循 <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">此頁</a> 所示的格式，預設形式為：:dateExample。',
    'job_config_uc_delimiter_help'        => '選擇輸入檔案所使用的欄位分隔符號，若不確定，逗號會最為穩妥。',
    'job_config_uc_account_help'          => '若您的檔案<strong>不包含</strong>資產帳戶資訊，請在此下拉式選單選擇檔案內的交易屬於哪個帳戶。',
    'job_config_uc_apply_rules_title'     => '套用規則',
    'job_config_uc_apply_rules_text'      => '套用您的規則至每一筆匯入的交易，請注意匯入會顯著減慢。',
    'job_config_uc_specifics_title'       => '特定銀行選項',
    'job_config_uc_specifics_txt'         => '一些銀行提供的檔案，格式有欠理想，Firefly III 可以自動修復這些問題。如果您的銀行提供了不佳的檔案，又沒有在此列出，請到 GitHub 開新報告。',
    'job_config_uc_submit'                => '繼續',
    'invalid_import_account'              => '您選擇了匯入到無效的帳戶。',
    'import_liability_select'             => '債務',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => '選擇您的登入',
    'job_config_spectre_login_text'       => 'Firefly III 在您的 Spectre 帳戶找到 :count 個現存登入，匯入時您想使用何者？',
    'spectre_login_status_active'         => '啟用',
    'spectre_login_status_inactive'       => '未啟用',
    'spectre_login_status_disabled'       => '停用',
    'spectre_login_new_login'             => '登入其他銀行，或使用不同的憑證登入。',
    'job_config_spectre_accounts_title'   => '選擇欲匯入的帳戶',
    'job_config_spectre_accounts_text'    => '您已選擇 ":name" (:country)。您在此提供者有 :count 個帳戶可用，請選擇這些交易要儲存到 Firefly III 哪些資產帳戶。記得 Firefly III 與 ":name" 帳戶兩者均需使用相同貨幣，才能匯入資料。',
    'spectre_do_not_import'               => '(不匯入)',
    'spectre_no_mapping'                  => '您似乎沒有選擇任何欲匯入的帳戶。',
    'imported_from_account'               => '匯入自 ":account"',
    'spectre_account_with_number'         => '帳戶 :number',
    'job_config_spectre_apply_rules'      => '套用規則',
    'job_config_spectre_apply_rules_text' => '您的規則預設會套用至此匯入流程中所建立的交易。若您不希望如此，請取消選取此核選方塊。',

    // job configuration for bunq:
    'should_download_config'              => '您該下載此工作的 <a href=":route">設定檔</a>，好方便日後再匯入。',
    'share_config_file'                   => '如果您是從公共銀行匯入資料，您應該 <a href="https://github.com/firefly-iii/import-configurations/wiki">分享您的設定檔</a> 好方便其他使用者匯入他們的資料。分享您的設定檔並不會暴露您的財務細節。',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => '國際銀行帳戶號碼 (IBAN)',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => '狀態',
    'spectre_extra_key_card_type'          => '卡類',
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
    'spectre_extra_key_cards'              => '卡',
    'spectre_extra_key_units'              => '單位',
    'spectre_extra_key_unit_price'         => '單價',
    'spectre_extra_key_transactions_count' => '交易數',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => '匯入設定 (4/4) - 連接匯入資料與 Firefly III 資料',
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
    'status_fatal_more'               => '這一 (也許語焉不明的) 錯誤訊息有記錄檔補充， 可在硬碟或執行 Firefly III 的 Docker 容器中找到。',
    'status_finished_title'           => '匯入完成',
    'status_finished_text'            => '匯入已完成。',
    'finished_with_errors'            => '執行匯入時有些錯誤，請仔細複審。',
    'unknown_import_result'           => '未知的匯入結果',
    'result_no_transactions'          => '沒有匯入任何交易紀錄。也許是全部均為重複紀錄，或無紀錄可供匯入，日誌檔或會說明原委。若您定期匯入資料，這也是不出奇的。',
    'result_one_transaction'          => '僅匯入了 1 筆交易，並儲存在標籤 <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> 下，可進一步再檢視。',
    'result_many_transactions'        => 'Firefly III 已匯入 :count 筆交易，並儲存在標籤 <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> 下，可進一步再檢視。',

    // general errors and warnings:
    'bad_job_status'                  => '如要存取此頁，您的匯入工作不能有 ":status" 狀態。',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];
