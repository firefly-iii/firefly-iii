<?php

/**
 * intro.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    // index
    'index_intro'                                     => '欢迎来到 Firefly III 的首页。请花时间参观一下这个介绍，了解 Firefly III 是如何运作的。',
    'index_accounts-chart'                            => '此图表显示您的资产帐户的目前馀额，您可以在偏好设定中选择此处可见的帐户。',
    'index_box_out_holder'                            => '这个小盒子和这个旁边的盒子会提供您财务状况的快速概览。',
    'index_help'                                      => '如果您需要有关页面或表单的说明，请按此按钮。',
    'index_outro'                                     => 'Firefly III 的大多数页面将从像这样的小介绍开始，如果您有任何问题或意见，请与我联繫。请享受！',
    'index_sidebar-toggle'                            => '若要建立新的交易记录、帐户或其他内容，请使用此图示下的选单。',
    'index_cash_account'                              => '这些是迄今创建的账户。您可以使用现金账户追踪现金支出，但当然不是强制性的。',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => '从这个下拉选择您最喜欢的资产帐户或负债。',
    'transactions_create_withdrawal_destination'      => '在此选择一个费用帐户。留空即表示现金支出。',
    'transactions_create_withdrawal_foreign_currency' => '使用此字段设置外汇和数额。',
    'transactions_create_withdrawal_more_meta'        => 'Plenty of other meta data you set in these fields.',
    'transactions_create_withdrawal_split_add'        => '如果您想要拆分交易，按此按钮添加一笔拆分',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Select or type the payee in this auto-completing dropdown/textbox. Leave it empty if you want to make a cash deposit.',
    'transactions_create_deposit_destination'         => '在此选择一个资产或负债帐户。',
    'transactions_create_deposit_foreign_currency'    => '使用此字段设置外汇和数额。',
    'transactions_create_deposit_more_meta'           => 'Plenty of other meta data you set in these fields.',
    'transactions_create_deposit_split_add'           => '如果您想要拆分交易，按此按钮添加一笔拆分',

    // transactions (transfer)
    'transactions_create_transfer_source'             => '在此选择来源资产帐户。',
    'transactions_create_transfer_destination'        => '在此选择目标资产帐户。',
    'transactions_create_transfer_foreign_currency'   => '使用此字段设置外汇和数额。',
    'transactions_create_transfer_more_meta'          => 'Plenty of other meta data you set in these fields.',
    'transactions_create_transfer_split_add'          => 'If you want to split a transaction, add more splits with this button',

    // create account:
    'accounts_create_iban'                            => '给您的帐户一个有效的 IBAN，可使未来资料导入变得更容易。',
    'accounts_create_asset_opening_balance'           => '资产帐户可能有一个 "初始馀额"，表示此帐户在 Firefly III 中的纪录开始。',
    'accounts_create_asset_currency'                  => 'Fireflly III 支持多种货币。资产帐户有一种主要货币，您必须在此处设定。',
    'accounts_create_asset_virtual'                   => '有时，它可以协助赋予你的帐户一个虚拟额度：一个总是增加至实际馀额中，或自其中删减的固定金额。',

    // budgets index
    'budgets_index_intro'                             => '预算是用来管理你的财务，也是 Firefly III 的核心功能之一。',
    'budgets_index_set_budget'                        => '设定每个期间的总预算，这样 Firefly III 就可以告诉你，是否已经将所有可用的钱设定预算。',
    'budgets_index_see_expenses_bar'                  => '消费金额会慢慢地填满这个横条。',
    'budgets_index_navigate_periods'                  => '前往区间，以便提前轻鬆设定预算。',
    'budgets_index_new_budget'                        => '根据需要建立新预算。',
    'budgets_index_list_of_budgets'                   => '使用此表可以设定每个预算的金额，并查看您的情况。',
    'budgets_index_outro'                             => '要瞭解有关预算的详细资讯，请查看右上角的说明图示。',

    // reports (index)
    'reports_index_intro'                             => '使用这些报表可以获得有关您财务状况的详细洞察报告。',
    'reports_index_inputReportType'                   => '选择报表类型。查看说明页面以瞭解每个报表向您显示的内容。',
    'reports_index_inputAccountsSelect'               => '您可以根据需要排除或包括资产帐户。',
    'reports_index_inputDateRange'                    => '所选日期范围完全由您决定：从1天到10年不等。',
    'reports_index_extra-options-box'                 => '根据您选择的报表，您可以在此处选择额外的筛选标准和选项。更改报表类型时，请查看此区块。',

    // reports (reports)
    'reports_report_default_intro'                    => '这份报表将为您提供一个快速和全面的个人财务概览。如果你想看其他的东西，请不要犹豫并联繫我！',
    'reports_report_audit_intro'                      => '此报表将为您提供有关资产帐户的详细洞察报告。',
    'reports_report_audit_optionsBox'                 => '使用这些选取方块可以显示或隐藏您感兴趣的栏。',

    'reports_report_category_intro'                  => '此报表将提供您一个或多个类别洞察报告。',
    'reports_report_category_pieCharts'              => '这些图表将提供您每个类别或每个帐户中，支出和所得的洞察报告。',
    'reports_report_category_incomeAndExpensesChart' => '此图表显示您的每个类别的支出和所得。',

    'reports_report_tag_intro'                  => '此报表将提供您一个或多个标签洞察报告。',
    'reports_report_tag_pieCharts'              => '这些图表将提供您每个类别、帐户、分类或预算中，支出和所得的洞察报告。',
    'reports_report_tag_incomeAndExpensesChart' => '此图表显示您的每个标签的支出和所得。',

    'reports_report_budget_intro'                             => '此报表将提供您一个或多个预算的洞察报告。',
    'reports_report_budget_pieCharts'                         => '这些图表将提供您每个预算或每个帐户中，支出的洞察报告。',
    'reports_report_budget_incomeAndExpensesChart'            => '此图表显示您的每个预算的支出。',

    // create transaction
    'transactions_create_switch_box'                          => '使用这些按钮可以快速切换要保存的交易类型。',
    'transactions_create_ffInput_category'                    => '您可以在此栏位中随意输入，会建议您先前已建立的分类。',
    'transactions_create_withdrawal_ffInput_budget'           => '将您的提款连结至预算，以利财务管控。',
    'transactions_create_withdrawal_currency_dropdown_amount' => '当您的提款使用另一种货币时, 请使用此下拉清单。',
    'transactions_create_deposit_currency_dropdown_amount'    => '当您的存款使用另一种货币时, 请使用此下拉清单。',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => '选择一个存钱罐，并将此转帐连结到您的储蓄。',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => '此栏位显示您在每个存钱罐中存了多少。',
    'piggy-banks_index_button'                                => '此进度条旁边有两个按钮 ( + 和 - )，用于从每个存钱罐中投入或取出资金。',
    'piggy-banks_index_accountStatus'                         => '此表中列出了所有有存钱罐的资产帐户的状态。',

    // create piggy
    'piggy-banks_create_name'                                 => '你的目标是什麽？一个新沙发、一个相机、急难用金？',
    'piggy-banks_create_date'                                 => '您可以为存钱罐设定目标日期或截止日期。',

    // show piggy
    'piggy-banks_show_piggyChart'                             => '这张图表将显示这个存钱罐的历史。',
    'piggy-banks_show_piggyDetails'                           => '关于你的存钱罐的一些细节',
    'piggy-banks_show_piggyEvents'                            => '此处还列出了任何增加或删除。',

    // bill index
    'bills_index_rules'                                       => '在此可检视此帐单是否触及某些规则',
    'bills_index_paid_in_period'                              => '此栏位表示上次支付帐单的时间。',
    'bills_index_expected_in_period'                          => '如果(以及何时)下期帐单即将到期，此栏位将显示每一笔的帐单。',

    // show bill
    'bills_show_billInfo'                                     => '此表格显示了有关该帐单的一般资讯。',
    'bills_show_billButtons'                                  => '使用此按钮可以重新扫描旧交易记录，以便将其与此帐单配对。',
    'bills_show_billChart'                                    => '此图表显示与此帐单连结的交易记录。',

    // create bill
    'bills_create_intro'                                      => '使用帐单来跟踪你每个区间到期的金额。想想租金、保险或抵押贷款等支出。',
    'bills_create_name'                                       => '使用描述性名称, 如 "租金" 或 "健康保险"。',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => '选择此帐单的最小和最大金额。',
    'bills_create_repeat_freq_holder'                         => '大多数帐单每月重複一次，但你可以在这裡设定另一个频次。',
    'bills_create_skip_holder'                                => '如果帐单每2週重複一次，则应将 "略过" 栏位设定为 "1"，以便每隔一週跳一次。',

    // rules index
    'rules_index_intro'                                       => 'Firefly III 允许您管理规则，这些规则将魔幻自动地应用于您建立或编辑的任何交易。',
    'rules_index_new_rule_group'                              => '您可以将规则整合为群组，以便于管理。',
    'rules_index_new_rule'                                    => '建立任意数量的规则。',
    'rules_index_prio_buttons'                                => '以你认为合适的任何方式排序它们。',
    'rules_index_test_buttons'                                => '您可以测试规则或将其套用至现有交易。',
    'rules_index_rule-triggers'                               => '规则具有 "触发器" 和 "操作"，您可以通过拖放进行排序。',
    'rules_index_outro'                                       => '请务必使用右上角的 (?) 图示查看说明页面！',

    // create rule:
    'rules_create_mandatory'                                  => '选择一个描述性标题，并设定应触发规则的时机。',
    'rules_create_ruletriggerholder'                          => '增加任意数量的触发器，但请记住在任一动作启用前，所有触发器必须配对。',
    'rules_create_test_rule_triggers'                         => '使用此按钮可以查看哪些交易记录将配对您的规则。',
    'rules_create_actions'                                    => '设定任意数量的动作。',

    // preferences
    'preferences_index_tabs'                                  => '这些标签页后尚有更多选项可用。',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III 支持多种货币，您可以在此页面上更改。',
    'currencies_index_default'                                => 'Firefly III 有一种预设货币。',
    'currencies_index_buttons'                                => '使用这些按钮可以更改预设货币或启用其他货币。',

    // create currency
    'currencies_create_code'                                  => '此代码应符合 ISO 标准 (可 Google 您的新货币)。',
];
