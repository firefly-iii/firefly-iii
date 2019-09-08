<?php

/**
 * intro.php
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
    // index
    'index_intro'                                     => '歡迎來到 Firefly III 首頁。誠邀您花一點時間，看看這介紹，瞭解一下 Firefly III 如何運作。',
    'index_accounts-chart'                            => '此圖表顯示資產帳戶的目前餘額，哪些帳戶在此顯示，可在偏好設定中選擇。',
    'index_box_out_holder'                            => '這小方塊 (以及旁邊的) 給您一個財務狀況的快速概覽。',
    'index_help'                                      => '如果您需要有關頁面或表單的説明，請按此按鈕。',
    'index_outro'                                     => 'Firefly III 大部分頁面會以這樣的小介紹開始，如有問題或意見，不妨與我聯繫。祝您使用得心應手！',
    'index_sidebar-toggle'                            => '若要建立新的交易記錄、帳戶或其他內容，請使用此圖示下的選單。',
    'index_cash_account'                              => '目前為止就建立了這些帳戶。您可以用現金帳戶來追蹤現金支出，如果您喜歡。',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => '在此下拉清單中選取您喜歡的資產帳戶或負債。',
    'transactions_create_withdrawal_destination'      => '在此選取一個支出帳戶。留空即代表現金支出。',
    'transactions_create_withdrawal_foreign_currency' => '使用此欄位設定外幣及金額。',
    'transactions_create_withdrawal_more_meta'        => '這些欄位設定許多其他的中繼資料。',
    'transactions_create_withdrawal_split_add'        => '若您希望拆分交易，按此按鈕加一筆拆分',

    // transactions (deposit)
    'transactions_create_deposit_source'              => '在此自動完成的下拉清單/文字方塊中選取或鍵入支款人。留空即代表現金存款。',
    'transactions_create_deposit_destination'         => '在此選取一個資產或負債帳戶。',
    'transactions_create_deposit_foreign_currency'    => '使用此欄位設定外幣及金額。',
    'transactions_create_deposit_more_meta'           => '這些欄位設定許多其他的中繼資料。',
    'transactions_create_deposit_split_add'           => '若您希望拆分交易，按此按鈕加一筆拆分',

    // transactions (transfer)
    'transactions_create_transfer_source'             => '在此選取來源資產帳戶。',
    'transactions_create_transfer_destination'        => '在此選取目標資產帳戶。',
    'transactions_create_transfer_foreign_currency'   => '使用此欄位設定外幣及金額。',
    'transactions_create_transfer_more_meta'          => '這些欄位設定許多其他的中繼資料。',
    'transactions_create_transfer_split_add'          => '若您希望拆分交易，按此按鈕加一筆拆分',

    // create account:
    'accounts_create_iban'                            => '帳戶若設定有效的 IBAN，有助日後匯入資料。',
    'accounts_create_asset_opening_balance'           => '資產帳戶可以設定一個 "初始餘額"，表示此帳戶在 Firefly III 中開始時的紀錄。',
    'accounts_create_asset_currency'                  => 'Fireflly III 支援多種貨幣。資產帳戶有一種主要貨幣，須在此設定。',
    'accounts_create_asset_virtual'                   => '有時，您或會想給予帳戶一個虛擬額度：即在實際餘額之上加減一個定額。',

    // budgets index
    'budgets_index_intro'                             => '預算用來管理您的財務，是 Firefly III 的
核心功能之一。',
    'budgets_index_set_budget'                        => '設定每個時期的總預算，讓 Firefly III 能告訴您，所有可用的錢是否都已設定預算。',
    'budgets_index_see_expenses_bar'                  => '消費金額會慢慢地填滿這個橫條。',
    'budgets_index_navigate_periods'                  => '往前後不同時期，輕鬆預先設定預算。',
    'budgets_index_new_budget'                        => '隨意建立新預算。',
    'budgets_index_list_of_budgets'                   => '在此表上設定每個預算的金額，查看表現如何。',
    'budgets_index_outro'                             => '要瞭解有關預算的詳細資訊，請查看右上角的説明圖示。',

    // reports (index)
    'reports_index_intro'                             => '從這些報表洞察您的詳細財務狀況。',
    'reports_index_inputReportType'                   => '挑一種報表。查看說明頁面瞭解各報表展示的內容。',
    'reports_index_inputAccountsSelect'               => '您可以根據需要排除或包括資產帳戶。',
    'reports_index_inputDateRange'                    => '所選日期範圍完全由您決定：從1天到10年不等。',
    'reports_index_extra-options-box'                 => '根據您選擇的報表，您可以在此處選擇額外的篩選標準和選項。更改報表類型時，請查看此區塊。',

    // reports (reports)
    'reports_report_default_intro'                    => '這份報表可快速全面概覽您的個人財務狀況。如有未羅列的項目，歡迎與我聯繫！',
    'reports_report_audit_intro'                      => '這份報表可讓您洞悉資產帳戶的詳細狀況。',
    'reports_report_audit_optionsBox'                 => '在這些選取方塊勾選您感興趣想顯示的欄。',

    'reports_report_category_intro'                  => '此報表可讓您洞悉一或多個分類的詳細狀況。',
    'reports_report_category_pieCharts'              => '這些圖表可讓您洞悉按分類或帳戶的收支詳細狀況。',
    'reports_report_category_incomeAndExpensesChart' => '此圖表按分類顯示您的收支。',

    'reports_report_tag_intro'                  => '此報表可讓您洞悉一或多個標籤的詳細狀況。',
    'reports_report_tag_pieCharts'              => '這些圖表可讓您洞悉按標籤、帳戶、分類或預算的收支詳細狀況。',
    'reports_report_tag_incomeAndExpensesChart' => '此圖表按標籤顯示您的收支。',

    'reports_report_budget_intro'                             => '此報表可讓您洞悉一或多個預算的詳細狀況。',
    'reports_report_budget_pieCharts'                         => '這些圖表可讓您洞悉按預算或帳戶的支出詳細狀況。',
    'reports_report_budget_incomeAndExpensesChart'            => '此圖表按預算顯示您的支出。',

    // create transaction
    'transactions_create_switch_box'                          => '使用這些按鈕可以快速切換要保存的交易類型。',
    'transactions_create_ffInput_category'                    => '此欄位自由輸入，會顯示先前已建立分類的建議。',
    'transactions_create_withdrawal_ffInput_budget'           => '將您的提款連結至預算，以利財務管控。',
    'transactions_create_withdrawal_currency_dropdown_amount' => '在此下拉清單為提款選取另一種貨幣。',
    'transactions_create_deposit_currency_dropdown_amount'    => '在此下拉清單為存款選取另一種貨幣。',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => '選擇一個小豬撲滿，並將此轉帳連結到您的儲蓄。',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => '此欄位顯示您在每個小豬撲滿中保存了多少。',
    'piggy-banks_index_button'                                => '進度橫條旁有兩個按鈕 (+ 和-)，可以在每個小豬撲滿中加減金額。',
    'piggy-banks_index_accountStatus'                         => '有小豬撲滿的資產帳戶，會在此表格中列出狀態。',

    // create piggy
    'piggy-banks_create_name'                                 => '您的目標為何？一張新沙發、一台相機，還是應急備用？',
    'piggy-banks_create_date'                                 => '您可以設定小豬撲滿的目標日期或期限。',

    // show piggy
    'piggy-banks_show_piggyChart'                             => '此圖表顯示小豬撲滿的歷程紀錄。',
    'piggy-banks_show_piggyDetails'                           => '關於您的小豬撲滿的一些細節',
    'piggy-banks_show_piggyEvents'                            => '過去的金額加減會在此列出。',

    // bill index
    'bills_index_rules'                                       => '在此可檢視此帳單是否觸及某些規則',
    'bills_index_paid_in_period'                              => '此欄位表示上次支付帳單的時間。',
    'bills_index_expected_in_period'                          => '每張帳單是否及何時下次到期，會在此欄位顯示。',

    // show bill
    'bills_show_billInfo'                                     => '此表格顯示了有關該帳單的一般資訊。',
    'bills_show_billButtons'                                  => '使用此按鈕可以重新掃描舊交易記錄，以便將其與此帳單配對。',
    'bills_show_billChart'                                    => '此圖表顯示與此帳單連結的交易記錄。',

    // create bill
    'bills_create_intro'                                      => '使用帳單來追蹤您每段時期到期的金額，比如是租金、保險或抵押貸款等支出。',
    'bills_create_name'                                       => '訂下一個顧名思義的名稱，如 "租金" 或 "健康保險"。',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => '選擇此帳單的最小和最大金額。',
    'bills_create_repeat_freq_holder'                         => '大部分帳單每月重複一次，但頻率可在此另設。',
    'bills_create_skip_holder'                                => '如果帳單每兩週重複一次，"略過" 欄位則應設為 "1"，以便每隔一週跳一次。',

    // rules index
    'rules_index_intro'                                       => 'Firefly III 讓您管理一些規則，在建立或編輯交易時就會神乎其技地套用。',
    'rules_index_new_rule_group'                              => '您可以將規則整併為群組，以便於管理。',
    'rules_index_new_rule'                                    => '規則多寡，隨您喜歡。',
    'rules_index_prio_buttons'                                => '排序方式不拘。',
    'rules_index_test_buttons'                                => '您可以測試或套用規則至現有交易。',
    'rules_index_rule-triggers'                               => '規則的 "觸發器" 和 "動作" 可拖放排序。',
    'rules_index_outro'                                       => '記得按下右上角的 (?) 圖示查看説明頁面！',

    // create rule:
    'rules_create_mandatory'                                  => '訂下一個適切描述的標題，並設定規則觸發的時機。',
    'rules_create_ruletriggerholder'                          => '觸發器隨意增加，惟需謹記，必須符合<strong>所有</strong>觸發器，動作才會觸發。',
    'rules_create_test_rule_triggers'                         => '使用此按鈕可以查看哪些交易記錄將配對您的規則。',
    'rules_create_actions'                                    => '隨您設定多少動作。',

    // preferences
    'preferences_index_tabs'                                  => '這些標籤頁尚有更多可用選項。',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III 支援多種貨幣，可在此頁變更。',
    'currencies_index_default'                                => 'Firefly III 有一種預設貨幣。',
    'currencies_index_buttons'                                => '使用這些按鈕可以更改預設貨幣或啟用其他貨幣。',

    // create currency
    'currencies_create_code'                                  => '此應為 ISO 標準代碼 (新貨幣代碼可線上搜尋)。',
];
