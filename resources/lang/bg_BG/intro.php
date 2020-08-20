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
    'index_intro'                                     => 'Добре дошли в заглавната страница на Firefly III. Моля отделете време за това въведение, за да усетите как работи Firefly III.',
    'index_accounts-chart'                            => 'Тази графика показва текущият баланс на вашите сметки за активи. Можете да изберете видимите тук сметки според вашите предпочитания.',
    'index_box_out_holder'                            => 'Тази малка кутия и кутиите до нея ще ви дадат бърз общ преглед на вашата финансова ситуация.',
    'index_help'                                      => 'Ако някога имате нужда от помощ със страница или форма, натиснете този бутон.',
    'index_outro'                                     => 'Повечето страници на Firefly III ще започнат с малка обиколка като тази. Моля свържете се с мен, когато имате въпроси или коментари. Насладете се!',
    'index_sidebar-toggle'                            => 'За да създадете нови транзакции, сметки или други неща, използвайте менюто под тази икона.',
    'index_cash_account'                              => 'Това са създадените досега сметки. Можете да използвате касовата сметка за проследяване на разходите в брой, но това не е задължително.',

    // transactions
    'transactions_create_basic_info'                  => 'Въведете основната информация за вашата транзакция. Източник, дестинация, дата и описание.',
    'transactions_create_amount_info'                 => 'Въведете сумата на транзакцията. Ако е необходимо, полетата ще се актуализират автоматично за информация за сума в чужда валута.',
    'transactions_create_optional_info'               => 'Всички тези полета не са задължителни. Добавянето на метаданни тук ще направи вашите транзакции по-добре организирани.',
    'transactions_create_split'                       => 'Ако искате да разделите транзакция, добавете още разделяния с този бутон',

    // create account:
    'accounts_create_iban'                            => 'Дайте на вашите сметки валиден IBAN. Това може да направи импортирането на данни много лесно в бъдеще.',
    'accounts_create_asset_opening_balance'           => 'Сметките за активи може да имат "начално салдо", което показва началото на историята на този акаунт в Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III поддържа множество валути. Сметките за активи имат една основна валута, която трябва да зададете тук.',
    'accounts_create_asset_virtual'                   => 'Понякога може да е полезно да се даде виртуален баланс на вашата сметка: допълнителна сума, която винаги се добавя към или отстранява от действителното салдо.',

    // budgets index
    'budgets_index_intro'                             => 'Бюджетите се използват за управление на вашите финанси и формират една от основните функции на Firefly III.',
    'budgets_index_set_budget'                        => 'Задайте общия си бюджет за всеки период, за да може Firefly III да ви каже дали сте предвидили всички налични пари.',
    'budgets_index_see_expenses_bar'                  => 'Харченето на пари бавно ще запълва тази лента.',
    'budgets_index_navigate_periods'                  => 'Придвижвайте се през периодите, за да задавате лесно бюджетите си напред.',
    'budgets_index_new_budget'                        => 'Създайте нови бюджети, както сметнете за добре.',
    'budgets_index_list_of_budgets'                   => 'Използвайте тази таблица, за да зададете сумите за всеки бюджет и да видите как се справяте.',
    'budgets_index_outro'                             => 'За да научите повече за бюджетирането, проверете иконата за помощ в горния десен ъгъл.',

    // reports (index)
    'reports_index_intro'                             => 'Използвайте тези отчети, за да получите подробна информация за вашите финанси.',
    'reports_index_inputReportType'                   => 'Изберете тип отчет. Разгледайте страниците за помощ, за да видите какво ви показва всеки отчет.',
    'reports_index_inputAccountsSelect'               => 'Можете да изключите или включите сметки за активи, както сметнете за добре.',
    'reports_index_inputDateRange'                    => 'Избраният диапазон от дати зависи изцяло от вас: от един ден до 10 години.',
    'reports_index_extra-options-box'                 => 'В зависимост от отчета който сте избрали, можете да изберете допълнителни филтри и опции тук. Гледайте това поле, когато променяте типовете отчети.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Този отчет ще ви даде бърз и изчерпателен преглед на вашите финанси. Ако искате да видите нещо друго, моля не колебайте да се свържете с мен!',
    'reports_report_audit_intro'                      => 'Този отчет ще ви даде подробна информация за вашите сметки за активи.',
    'reports_report_audit_optionsBox'                 => 'Използвайте тези квадратчета, за да покажете или скриете колоните които ви интересуват.',

    'reports_report_category_intro'                  => 'Този отчет ще ви даде представа за една или няколко категории.',
    'reports_report_category_pieCharts'              => 'Тези диаграми ще ви дадат представа за разходите и приходите по категория или по сметка.',
    'reports_report_category_incomeAndExpensesChart' => 'Тази диаграма показва вашите разходи и приходи по категория.',

    'reports_report_tag_intro'                  => 'Този отчет ще ви даде представа за един или няколко етикета.',
    'reports_report_tag_pieCharts'              => 'Тези диаграми ще ви дадат представа за разходите и приходите по етикет, сметка, категория или бюджет.',
    'reports_report_tag_incomeAndExpensesChart' => 'Тази диаграма показва вашите разходи и доходи по етикет.',

    'reports_report_budget_intro'                             => 'Този отчет ще ви даде представа за един или няколко бюджета.',
    'reports_report_budget_pieCharts'                         => 'Тези диаграми ще ви дадат представа за разходите по бюджет или по сметка.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Тази диаграма показва вашите разходи по бюджет.',

    // create transaction
    'transactions_create_switch_box'                          => 'Използвайте тези бутони за бързо превключване на типа транзакция, която искате да запазите.',
    'transactions_create_ffInput_category'                    => 'Можете свободно да пишете в това поле. Предварително създадени категории ще бъдат предложени.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Свържете теглене си с бюджет за по-добър финансов контрол.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Използвайте това падащо меню, когато тегленето ви е в друга валута.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Използвайте това падащо меню, когато депозита ви е в друга валута.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Изберете касичка и свържете това прехвърляне с вашите спестявания.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Това поле ви показва колко сте спестили във всяка касичка.',
    'piggy-banks_index_button'                                => 'До тази лента за прогрес са разположени два бутона (+ и -) за добавяне или премахване на пари от всяка касичка.',
    'piggy-banks_index_accountStatus'                         => 'За всяка сметка за активи с най-малко една касичка статусът е посочен в тази таблица.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Каква е твоята цел? Нов диван, камера, пари за спешни случаи?',
    'piggy-banks_create_date'                                 => 'Можете да зададете целева дата или краен срок за вашата касичка.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Тази диаграма ще покаже историята на тази касичка.',
    'piggy-banks_show_piggyDetails'                           => 'Някои подробности за вашата касичка',
    'piggy-banks_show_piggyEvents'                            => 'Всички допълнения или премахвания също са посочени тук.',

    // bill index
    'bills_index_rules'                                       => 'Тук виждате кои правила ще проверят дали тази сметка е получена',
    'bills_index_paid_in_period'                              => 'Това поле указва кога за последно е платена сметката.',
    'bills_index_expected_in_period'                          => 'Това поле обозначава за всяка сметка, ако и кога се очаква да се получи следващата сметка.',

    // show bill
    'bills_show_billInfo'                                     => 'Тази таблица показва обща информация за тази сметка.',
    'bills_show_billButtons'                                  => 'Използвайте този бутон за повторно сканиране на стари транзакции, така че те да бъдат съпоставени с тази сметка.',
    'bills_show_billChart'                                    => 'Тази диаграма показва транзакциите, свързани с тази сметка.',

    // create bill
    'bills_create_intro'                                      => 'Използвайте сметки за да проследявате сумата пари, които дължите за всеки период. Помислете за разходи като наем, застраховка или ипотечни плащания.',
    'bills_create_name'                                       => 'Използвайте описателно име като "Наем" или "Здравно осигуряване".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Изберете минимална и максимална сума за тази сметка.',
    'bills_create_repeat_freq_holder'                         => 'Повечето сметки се повтарят месечно, но можете да зададете друга честота тук.',
    'bills_create_skip_holder'                                => 'Ако сметката се повтаря на всеки 2 седмици, полето "Пропусни" трябва да бъде настроено на "1", за да се прескача през седмица.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III ви позволява да управлявате правила, които автоматично ще се прилагат към всяка транзакция, която създавате или редактирате.',
    'rules_index_new_rule_group'                              => 'Можете да комбинирате правила в групи за по-лесно управление.',
    'rules_index_new_rule'                                    => 'Създайте колкото искате правила.',
    'rules_index_prio_buttons'                                => 'Подредете ги, както сметнете за добре.',
    'rules_index_test_buttons'                                => 'Можете да тествате правилата си или да ги прилагате към съществуващи транзакции.',
    'rules_index_rule-triggers'                               => 'Правилата имат "задействания" и "действия", които можете да подредите чрез плъзгане и пускане.',
    'rules_index_outro'                                       => 'Не забравяйте да разгледате помощните страници, като използвате иконата (?) горе вдясно!',

    // create rule:
    'rules_create_mandatory'                                  => 'Изберете описателно заглавие и задайте кога правилото трябва да бъде задействано.',
    'rules_create_ruletriggerholder'                          => 'Добавете колкото искате задействания, но не забравяйте че ВСИЧКИ задействания трябва да съвпаднат, преди да бъдат осъществени действия.',
    'rules_create_test_rule_triggers'                         => 'Използвайте този бутон, за да видите кои транзакции биха съответствали на вашето правило.',
    'rules_create_actions'                                    => 'Задайте толкова действия, колкото искате.',

    // preferences
    'preferences_index_tabs'                                  => 'Повече опции са достъпни зад тези раздели.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III поддържа множество валути, които можете да промените на тази страница.',
    'currencies_index_default'                                => 'Firefly III има една валута по подразбиране.',
    'currencies_index_buttons'                                => 'Използвайте тези бутони, за да промените валутата по подразбиране или да активирате други валути.',

    // create currency
    'currencies_create_code'                                  => 'Този код трябва да е съвместим с ISO (използвайте Google да го намерите за вашата нова валута).',
];
