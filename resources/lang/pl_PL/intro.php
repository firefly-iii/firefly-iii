<?php
/**
 * intro.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
    'index_intro'                           => 'Witamy na stronie domowej Firefly III. Proszę poświęć trochę czasu, aby przejść przez to wprowadzenie, aby poznać sposób działania Firefly III.',
    'index_accounts-chart'                  => 'Ten wykres przedstawia bieżące saldo kont aktywów. Możesz wybrać konta widoczne tutaj w Twoich preferencjach.',
    'index_box_out_holder'                  => 'To małe pole i pola obok niego umożliwiają szybki przegląd Twojej sytuacji finansowej.',
    'index_help'                            => 'Jeśli potrzebujesz pomocy na stronie lub formularzu, naciśnij ten przycisk.',
    'index_outro'                           => 'Większość stron z Firefly III zacznie się od małego wprowadzenia jak to. Skontaktuj się ze mną, jeśli masz pytania lub komentarze. Miłego korzystania!',
    'index_sidebar-toggle'                  => 'Aby utworzyć nowe transakcje, konta lub inne rzeczy, użyj menu pod tą ikoną.',

    // create account:
    'accounts_create_iban'                  => 'Nadaj kontom ważny numer IBAN. Może to ułatwić import danych w przyszłości.',
    'accounts_create_asset_opening_balance' => 'Konta zasobów mogą mieć "bilans otwarcia", wskazując początek historii tego konta w Firefly.',
    'accounts_create_asset_currency'        => 'Firefly III obsługuje wiele walut. Konta aktywów mają jedną główną walutę, który należy ustawić tutaj.',
    'accounts_create_asset_virtual'         => 'Czasami warto dodać do konta wirtualne saldo: dodatkowa kwota zawsze dodawana lub odejmowana od rzeczywistego salda.',

    // budgets index
    'budgets_index_intro'                   => 'Budżety są wykorzystywane do zarządzania finansami i stanowią jedną z podstawowych funkcji Firefly III.',
    'budgets_index_set_budget'              => 'Ustaw całkowity budżet na każdy okres, aby Firefly mógł Ci powiedzieć, czy wydałeś wszystkie dostępne pieniądze.',
    'budgets_index_see_expenses_bar'        => 'Wydawanie pieniędzy powoli wypełnia ten pasek.',
    'budgets_index_navigate_periods'        => 'Przejrzyj okresy, aby łatwiej ustawić przyszłe budżety.',
    'budgets_index_new_budget'              => 'Utwórz nowe budżety zgodnie z Twoimi potrzebami.',
    'budgets_index_list_of_budgets'         => 'Skorzystaj z tej tabeli, aby ustawić kwoty dla każdego budżetu i sprawdź jak ci idzie.',
    'budgets_index_outro'                   => 'Aby dowiedzieć się więcej o budżetowaniu, użyj ikonki pomocy w prawym górnym rogu.',

    // reports (index)
    'reports_index_intro'                   => 'Skorzystaj z tych raportów, aby uzyskać szczegółowe informacje o swoich finansach.',
    'reports_index_inputReportType'         => 'Wybierz typ raportu. Sprawdź stronę pomocy, aby zobaczyć, co pokazuje każdy raport.',
    'reports_index_inputAccountsSelect'     => 'Możesz wykluczyć lub uwzględnić konta zasobów według własnego uznania.',
    'reports_index_inputDateRange'          => 'Wybrany zakres dat zależy wyłącznie od ciebie: od jednego dnia do 10 lat.',
    'reports_index_extra-options-box'       => 'W zależności od wybranego raportu możesz wybrać dodatkowe filtry i opcje tutaj. Obserwuj to pole, gdy zmieniasz typy raportów.',

    // reports (reports)
    'reports_report_default_intro'          => 'Raport ten zapewni szybki i wszechstronny przegląd twoich finansów. Jeśli chcesz zobaczyć cokolwiek innego, nie wahaj się ze mną skontaktować!',
    'reports_report_audit_intro'            => 'Ten raport zawiera szczegółowe informacje na temat kont zasobów.',
    'reports_report_audit_optionsBox'       => 'Użyj tych pól wyboru aby pokazać lub ukryć kolumny, które cię interesują.',

    'reports_report_category_intro'                  => 'Ten raport daje wgląd w jedną lub wiele kategorii.',
    'reports_report_category_pieCharts'              => 'Te wykresy dają wgląd w wydatki i dochody według kategorii lub konta.',
    'reports_report_category_incomeAndExpensesChart' => 'This chart shows your expenses and income per category.',

    'reports_report_tag_intro'                  => 'This report will give you insight in one or multiple tags.',
    'reports_report_tag_pieCharts'              => 'These charts will give you insight in expenses and income per tag, account, category or budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'This chart shows your expenses and income per tag.',

    'reports_report_budget_intro'                             => 'This report will give you insight in one or multiple budgets.',
    'reports_report_budget_pieCharts'                         => 'These charts will give you insight in expenses per budget or per account.',
    'reports_report_budget_incomeAndExpensesChart'            => 'This chart shows your expenses per budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Use these buttons to quickly switch the type of transaction you wish to save.',
    'transactions_create_ffInput_category'                    => 'You can freely type in this field. Previously created categories will be suggested.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Link your withdrawal to a budget for better financial control.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Use this dropdown when your withdrawal is in another currency.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Use this dropdown when your deposit is in another currency.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Select a piggy bank and link this transfer to your savings.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'This field shows you how much you\'ve saved in each piggy bank.',
    'piggy-banks_index_button'                                => 'Next to this progress bar are two buttons (+ and -) to add or remove money from each piggy bank.',
    'piggy-banks_index_accountStatus'                         => 'For each asset account with at least one piggy bank the status is listed in this table.',

    // create piggy
    'piggy-banks_create_name'                                 => 'What is your goal? A new couch, a camera, money for emergencies?',
    'piggy-banks_create_date'                                 => 'You can set a target date or a deadline for your piggy bank.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'This chart will show the history of this piggy bank.',
    'piggy-banks_show_piggyDetails'                           => 'Niektóre szczegóły dotyczące skarbonki',
    'piggy-banks_show_piggyEvents'                            => 'Any additions or removals are also listed here.',

    // bill index
    'bills_index_paid_in_period'                              => 'This field indicates when the bill was last paid.',
    'bills_index_expected_in_period'                          => 'This field indicates for each bill if and when the next bill is expected to hit.',

    // show bill
    'bills_show_billInfo'                                     => 'This table shows some general information about this bill.',
    'bills_show_billButtons'                                  => 'Use this button to re-scan old transactions so they will be matched to this bill.',
    'bills_show_billChart'                                    => 'This chart shows the transactions linked to this bill.',

    // create bill
    'bills_create_name'                                       => 'Use a descriptive name such as "Rent" or "Health insurance".',
    'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Select a minimum and maximum amount for this bill.',
    'bills_create_repeat_freq_holder'                         => 'Most bills repeat monthly, but you can set another frequency here.',
    'bills_create_skip_holder'                                => 'If a bill repeats every 2 weeks for example, the "skip"-field should be set to "1" to skip every other week.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III allows you to manage rules, that will automagically be applied to any transaction you create or edit.',
    'rules_index_new_rule_group'                              => 'You can combine rules in groups for easier management.',
    'rules_index_new_rule'                                    => 'Twórz dowolną liczbę reguł.',
    'rules_index_prio_buttons'                                => 'Zamów je w sposób, jaki uznasz za stosowny.',
    'rules_index_test_buttons'                                => 'Możesz przetestować swoje zasady lub zastosować je do istniejących transakcji.',
    'rules_index_rule-triggers'                               => 'Rules have "triggers" and "actions" that you can order by drag-and-drop.',
    'rules_index_outro'                                       => 'Be sure to check out the help pages using the (?) icon in the top right!',

    // create rule:
    'rules_create_mandatory'                                  => 'Choose a descriptive title, and set when the rule should be fired.',
    'rules_create_ruletriggerholder'                          => 'Add as many triggers as you like, but remember that ALL triggers must match before any actions are fired.',
    'rules_create_test_rule_triggers'                         => 'Use this button to see which transactions would match your rule.',
    'rules_create_actions'                                    => 'Ustaw tak wiele akcji, jak chcesz.',

    // preferences
    'preferences_index_tabs'                                  => 'Więcej opcji dostępne są za tymi kartami.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III supports multiple currencies, which you can change on this page.',
    'currencies_index_default'                                => 'Firefly III has one default currency. You can always switch of course using these buttons.',

    // create currency
    'currencies_create_code'                                  => 'This code should be ISO compliant (Google it for your new currency).',
];
