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
    'index_intro'                                     => 'Välkommen till index sidan för Firefly III. Vänligen ta lite tid för att gå genom introt och kunna få en känsla hur Firefly III fungerar.',
    'index_accounts-chart'                            => 'Detta diagram visar nuvarande balans på dina tillgångskonton. Det går att välja vilka konton som ses här under inställningarna.',
    'index_box_out_holder'                            => 'Den här lilla rutan och rutorna bredvid ger dig en snabb överblick över din ekonomiska situation.',
    'index_help'                                      => 'Om du någonsin behöver hjälp med en sida eller ett formulär, tryck på den här knappen.',
    'index_outro'                                     => 'De flesta sidor av Firefly III börjar med en lite rundtur som denna. Kontakta mig om det finns några frågor eller kommentarer. Lycka till!',
    'index_sidebar-toggle'                            => 'För att skapa nya transaktioner, konton eller andra saker, använd menyn under den här ikonen.',
    'index_cash_account'                              => 'Dessa är de konton som skapats hittills. Använd kontantkonto för att spåra kontantutgifter men det är naturligtvis inte obligatoriskt.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Välj ditt favorit tillgång eller skuldkonto från rullgardinsmenyn.',
    'transactions_create_withdrawal_destination'      => 'Välj skuldkonto här. Lämna tomt om det är ett kontantuttag.',
    'transactions_create_withdrawal_foreign_currency' => 'Använd detta fält för utländsk valuta och summa.',
    'transactions_create_withdrawal_more_meta'        => 'Massor av andra metadata som kan anges i dessa fält.',
    'transactions_create_withdrawal_split_add'        => 'Om du vill dela en transaktion, lägg till fler delar via denna knapp',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Välj eller skriv mottagare i denna auto-ifyllnads rullgardin/textrutan. Lämna tom om det är en kontant insättning.',
    'transactions_create_deposit_destination'         => 'Välj ett tillgång- eller belastningskonto här.',
    'transactions_create_deposit_foreign_currency'    => 'Använd detta fält för utländsk valuta och summa.',
    'transactions_create_deposit_more_meta'           => 'Massor av olika metadata kan anges i dessa fält.',
    'transactions_create_deposit_split_add'           => 'Om du vill dela en transaktion, lägg till fler delar via denna knapp',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Välj källtillgångskontot här.',
    'transactions_create_transfer_destination'        => 'Välj destinationstillgångskontot här.',
    'transactions_create_transfer_foreign_currency'   => 'Använd detta fält för utländsk valuta och summa.',
    'transactions_create_transfer_more_meta'          => 'Massor av olika metadata kan anges i dessa fält.',
    'transactions_create_transfer_split_add'          => 'Om du vill dela en transaktion, lägg till fler delar via denna knapp',

    // create account:
    'accounts_create_iban'                            => 'Ge dina konton giltig IBAN. Detta kan förenkla för dataimport i framtiden.',
    'accounts_create_asset_opening_balance'           => 'Tillgångskonton kan ha en "öppningsbalans", vilket indikerar början på det här kontoets historia i Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III stöder flera valutor. Tillgångskonton har en huvudvaluta, som du måste ställa in här.',
    'accounts_create_asset_virtual'                   => 'Ibland kan det hjälpa att ge ditt konto ett virtuell saldo: ett extra belopp som alltid läggs till eller tas bort från ditt faktiska saldo.',

    // budgets index
    'budgets_index_intro'                             => 'Budgetar används för att hantera din ekonomi och utgör en av kärnfunktionerna i Firefly III.',
    'budgets_index_set_budget'                        => 'Ställ in din totala budget för varje period så att Firefly III kan berätta om du har budgeterat alla tillgängliga pengar.',
    'budgets_index_see_expenses_bar'                  => 'Att spendera pengar kommer långsamt att fylla det här fältet.',
    'budgets_index_navigate_periods'                  => 'Navigera genom perioder för att enkelt kunna sätta budgetar i god tid.',
    'budgets_index_new_budget'                        => 'Skapa en ny budget som du tycker passar.',
    'budgets_index_list_of_budgets'                   => 'Använd denna tabell för att ställa in beloppen för varje budget och se hur det står till.',
    'budgets_index_outro'                             => 'För att lära dig mer om budgetering, kolla in hjälpikonen i det övre högra hörnet.',

    // reports (index)
    'reports_index_intro'                             => 'Använd dessa rapporter för att få detaljerad insikt i din ekonomi.',
    'reports_index_inputReportType'                   => 'Välj en rapporttyp. Se hjälpsidorna för att se vad varje rapport visar.',
    'reports_index_inputAccountsSelect'               => 'Det går att exkluder eller inkludera tillgångskonton som du tycker passar.',
    'reports_index_inputDateRange'                    => 'Valt datumintervall är helt upp till dig: från en dag till 10 år.',
    'reports_index_extra-options-box'                 => 'Beroende på vilken rapport du har valt kan du välja extra filter och alternativ här. Se den här rutan när du ändrar rapporttyper.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Denna rapport ger dig en snabb och omfattande översikt över din ekonomi. Om du vill se något annat, vänligen snälla inte kontakta mig!',
    'reports_report_audit_intro'                      => 'Denna rapport ger dig detaljerad insikt i dina tillgångskonton.',
    'reports_report_audit_optionsBox'                 => 'Använd dessa kryssrutor för att visa eller dölja de kolumner du är intresserad av.',

    'reports_report_category_intro'                  => 'Denna rapport ger dig inblick i en eller flera kategorier.',
    'reports_report_category_pieCharts'              => 'Dessa diagram ger dig inblick i utgifter och inkomst per kategori eller per konto.',
    'reports_report_category_incomeAndExpensesChart' => 'Detta diagram visar dina utgifter och inkomst per kategori.',

    'reports_report_tag_intro'                  => 'Denna rapport ger dig inblick i en eller flera taggar.',
    'reports_report_tag_pieCharts'              => 'Dessa diagram ger dig inblick i utgifter och inkomst per tagg, konto, kategori eller budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'Detta diagram visar dina utgifter och inkomst per etikett.',

    'reports_report_budget_intro'                             => 'Denna rapport ger dig inblick i en eller flera budgetar.',
    'reports_report_budget_pieCharts'                         => 'Dessa diagram ger dig inblick i utgifter per budget eller per konto.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Detta diagram visar dina utgifter per budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Använd dessa knappar för att snabbt växla vilken typ av transaktion du vill spara.',
    'transactions_create_ffInput_category'                    => 'Du kan skriva fritt i det här fältet. Tidigare skapade kategorier kommer att föreslås.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Länka ditt uttag till en budget för bättre ekonomisk kontroll.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Använd den här listrutan när ditt uttag är i en annan valuta.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Använd den här listrutan när din insättning är i en annan valuta.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Välj en spargris och länka denna överföring till dina besparingar.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Det här fältet visar hur mycket du har sparat i varje spargris.',
    'piggy-banks_index_button'                                => 'Bredvid framstegsfältet finns två knappar (+ och -) för att lägga till eller ta bort pengar från varje spargris.',
    'piggy-banks_index_accountStatus'                         => 'För varje tillgångskonto med minst en spargris listas status i denna tabell.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Vad är ditt mål? En ny soffa, en kamera, pengar för nödsituationer?',
    'piggy-banks_create_date'                                 => 'Du kan ställa in ett måldatum eller en tidsfrist för din spargris.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Detta diagram visar historiken för denna spargris.',
    'piggy-banks_show_piggyDetails'                           => 'Information om din spargris',
    'piggy-banks_show_piggyEvents'                            => 'Eventuella tillägg eller borttagningar listas också här.',

    // bill index
    'bills_index_rules'                                       => 'Här ser du vilka regler kontrollerar om denna räkning har träffats',
    'bills_index_paid_in_period'                              => 'Det här fältet anger när räkningen betalades.',
    'bills_index_expected_in_period'                          => 'Detta fält anger för varje faktura om och när nästa räkning förväntas komma.',

    // show bill
    'bills_show_billInfo'                                     => 'Den här tabellen visar allmän information om denna räkning.',
    'bills_show_billButtons'                                  => 'Använd den här knappen för att skanna om gamla transaktioner så att de kommer att matchas med den här räkningen.',
    'bills_show_billChart'                                    => 'Detta diagram visar transaktionerna kopplade till denna räkning.',

    // create bill
    'bills_create_intro'                                      => 'Använd räkningar för att följa hur mycket pengar du spenderar varje period. Tänk på utgifter som hyror, försäkringar eller låneränter.',
    'bills_create_name'                                       => 'Använd ett beskrivande namn som "Hyra" eller "Sjukförsäkring".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Välj ett lägsta och högsta belopp för denna räkning.',
    'bills_create_repeat_freq_holder'                         => 'De flesta räkningar upprepas varje månad, men du kan ställa in en annan frekvens här.',
    'bills_create_skip_holder'                                => 'Om en räkning upprepas varannan vecka bör fältet "hoppa över" ställas in på "1" för att hoppa över varannan vecka.',

    // rules index
    'rules_index_intro'                                       => 'Med Firefly III kan du hantera regler som automatiskt tillämpas på alla transaktioner du skapar eller redigerar.',
    'rules_index_new_rule_group'                              => 'Du kan kombinera regler i grupper för enklare hantering.',
    'rules_index_new_rule'                                    => 'Skapa så många regler du vill.',
    'rules_index_prio_buttons'                                => 'Sortera dem som du vill.',
    'rules_index_test_buttons'                                => 'Du kan testa dina regler eller tillämpa dem på befintliga transaktioner.',
    'rules_index_rule-triggers'                               => 'Regler har "utlösare" och "åtgärder" som du kan sortera genom att dra och släppa.',
    'rules_index_outro'                                       => 'Var noga med att kolla in hjälpsidorna med hjälp av ikonen (?) Längst upp till höger!',

    // create rule:
    'rules_create_mandatory'                                  => 'Välj en beskrivande titel och ställ in när regeln ska avfyras.',
    'rules_create_ruletriggerholder'                          => 'Lägg till så många utlösare som du vill, men kom ihåg att ALLA utlösare måste matcha innan några handlingar körs.',
    'rules_create_test_rule_triggers'                         => 'Använd den här knappen för att se vilka transaktioner som matchar din regel.',
    'rules_create_actions'                                    => 'Ställ in så många åtgärder du vill.',

    // preferences
    'preferences_index_tabs'                                  => 'Fler alternativ finns bakom dessa flikar.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III stöder flera valutor, som du kan ändra på den här sidan.',
    'currencies_index_default'                                => 'Firefly III har en standardvaluta.',
    'currencies_index_buttons'                                => 'Använd dessa knappar för att ändra standardvaluta eller aktivera andra valutor.',

    // create currency
    'currencies_create_code'                                  => 'Den här koden ska vara ISO-kompatibel (Google den för din nya valuta).',
];
