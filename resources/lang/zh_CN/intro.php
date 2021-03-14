<?php

/**
 * intro.php
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
    // index
    'index_intro'                                     => '欢迎来到 Firefly III 首页，请跟随系统引导，了解 Firefly III 的运作方式。',
    'index_accounts-chart'                            => '此图表显示您资产账户的当前余额，您可以在偏好设定中选择此处可见的账户。',
    'index_box_out_holder'                            => '此区块与旁侧区块提供您财务状况的快速概览。',
    'index_help'                                      => '如果您需要有关页面或表单的说明，请点击此按钮。',
    'index_outro'                                     => 'Firefly III 的大多数页面都有类似的引导流程，如果您有任何问题或意见，请与开发者联系。感谢您选择 Firefly III。',
    'index_sidebar-toggle'                            => '若要创建新的交易、账户或其他内容，请使用此图标下的菜单。',
    'index_cash_account'                              => '这些是迄今创建的账户。您可以使用现金账户追踪现金支出，但当然不是强制性的。',

    // transactions
    'transactions_create_basic_info'                  => '输入您交易的基本信息，包括来源账户、目标账户、日期和描述。',
    'transactions_create_amount_info'                 => '输入交易金额。如有必要，这些字段会自动更新以获取外币信息。',
    'transactions_create_optional_info'               => '这些字段都是可选项，在此处添加元数据会使您的交易更有条理。',
    'transactions_create_split'                       => '如果您要拆分一笔交易，点击此按钮即可',

    // create account:
    'accounts_create_iban'                            => '为您的账户添加一个有效的 IBAN，将来可以更轻松地导入资料。',
    'accounts_create_asset_opening_balance'           => '资产账户可以使用“初始余额”表示此账户在 Firefly III 中的初始状态。',
    'accounts_create_asset_currency'                  => 'Firefly III 支持多种货币，您必须在此设定资产账户的主要货币。',
    'accounts_create_asset_virtual'                   => '它有时可以协助赋予您的账户一个虚拟额度：一个总是增加/减少实际余额的额外金额。',

    // budgets index
    'budgets_index_intro'                             => '预算可以用来管理您的财务，是 Firefly III 的核心功能之一。',
    'budgets_index_set_budget'                        => '设定每个周期的总预算，让 Firefly III 来判断您是否已经将所有可用钱财加入预算。',
    'budgets_index_see_expenses_bar'                  => '进行消费会慢慢地填满这个横条。',
    'budgets_index_navigate_periods'                  => '前往不同的周期，可以方便地提前设定预算。',
    'budgets_index_new_budget'                        => '根据需要创建新预算。',
    'budgets_index_list_of_budgets'                   => '使用此表格可以设定每个预算的金额，并查看您的使用情况。',
    'budgets_index_outro'                             => '要了解更多有关预算的信息，请查看右上角的帮助图标。',

    // reports (index)
    'reports_index_intro'                             => '使用这些报表可以详细地了解您的财务状况。',
    'reports_index_inputReportType'                   => '选择报表类型，查看帮助页面以了解每个报表向您显示的内容。',
    'reports_index_inputAccountsSelect'               => '您可以根据需要排除或包括资产账户。',
    'reports_index_inputDateRange'                    => '所选日期范围完全由您决定：从1天到10年不等。',
    'reports_index_extra-options-box'                 => '根据您选择的报表，您可以在此处选择额外的筛选标准和选项。更改报表类型时，请留意此区块。',

    // reports (reports)
    'reports_report_default_intro'                    => '这份报表将为您提供一个快速和全面的个人财务概览。如果您想看到更多的内容，欢迎联系开发者！',
    'reports_report_audit_intro'                      => '此报表可以让您详细地了解您的资产账户的情况。',
    'reports_report_audit_optionsBox'                 => '使用这些复选框可以显示或隐藏您感兴趣的列。',

    'reports_report_category_intro'                  => '此报表可以让您详细地了解一个或多个分类的情况。',
    'reports_report_category_pieCharts'              => '这些图表可以让您详细地了解每个分类或每个账户中的支出和收入情况。',
    'reports_report_category_incomeAndExpensesChart' => '此图表显示您的每个分类的支出和收入情况。',

    'reports_report_tag_intro'                  => '此报表可以让您详细地了解一个或多个标签的情况。',
    'reports_report_tag_pieCharts'              => '这些图表可以让您详细地了解每个标签、账户、分类或预算中的支出和收入情况。',
    'reports_report_tag_incomeAndExpensesChart' => '此图表显示您的每个标签的支出和收入情况。',

    'reports_report_budget_intro'                             => '此报表可以让您详细地了解一项或多项预算的情况。',
    'reports_report_budget_pieCharts'                         => '这些图表可以让您详细地了解每项预算或每个账户中的支出情况。',
    'reports_report_budget_incomeAndExpensesChart'            => '此图表显示您的每项预算的支出情况。',

    // create transaction
    'transactions_create_switch_box'                          => '使用这些按钮可以快速切换要保存的交易类型。',
    'transactions_create_ffInput_category'                    => '您可以在此随意输入，系统会自动提示您已创建的分类。',
    'transactions_create_withdrawal_ffInput_budget'           => '将您的取款关联至预算，以更好地管控财务。',
    'transactions_create_withdrawal_currency_dropdown_amount' => '当您的取款使用另一种货币时，请使用此下拉菜单。',
    'transactions_create_deposit_currency_dropdown_amount'    => '当您的存款使用另一种货币时，请使用此下拉菜单。',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => '选择一个存钱罐，并将此转账关联到您的存钱罐储蓄。',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => '此字段显示您在每个存钱罐中存了多少钱。',
    'piggy-banks_index_button'                                => '此进度条旁边有两个按钮 (+ 和 -)，用于从每个存钱罐中存入或取出资金。',
    'piggy-banks_index_accountStatus'                         => '此表格中列出了所有至少拥有一个存钱罐的资产账户的状态。',

    // create piggy
    'piggy-banks_create_name'                                 => '您的目标是什么？一张新沙发、一台相机，或是应急用金？',
    'piggy-banks_create_date'                                 => '您可以为存钱罐设定目标日期或截止日期。',

    // show piggy
    'piggy-banks_show_piggyChart'                             => '这张图表将显示这个存钱罐的历史。',
    'piggy-banks_show_piggyDetails'                           => '关于您的存钱罐的一些细节',
    'piggy-banks_show_piggyEvents'                            => '此处还列出了任何增加或删除记录。',

    // bill index
    'bills_index_rules'                                       => '在此可检视此账单是否触及某些规则',
    'bills_index_paid_in_period'                              => '此字段表示上次支付账单的时间。',
    'bills_index_expected_in_period'                          => '此字段表示每笔账单是否有下一期，以及下期账单预计何时到期。',

    // show bill
    'bills_show_billInfo'                                     => '此表格显示了有关此账单的常用信息。',
    'bills_show_billButtons'                                  => '使用此按钮可以重新扫描旧交易记录，以便将其与此账单配对。',
    'bills_show_billChart'                                    => '此图表显示与此账单关联的交易记录。',

    // create bill
    'bills_create_intro'                                      => '使用账单来追踪你每个区间要缴纳的费用，例如租金、保险或抵押贷款等支出。',
    'bills_create_name'                                       => '使用描述性名称, 如“租金”或“健康保险”。',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => '选择此账单的最小和最大金额。',
    'bills_create_repeat_freq_holder'                         => '大多数账单每月重复，但你可以在这里设定另一个频次。',
    'bills_create_skip_holder'                                => '如果账单每2周重复一次，则应将“跳过”栏位设定为“1”，以便每隔一周跳过一次。',

    // rules index
    'rules_index_intro'                                       => 'Firefly III 允许您管理规则，这些规则将自动地应用于您创建或编辑的任何交易。',
    'rules_index_new_rule_group'                              => '您可以将规则整合为组，以便于管理。',
    'rules_index_new_rule'                                    => '您可以创建任意数量的规则。',
    'rules_index_prio_buttons'                                => '以你认为合适的任何方式排序它们。',
    'rules_index_test_buttons'                                => '您可以测试规则或将其套用至现有交易。',
    'rules_index_rule-triggers'                               => '规则具有“触发条件”和“动作”，您可以通过拖放进行排序。',
    'rules_index_outro'                                       => '请务必使用右上角的问号图标查看帮助页面！',

    // create rule:
    'rules_create_mandatory'                                  => '选择一个描述性标题，并设定应触发规则的时机。',
    'rules_create_ruletriggerholder'                          => '您可以添加任意数量的触发条件，但请记住，所有触发条件必须满足才能启用动作。',
    'rules_create_test_rule_triggers'                         => '使用此按钮可以查看哪些交易记录将配对您的规则。',
    'rules_create_actions'                                    => '您可以设定任意数量的动作。',

    // preferences
    'preferences_index_tabs'                                  => '这些标签页后还有更多可用选项。',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III 支持多种货币，您可以在此页面上更改。',
    'currencies_index_default'                                => 'Firefly III 拥有一种默认货币。',
    'currencies_index_buttons'                                => '使用这些按钮可以更改默认货币或启用其他货币。',

    // create currency
    'currencies_create_code'                                  => '此代码应符合 ISO 标准 (可以用 Google 搜索您的新货币)。',
];
