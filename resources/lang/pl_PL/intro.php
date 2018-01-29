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
    'accounts_create_asset_opening_balance' => 'Konta aktywów mogą mieć "bilans otwarcia", wskazujący początek historii tego konta w Firefly III.',
    'accounts_create_asset_currency'        => 'Firefly III obsługuje wiele walut. Konta aktywów mają jedną główną walutę, który należy ustawić tutaj.',
    'accounts_create_asset_virtual'         => 'Czasami warto dodać do konta wirtualne saldo: dodatkowa kwota zawsze dodawana lub odejmowana od rzeczywistego salda.',

    // budgets index
    'budgets_index_intro'                   => 'Budżety są wykorzystywane do zarządzania finansami i stanowią jedną z podstawowych funkcji Firefly III.',
    'budgets_index_set_budget'              => 'Ustaw całkowity budżet na każdy okres, aby Firefly III mógł Ci powiedzieć, czy wydałeś wszystkie dostępne pieniądze.',
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
    'reports_report_audit_intro'            => 'Ten raport zawiera szczegółowe informacje na temat kont aktywów.',
    'reports_report_audit_optionsBox'       => 'Użyj tych pól wyboru aby pokazać lub ukryć kolumny, które Cię interesują.',

    'reports_report_category_intro'                  => 'Ten raport daje wgląd w jedną lub wiele kategorii.',
    'reports_report_category_pieCharts'              => 'Te wykresy dają wgląd w wydatki i dochody według kategorii lub konta.',
    'reports_report_category_incomeAndExpensesChart' => 'Ten wykres pokazuje twoje wydatki i dochody według kategorii.',

    'reports_report_tag_intro'                  => 'Ten raport daje wgląd w jeden lub wiele tagów.',
    'reports_report_tag_pieCharts'              => 'Te wykresy dają wgląd w wydatki i dochody według tagu, konta, kategorii lub budżetu.',
    'reports_report_tag_incomeAndExpensesChart' => 'Ten wykres pokazuje Twoje wydatki i dochody według tagu.',

    'reports_report_budget_intro'                             => 'Ten raport daje wgląd w jeden lub wiele budżetów.',
    'reports_report_budget_pieCharts'                         => 'Te wykresy dają wgląd w wydatki według budżetu lub konta.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Ten wykres pokazuje twoje wydatki według budżetu.',

    // create transaction
    'transactions_create_switch_box'                          => 'Użyj tych przycisków, aby szybko przełączyć typ transakcji, którą chcesz zapisać.',
    'transactions_create_ffInput_category'                    => 'Możesz swobodnie pisać w tym polu. Wcześniej utworzone kategorie zostaną zaproponowane.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Połącz swoje wypłaty z budżetem, aby uzyskać lepszą kontrolę finansową.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Użyj tego menu, gdy wypłata jest w innej walucie.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Użyj tego menu, gdy depozyt jest w innej walucie.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Wybierz skarbonkę i połącz ten przelew z oszczędnościami.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'To pole pokazuje, ile zaoszczędziłeś w każdej skarbonce.',
    'piggy-banks_index_button'                                => 'Obok tego paska postępu znajdują się dwa przyciski (+ oraz -) do dodawania lub usuwania pieniędzy z każdej skarbonki.',
    'piggy-banks_index_accountStatus'                         => 'Dla każdego konta aktywów z co najmniej jedną skarbonką status jest pokazany w tej tabeli.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Jaki jest twój cel? Nowa kanapa, aparat fotograficzny, pieniądze na nagłe wypadki?',
    'piggy-banks_create_date'                                 => 'Możesz ustawić docelową datę lub termin dla swojej skarbonki.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Ten wykres pokaże historię Twojej skarbonki.',
    'piggy-banks_show_piggyDetails'                           => 'Niektóre szczegóły dotyczące skarbonki',
    'piggy-banks_show_piggyEvents'                            => 'Wszelkie dodatki lub usunięcia są również tutaj wymienione.',

    // bill index
    'bills_index_paid_in_period'                              => 'To pole wskazuje, kiedy rachunek został ostatnio opłacony.',
    'bills_index_expected_in_period'                          => 'To pole wskazuje dla każdego rachunku, czy i kiedy oczekuje się następnego rachunku.',

    // show bill
    'bills_show_billInfo'                                     => 'Ta tabela pokazuje ogólne informacje na temat tego rachunku.',
    'bills_show_billButtons'                                  => 'Użyj tego przycisku, aby ponownie przeskanować stare transakcje, aby dopasować je do tego rachunku.',
    'bills_show_billChart'                                    => 'Ten wykres pokazuje transakcje powiązane z tym rachunkiem.',

    // create bill
    'bills_create_name'                                       => 'Użyj opisowej nazwy, takiej jak "Czynsz" lub "Ubezpieczenie zdrowotne".',
    'bills_create_match'                                      => 'Aby dopasować transakcje, użyj zwrotów z tych transakcji lub konta wydatków. Wszystkie słowa muszą pasować.',
    'bills_create_amount_min_holder'                          => 'Wybierz minimalną i maksymalną kwotę dla tego rachunku.',
    'bills_create_repeat_freq_holder'                         => 'Większość rachunków powtarza się co miesiąc, ale możesz ustawić inną częstotliwość tutaj.',
    'bills_create_skip_holder'                                => 'Jeśli rachunek powtarza się co 2 tygodnie, pole "Pomiń" powinno być ustawione na "1", aby pominąć co drugi tydzień.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III pozwala zarządzać regułami, które automatycznie zostaną zastosowane do każdej transakcji, którą tworzysz lub edytujesz.',
    'rules_index_new_rule_group'                              => 'Możesz łączyć reguły w grupach, aby ułatwić zarządzanie.',
    'rules_index_new_rule'                                    => 'Twórz dowolną liczbę reguł.',
    'rules_index_prio_buttons'                                => 'Szereguj je w dowolny sposób, jaki uznasz za stosowny.',
    'rules_index_test_buttons'                                => 'Możesz przetestować swoje reguły lub zastosować je do istniejących transakcji.',
    'rules_index_rule-triggers'                               => 'Reguły mają "wyzwalacze" i "akcje", które można szeregować poprzez przeciąganie i upuszczanie.',
    'rules_index_outro'                                       => 'Koniecznie sprawdź strony pomocy używając ikony (?) w prawym górnym rogu!',

    // create rule:
    'rules_create_mandatory'                                  => 'Wybierz opisowy tytuł i ustaw, kiedy reguła ma zostać uruchomiona.',
    'rules_create_ruletriggerholder'                          => 'Dodaj tyle wyzwalaczy, ile zechcesz, ale pamiętaj, że WSZYSTKIE wyzwalacze muszą pasować do transakcji przed uruchomieniem jakichkolwiek akcji.',
    'rules_create_test_rule_triggers'                         => 'Użyj tego przycisku, aby zobaczyć, które transakcje pasują do Twojej reguły.',
    'rules_create_actions'                                    => 'Ustaw tak wiele akcji, jak chcesz.',

    // preferences
    'preferences_index_tabs'                                  => 'Więcej opcji dostępne są za tymi kartami.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III obsługuje wiele walut, które możesz zmienić na tej stronie.',
    'currencies_index_default'                                => 'Firefly III ma jedną domyślną walutę. Zawsze możesz ją przełączyć używając tych przycisków.',

    // create currency
    'currencies_create_code'                                  => 'Ten kod powinien być zgodny z normą ISO (poszukaj go w Google dla Twojej nowej waluty).',
];
