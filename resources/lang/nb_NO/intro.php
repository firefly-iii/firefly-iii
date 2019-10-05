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
    'index_intro'                                     => 'Velkommen til forsiden til Firefly III. Ta deg tid til å gå gjennom denne introen for å få en følelse av hvordan Firefly III fungerer.',
    'index_accounts-chart'                            => 'Dette diagrammet viser gjeldende saldo på aktivakontoene dine. Du kan velge kontoene som er synlige her under innstillinger.',
    'index_box_out_holder'                            => 'Denne lille boksen og boksene ved siden av gir deg rask oversikt over din økonomiske situasjon.',
    'index_help'                                      => 'Hvis du trenger hjelp til en side eller et skjema, trykker du på denne knappen.',
    'index_outro'                                     => 'De fleste sidene av Firefly III vil starte med en liten gjennomgang slik som denne. Ta kontakt med meg hvis du har spørsmål eller kommentarer. Sett igang!',
    'index_sidebar-toggle'                            => 'For å opprette nye transaksjoner, kontoer eller andre ting, bruk menyen under dette ikonet.',
    'index_cash_account'                              => 'These are the accounts created so far. You can use the cash account to track cash expenses but it\'s not mandatory of course.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Select your favorite asset account or liability from this dropdown.',
    'transactions_create_withdrawal_destination'      => 'Select an expense account here. Leave it empty if you want to make a cash expense.',
    'transactions_create_withdrawal_foreign_currency' => 'Use this field to set a foreign currency and amount.',
    'transactions_create_withdrawal_more_meta'        => 'Plenty of other meta data you set in these fields.',
    'transactions_create_withdrawal_split_add'        => 'If you want to split a transaction, add more splits with this button',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Select or type the payee in this auto-completing dropdown/textbox. Leave it empty if you want to make a cash deposit.',
    'transactions_create_deposit_destination'         => 'Select an asset or liability account here.',
    'transactions_create_deposit_foreign_currency'    => 'Use this field to set a foreign currency and amount.',
    'transactions_create_deposit_more_meta'           => 'Plenty of other meta data you set in these fields.',
    'transactions_create_deposit_split_add'           => 'If you want to split a transaction, add more splits with this button',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Select the source asset account here.',
    'transactions_create_transfer_destination'        => 'Select the destination asset account here.',
    'transactions_create_transfer_foreign_currency'   => 'Use this field to set a foreign currency and amount.',
    'transactions_create_transfer_more_meta'          => 'Plenty of other meta data you set in these fields.',
    'transactions_create_transfer_split_add'          => 'If you want to split a transaction, add more splits with this button',

    // create account:
    'accounts_create_iban'                            => 'Gi kontoene dine en gyldig IBAN. Dette gjør dataimport lettere i fremtiden.',
    'accounts_create_asset_opening_balance'           => 'Aktivakontoer kan ha en "åpningssaldo" som indikerer starten på denne kontoens historie i Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III støtter flere valutaer. Aktivakontoer har en hovedvaluta, som du må sette her.',
    'accounts_create_asset_virtual'                   => 'Det kan noen ganger hjelpe å gi kontoen din en virtuell saldo: et ekstra beløp blir alltid lagt til eller fjernet fra den faktiske saldoen.',

    // budgets index
    'budgets_index_intro'                             => 'Budsjetter brukes til å styre din økonomi og er en av kjernefunksjonene i Firefly III.',
    'budgets_index_set_budget'                        => 'Sett ditt totale budsjett for hver periode, så Firefly III kan fortelle deg om du har budsjettert med alle tilgjengelige penger.',
    'budgets_index_see_expenses_bar'                  => 'Når du bruker penger vil denne linjen fylles opp.',
    'budgets_index_navigate_periods'                  => 'Naviger gjennom perioder for å enkelt sette budsjetter på forhånd.',
    'budgets_index_new_budget'                        => 'Opprett nye budsjetter etter behov.',
    'budgets_index_list_of_budgets'                   => 'Bruk denne tabellen til å angi beløp for hvert budsjett og se hvordan du klarer deg.',
    'budgets_index_outro'                             => 'Hvis du vil vite mer om budsjettering, trykk på hjelp-ikonet øverst til høyre.',

    // reports (index)
    'reports_index_intro'                             => 'Bruk disse rapportene for å få detaljert innsikt i din økonomi.',
    'reports_index_inputReportType'                   => 'Velg en rapporttype. Sjekk ut hjelpesidene for å se hva hver rapport viser deg.',
    'reports_index_inputAccountsSelect'               => 'Du kan ekskludere eller inkludere aktivakontoer etter eget ønske.',
    'reports_index_inputDateRange'                    => 'Den valgte datoperioden er helt opp til deg: fra en dag, og opptil 10 år.',
    'reports_index_extra-options-box'                 => 'Avhengig av hvilken rapport du har valgt, kan du velge ekstra filtre og alternativer her. Følg med på denne boksen når du endrer rapporttyper.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Denne rapporten gir deg en rask og omfattende oversikt over økonomien din. Hvis du ønsker å se noe annet her, ikke nøl med å kontakte meg!',
    'reports_report_audit_intro'                      => 'Denne rapporten gir deg detaljert innsikt i aktivakontoene dine.',
    'reports_report_audit_optionsBox'                 => 'Bruk disse avkrysningssboksene for å vise eller skjule kolonnene du er interessert i.',

    'reports_report_category_intro'                  => 'Denne rapporten gir deg innblikk i en eller flere kategorier.',
    'reports_report_category_pieCharts'              => 'Disse diagrammene gir deg innblikk i utgifter og inntekt per kategori eller per konto.',
    'reports_report_category_incomeAndExpensesChart' => 'Dette diagrammet viser dine utgifter og inntekter per kategori.',

    'reports_report_tag_intro'                  => 'Denne rapporten gir deg innblikk i en eller flere tagger.',
    'reports_report_tag_pieCharts'              => 'Disse diagrammene gir deg innblikk i utgifter og inntekter per tagg, konto, kategori eller budsjett.',
    'reports_report_tag_incomeAndExpensesChart' => 'Dette diagrammet viser dine utgifter og inntekter per tagg.',

    'reports_report_budget_intro'                             => 'Denne rapporten gir deg innblikk i ett eller flere budsjetter.',
    'reports_report_budget_pieCharts'                         => 'Disse diagrammene gir deg innblikk i utgifter og inntekter per budsjett eller per konto.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Dette diagrammet viser dine utgifter per budsjett.',

    // create transaction
    'transactions_create_switch_box'                          => 'Bruk disse knappene for å raskt bytte til den typen transaksjon du vil lagre.',
    'transactions_create_ffInput_category'                    => 'Du kan fritt skrive inn dette feltet. Tidligere opprettede kategorier vil bli foreslått.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Koble uttaket ditt til et budsjett for bedre økonomisk kontroll.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Bruk denne rullegardinmenyen når uttaket ditt er i en annen valuta.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Bruk denne rullegardinmenyen når innskuddet ditt er i en annen valuta.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Velg en sparegris og knytt denne overføringen til din sparing.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Dette feltet viser hvor mye du har spart i hver sparegris.',
    'piggy-banks_index_button'                                => 'Ved siden av denne fremdriftslinjen er det to knapper (+ og -) for å legge til eller fjerne penger fra hver sparegris.',
    'piggy-banks_index_accountStatus'                         => 'For hver aktivakonto med minst en sparegris er statusen oppført i denne tabellen.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Hva er ditt mål? En ny sofa, et kamera, penger til uforutsette utgifter?',
    'piggy-banks_create_date'                                 => 'Du kan angi en måldato eller en frist for din sparegris.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Dette diagrammet viser historikken til denne sparegrisen.',
    'piggy-banks_show_piggyDetails'                           => 'Noen detaljer om sparegrisen din',
    'piggy-banks_show_piggyEvents'                            => 'Eventuelle tillegg eller flyttinger er også oppført her.',

    // bill index
    'bills_index_rules'                                       => 'Her ser du hvilke regler som vil sjekke om denne regningen passer',
    'bills_index_paid_in_period'                              => 'Dette feltet angir når regningen sist ble betalt.',
    'bills_index_expected_in_period'                          => 'Dette feltet angir for hver regning om og når neste regning forventes å komme.',

    // show bill
    'bills_show_billInfo'                                     => 'Denne tabellen viser generell informasjon om denne regningen.',
    'bills_show_billButtons'                                  => 'Bruk denne knappen til å skanne gamle transaksjoner på nytt, slik at de blir riktig koblet opp med denne regningen.',
    'bills_show_billChart'                                    => 'Dette diagrammet viser transaksjoner knyttet til denne regningen.',

    // create bill
    'bills_create_intro'                                      => 'Bruk regninger for å spore hvor mange penger du skal betale for hver periode. Tenk på utgifter som leie, forsikring eller nedbetaling av boliglån.',
    'bills_create_name'                                       => 'Bruk et beskrivende navn som "Leie" eller "Helseforsikring".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Velg minimum og maksimumsbeløp for denne regningen.',
    'bills_create_repeat_freq_holder'                         => 'De fleste regninger gjentas månedlig, men du kan angi en annen frekvens her.',
    'bills_create_skip_holder'                                => 'Hvis en regning gjentas hver andre uke, skal "hopp over" -feltet settes til "1" for å hoppe over annenhver uke.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III lar deg administrere regler som automatisk vil bli brukt på alle transaksjoner du oppretter eller redigerer.',
    'rules_index_new_rule_group'                              => 'Du kan kombinere regler i grupper for enklere håndtering.',
    'rules_index_new_rule'                                    => 'Lag så mange regler som du vil.',
    'rules_index_prio_buttons'                                => 'Legg dem i den rekkefølgen du synes passer best.',
    'rules_index_test_buttons'                                => 'Du kan teste reglene dine eller bruke dem på eksisterende transaksjoner.',
    'rules_index_rule-triggers'                               => 'Regler har "utløsere" og "handlinger" som du kan bytte rekkefølge på ved å dra og slippe.',
    'rules_index_outro'                                       => 'Husk å sjekke ut hjelpesidene ved å bruke (?) -ikonet øverst til høyre!',

    // create rule:
    'rules_create_mandatory'                                  => 'Velg en beskrivende tittel, og bestem når regelen skal utløses.',
    'rules_create_ruletriggerholder'                          => 'Legg til så mange utløsere som du vil, men husk at ALLE utløsere må samsvare før noen handlinger settes i gang.',
    'rules_create_test_rule_triggers'                         => 'Bruk denne knappen for å se hvilke eksisterende transaksjoner som samsvarer med regelen din.',
    'rules_create_actions'                                    => 'Sett så mange handlinger som du vil.',

    // preferences
    'preferences_index_tabs'                                  => 'Flere alternativer er tilgjengelig bak disse fanene.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III støtter flere valutaer, som du kan endre på denne siden.',
    'currencies_index_default'                                => 'Firefly III har en standard valuta.',
    'currencies_index_buttons'                                => 'Bruk disse knappene for å endre standard valuta eller aktivere andre valutaer.',

    // create currency
    'currencies_create_code'                                  => 'Denne koden bør være ISO-kompatibel (Søk på Google for å finne ISO-koden).',
];
