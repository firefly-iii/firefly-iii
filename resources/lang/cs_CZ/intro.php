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
    'index_intro'                                     => 'Vítejte na titulní stránce Firefly III. Věnujte čas projití se tímto úvodem, abyste se dozvěděli, jak Firefly III funguje.',
    'index_accounts-chart'                            => 'Tento graf zobrazuje stávající zůstatky vašich majetkových účtů. Jaké účty se zde mají zobrazovat lze nastavit v předvolbách.',
    'index_box_out_holder'                            => 'Tato malá oblast a ty další vedle něho podávají rychlý přehled vaší finanční situace.',
    'index_help'                                      => 'Pokud budete potřebovat nápovědu ke stránce nebo formuláři, klikněte na toto tlačítko.',
    'index_outro'                                     => 'Většina stránek Firefly III začíná krátkou prohlídkou, jako je tato. Obraťte se na mně, pokud máte dotazy nebo komentáře. Ať poslouží!',
    'index_sidebar-toggle'                            => 'Nabídku pod touto ikonou použijte pro vytváření nových transakcí, účtů a ostatní věcí.',
    'index_cash_account'                              => 'Toto jsou doposud vytvořené účty. Hotovostní účet můžete použít pro sledování výdajů v hotovosti, ale není to pochopitelně povinné.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Vyberte svůj oblíbený účet aktiv nebo závazků z této rozbalovací nabídky.',
    'transactions_create_withdrawal_destination'      => 'Zde vyberte výdajový účet. Nevyplňujte, pokud chcete vydat v hotovosti.',
    'transactions_create_withdrawal_foreign_currency' => 'Tuto kolonku použijte pro zadání cizí měny a částky.',
    'transactions_create_withdrawal_more_meta'        => 'Plenty of other meta data you set in these fields.',
    'transactions_create_withdrawal_split_add'        => 'Pokud chcete transakci rozúčtovat, přidejte další rozúčtování pomocí tohoto tlačítka',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Select or type the payee in this auto-completing dropdown/textbox. Leave it empty if you want to make a cash deposit.',
    'transactions_create_deposit_destination'         => 'Zde vyberte účet aktiv nebo závazků.',
    'transactions_create_deposit_foreign_currency'    => 'Tuto kolonku použijte pro zadání cizí měny a částky.',
    'transactions_create_deposit_more_meta'           => 'Plenty of other meta data you set in these fields.',
    'transactions_create_deposit_split_add'           => 'Pokud chcete transakci rozúčtovat, přidejte další rozúčtování pomocí tohoto tlačítka',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Select the source asset account here.',
    'transactions_create_transfer_destination'        => 'Select the destination asset account here.',
    'transactions_create_transfer_foreign_currency'   => 'Use this field to set a foreign currency and amount.',
    'transactions_create_transfer_more_meta'          => 'Plenty of other meta data you set in these fields.',
    'transactions_create_transfer_split_add'          => 'If you want to split a transaction, add more splits with this button',

    // create account:
    'accounts_create_iban'                            => 'Zadejte u svých účtů platný IBAN identifikátor. To by v budoucnu mohlo velmi ulehčit import dat.',
    'accounts_create_asset_opening_balance'           => 'Majetkové účty mohou mít „počáteční zůstatek“, označující začátek historie tohoto účtu ve Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III podporuje vícero měn. Majetkové účty mají jednu hlavní měnu, kterou je třeba nastavit zde.',
    'accounts_create_asset_virtual'                   => 'Někdy se může hodit dát svému účtu virtuální zůstatek: extra částku, vždy přičítanou nebo odečítanou od stávajícího zůstatku.',

    // budgets index
    'budgets_index_intro'                             => 'Rozpočty slouží ke správě vašich financí a tvoří jednu z hlavních funkcí Firefly III.',
    'budgets_index_set_budget'                        => 'Nastavte celkový rozpočet pro každé z období a Firefly III vám sdělí, pokud jste vyčerpali všechny dostupné peníze.',
    'budgets_index_see_expenses_bar'                  => 'Utracené peníze budou zvolna plnit tento pruh.',
    'budgets_index_navigate_periods'                  => 'Procházejte obdobími a jednoduše nastavujte rozpočty dopředu.',
    'budgets_index_new_budget'                        => 'Vytvářejte nové rozpočty, jak uznáte za vhodné.',
    'budgets_index_list_of_budgets'                   => 'Použijte tuto tabulku k nastavení částek pro každý rozpočet a zjistěte, jak na tom jste.',
    'budgets_index_outro'                             => 'Pokud se chcete dozvědět více o tvorbě rozpočtů, klikněte na ikonu nápovědy v pravém horním rohu.',

    // reports (index)
    'reports_index_intro'                             => 'Pomocí těchto přehledů získáte podrobné informace o svých financích.',
    'reports_index_inputReportType'                   => 'Vyberte typ přehledu. Podívejte se na stránky nápovědy a zjistěte, co vám každý přehled ukazuje.',
    'reports_index_inputAccountsSelect'               => 'Můžete vynechávat nebo zahrnovat majetkové účty, jak potřebujete.',
    'reports_index_inputDateRange'                    => 'Vybrané časové období je zcela na vás: od jednoho dne do deseti let.',
    'reports_index_extra-options-box'                 => 'Podle toho, jaký výkaz jste vybrali, je zde možné vybrat další filtry a volby. Při změně typu výkazu sledujte tuto oblast.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Tento výkaz vám podá rychlý a podrobný přehled vašich financí. Pokud chcete vidět něco jiného, neváhejte se na mne obrátit!',
    'reports_report_audit_intro'                      => 'Tento výkaz vám podá podrobný vhled do vašich majetkových účtů.',
    'reports_report_audit_optionsBox'                 => 'Pomocí těchto zaškrtávacích kolonek zobrazujte nebo skrývejte sloupce, které vás (ne)zajímají.',

    'reports_report_category_intro'                  => 'Tato sestava vám podá vhled do jedné nebo více kategorií.',
    'reports_report_category_pieCharts'              => 'Tyto grafy vám podají vhled do výdajů a příjmů pro jednotlivé kategorie nebo účty.',
    'reports_report_category_incomeAndExpensesChart' => 'Tento graf zobrazuje vaše náklady a příjmy v jednotlivých kategoriích.',

    'reports_report_tag_intro'                  => 'Tato sestava vám podává vhled do jednoho nebo více štítků.',
    'reports_report_tag_pieCharts'              => 'Tyto grafy vám podávají vhled do nákladů a příjmů pro jednotlivé štítky, účty, kategorie nebo rozpočty.',
    'reports_report_tag_incomeAndExpensesChart' => 'Tento graf zobrazuje vaše výdaje a příjmy pro každý štítek.',

    'reports_report_budget_intro'                             => 'Tato sestava vám dává vhled do jednoho nebo více rozpočtů.',
    'reports_report_budget_pieCharts'                         => 'Tyto grafy vám podají vhled do výdajů pro jednotlivé rozpočty nebo účty.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Tento graf zobrazuje vaše výdaje v jednotlivých rozpočtech.',

    // create transaction
    'transactions_create_switch_box'                          => 'Pomocí těchto tlačítek můžete rychle přepínat typ transakce, kterou chcete uložit.',
    'transactions_create_ffInput_category'                    => 'Do této kolonky si můžete napsat, co chcete. Budou navrhovány dříve vytvořené kategorie.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Propojte svůj výběr s rozpočtem a získáte lepší kontrolu nad financemi.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Tuto rozbalovací nabídku použijte pokud je váš výběr v jiné měně.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Tuto rozbalovací nabídku použijte, pokud je váš vklad v jiné měně.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Vyberte pokladničku a propojte tento převod se svými úsporami.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Tato kolonka zobrazuje, kolik jste naspořili v každé z pokladniček.',
    'piggy-banks_index_button'                                => 'Vedle tohoto ukazatele postupu se nachází dvě tlačítka (+ a -) pro přidání nebo odebrání peněz z každé z pokladniček.',
    'piggy-banks_index_accountStatus'                         => 'Pro každý majetkový účet s alespoň jednou pokladničkou je v této tabulce vypsán stav.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Co je vašim cílem? Nová pohovka, fotoaparát, rezerva pro nečekané výdaje?',
    'piggy-banks_create_date'                                 => 'Pro pokladničku je možné nastavit cílové datum nebo termín.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Tento graf bude zobrazovat historii vaší pokladničky.',
    'piggy-banks_show_piggyDetails'                           => 'Nějaké podrobnosti o vaší pokladničce',
    'piggy-banks_show_piggyEvents'                            => 'Jsou zde uvedeny také všechny přírůstky i odebrání.',

    // bill index
    'bills_index_rules'                                       => 'Zde vidíte která pravidla budou zkontrolována pokud je tento účet zasažen',
    'bills_index_paid_in_period'                              => 'Tato kolonka označuje, kdy byla účtenka/faktura naposledy zaplacena.',
    'bills_index_expected_in_period'                          => 'Tato kolonka u každé faktury označuje zda a kdy je očekávána další.',

    // show bill
    'bills_show_billInfo'                                     => 'Tato tabulka zobrazuje některé obecné informace o této faktuře.',
    'bills_show_billButtons'                                  => 'Toto tlačítko slouží k opětovnému prohledání starých transakcí, takže bude hledána shoda s touto účtenkou.',
    'bills_show_billChart'                                    => 'Tento graf zobrazuje transakce spojené s touto fakturou.',

    // create bill
    'bills_create_intro'                                      => 'Faktury používejte pro sledování částek, které máte v každém z období zaplatit. Jedná se výdaje jako nájem, pojištění nebo splátky hypotéky.',
    'bills_create_name'                                       => 'Zadejte výstižný název, jako „Nájem“ nebo „Životní pojištění“.',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Vyberte nejnižší a nejvyšší částku pro tuto fakturu.',
    'bills_create_repeat_freq_holder'                         => 'Většina plateb se opakuje měsíčně, ale je zde možné nastavit i jinou frekvenci.',
    'bills_create_skip_holder'                                => 'Pokud se platba opakuje každé dva týdny, kolonka „přeskočit“ by měla být nastavená na „1“, aby byl vynechán každý druhý týden.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III umožňuje spravovat pravidla, která budou automaticky uplatňována na všechny transakce které vytvoříte nebo upravíte.',
    'rules_index_new_rule_group'                              => 'Pro snazší zprávu je pravidla možné kombinovat ve skupinách.',
    'rules_index_new_rule'                                    => 'Vytvořte tolik pravidel, kolik chcete.',
    'rules_index_prio_buttons'                                => 'Seřaďte je tak, jak se vám to hodí.',
    'rules_index_test_buttons'                                => 'Pravidla je možné vyzkoušet nebo uplatnit na existující transakce.',
    'rules_index_rule-triggers'                               => 'Pravidla mají „spouštěče“ a „akce“. Jejich pořadí je možné měnit jejich přetahováním.',
    'rules_index_outro'                                       => 'Podívejte se do nápovědy (ikona otazníku v pravém horním rohu)!',

    // create rule:
    'rules_create_mandatory'                                  => 'Zvolte výstižný název a nastavte, kdy má být pravidlo spuštěno.',
    'rules_create_ruletriggerholder'                          => 'Přidejte tolik spouštěčů, kolik potřebujete, ale pamatujte, že aby byla spuštěná jakákoli akce je třeba, aby byly splněny podmínky VŠECH nastavených spouštěčů.',
    'rules_create_test_rule_triggers'                         => 'Toto tlačítko slouží ke zobrazení transakcí, které odpovídají pravidlu.',
    'rules_create_actions'                                    => 'Nastavte tolik akcí, kolik chcete.',

    // preferences
    'preferences_index_tabs'                                  => 'Další volby jsou k dispozici v kartách.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III podporuje více měn, které můžete měnit na této stránce.',
    'currencies_index_default'                                => 'Firefly III má jednu výchozí měnu.',
    'currencies_index_buttons'                                => 'Tato tlačítka slouží pro změnu výchozí měny nebo pro zapnutí dalších měn.',

    // create currency
    'currencies_create_code'                                  => 'Tento kód by měl být v souladu s normou ISO (vyhledejte si pro novou měnu).',
];
