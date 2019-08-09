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
    'index_intro'                                     => 'Ez a Firefly III kezdőoldala. Érdemes némi időt szánni ennek a bemutatónak a megtekintésére a Firefly III alapjainak megismeréséhez.',
    'index_accounts-chart'                            => 'Ezen a grafikon az eszközszámlák aktuális egyenlege látható. A grafikonon megjelenő bankszámlákat a beállításokban lehet kiválasztani.',
    'index_box_out_holder'                            => 'Ez a kis doboz és a mellette láthatóak gyors áttekintést nyújtanak a pénzügyi helyzetről.',
    'index_help'                                      => 'Ezt a gombot megnyomva lehet segítséget kérni egy oldal vagy egy űrlap használatához.',
    'index_outro'                                     => 'A Firefly III legtöbb oldala egy ilyen rövid bemutatóval kezdődik. Kérdés vagy észrevét esetén szívesen állok rendelkezésre. Kellemes használatot!',
    'index_sidebar-toggle'                            => 'Az ez alatt az ikon alatt megnyíló menü használható új tranzakciók, bankszámlák vagy egyéb dolgok létrehozásához.',
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
    'accounts_create_iban'                            => 'Érvényes IBAN hozzáadása a számlához. Ez a jövőben nagyon egyszerűvé teheti az adatok importálását.',
    'accounts_create_asset_opening_balance'           => 'Az eszközszámlák rendelkezhetnek egy „nyitó egyenleggel” ami a számla történetének kezdetét jelzi a Firefly III-ban.',
    'accounts_create_asset_currency'                  => 'A Firefly III több pénznemet támogat. Az eszközszámláknak van egy fő pénzneme, amelyet itt kell beállítani.',
    'accounts_create_asset_virtual'                   => 'Időnként segíthet egy virtuális egyenleget adni a bankszámlához: ez egy további összeg ami az aktuális egyenleghez mindig hozzáadódik vagy kivonásra kerül.',

    // budgets index
    'budgets_index_intro'                             => 'A költségkeretek a pénzügyek kezelésére szolgálnak, és a Firefly III egyik alapvető funkcióját képezik.',
    'budgets_index_set_budget'                        => 'Ha a teljes költségkeret minden időszakra be van állítva, a Firefly III megmondhatja, ha az összes rendelkezésre álló pénz fel lett használva.',
    'budgets_index_see_expenses_bar'                  => 'A pénzköltés lassan fel fogja tölteni ezt a sávot.',
    'budgets_index_navigate_periods'                  => 'Az időszakokon átnavigálva könnyedén, még idő előtt be lehet állítani a költségkereteket.',
    'budgets_index_new_budget'                        => 'Új költségkeretet létrehozása.',
    'budgets_index_list_of_budgets'                   => 'Ezen a táblán lehet beállítani a költségkeretek összegeit és áttekinteni, hogy hogyan állnak.',
    'budgets_index_outro'                             => 'A költségkeretek használatáról további információk a jobb felső sarokban található súgó ikon alatt találhatóak.',

    // reports (index)
    'reports_index_intro'                             => 'Ezek a jelentések részletes betekintést biztosítanak a pénzügyekbe.',
    'reports_index_inputReportType'                   => 'Jelentéstípus kiválasztása. A súgóoldalakon megtalálható, hogy az egyes jelentések mit mutatnak meg.',
    'reports_index_inputAccountsSelect'               => 'Szükség szerint lehet kizárni vagy hozzáadni eszközfiókokat.',
    'reports_index_inputDateRange'                    => 'Tetszőleges dátumtartományt lehet választani, egy naptól 10 évig.',
    'reports_index_extra-options-box'                 => 'A kiválasztott jelentéstől függően további szűrők és beállítások választhatóak. Ezek ebben a dobozban fognak megjelenni.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Ez a jelentés egy gyors és átfogó képet ad a pénzügyekről. Ha bármi másnak szerepelni kéne rajta vedd fel velem a kapcsolatot!',
    'reports_report_audit_intro'                      => 'Ez a jelentés részletes betekintést nyújt az eszközszámlákba.',
    'reports_report_audit_optionsBox'                 => 'A jelölőnégyzetek használatával lehet megjeleníteni vagy elrejteni az egyes oszlopokat.',

    'reports_report_category_intro'                  => 'Ez a jelentés egy vagy több kategóriában nyújt betekintést.',
    'reports_report_category_pieCharts'              => 'Ezek a diagramok áttekintést nyújtanak a költségekről és a bevételekről, kategóriánként vagy bankszámlákként.',
    'reports_report_category_incomeAndExpensesChart' => 'Ez a diagram a kategóriákon belüli költségeket és jövedelmeket mutatja.',

    'reports_report_tag_intro'                  => 'Ez a jelentés egy vagy több címkébe nyújt betekintést.',
    'reports_report_tag_pieCharts'              => 'Ezek a diagramok áttekintést nyújtanak a költségekről és a bevételekről, címkékként, bankszámlákként, kategóriákként vagy költségkeretenként.',
    'reports_report_tag_incomeAndExpensesChart' => 'Ez a diagram a kiadásokat és a bevételeket mutatja címkék szerint.',

    'reports_report_budget_intro'                             => 'Ez a jelentés betekintést nyújt egy vagy több költségkeretbe.',
    'reports_report_budget_pieCharts'                         => 'Ezek a diagramok betekintést nyújtanak a költségekbe költségkeretenként vagy számlánként.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Ez a táblázat a költségkeretenkénti költségeket mutatja.',

    // create transaction
    'transactions_create_switch_box'                          => 'Ezzel a gombbal lehet gyorsan átkapcsolni a menteni kívánt tranzakció típusát.',
    'transactions_create_ffInput_category'                    => 'Ebbe a mezőbe szabadon lehet gépelni. A korábban létrehozott kategóriák javaslatként fognak megjelenni.',
    'transactions_create_withdrawal_ffInput_budget'           => 'A költségek költségkeret kapcsolásával jobb pénzügyi kontroll érhető el.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Ezzel a legördülő listával lehet más pénznemet beállítani a költséghez.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Ezzel a legördülő listával lehet más pénznemet beállítani a bevételhez.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Egy malacpersely kiválasztása és az átvezetés hozzákapcsolása megtakarításként.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Ez a mező azt mutatja, hogy menni a megtakarítás a malacperselyekben.',
    'piggy-banks_index_button'                                => 'A folyamatsáv mellett két gomb található (+ és -) melyekkel pénzt lehet a malacperselyekbe betenni vagy kivenni onnan.',
    'piggy-banks_index_accountStatus'                         => 'A legalább egy malacpersellyel rendelkező eszközszámlák állapota ebben a listában jelenik meg.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Mi a cél? Egy új kanapé, egy kamera, pénz a vészhelyzetekre?',
    'piggy-banks_create_date'                                 => 'A malacperselyhez meg kell adni egy céldátumot vagy egy határidőt.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Ez a diagram a malacpersely történetét mutatja meg.',
    'piggy-banks_show_piggyDetails'                           => 'Néhány részlet a malacperselyről',
    'piggy-banks_show_piggyEvents'                            => 'Itt minden hozzáadás vagy kivétel is fel lesz sorolva.',

    // bill index
    'bills_index_rules'                                       => 'Itt lehet látni, hogy mely szabályok lesznek ellenőrizve ezen a számlán',
    'bills_index_paid_in_period'                              => 'Ez a mező jelzi, hogy a számla mikor volt utoljára befizetve.',
    'bills_index_expected_in_period'                          => 'A mező azt mutatja meg a számláknál, hogy a következő számla várhatóan mikor esedékes.',

    // show bill
    'bills_show_billInfo'                                     => 'Ez a táblázat néhány általános információt tartalmaz erről a számláról.',
    'bills_show_billButtons'                                  => 'Ezzel a gombbal lehet újraolvasni a tranzakciókat, hogy egyeztetve legyenek a számlával.',
    'bills_show_billChart'                                    => 'Ez a diagram a számlához kapcsolódó tranzakciókat mutatja.',

    // create bill
    'bills_create_intro'                                      => 'A számlákkal lehet nyomon követni az egyes időszakokban fizetendő pénzösszegeket. Ezek lehetnek bérleti, biztosítási vagy jelzálog díjak.',
    'bills_create_name'                                       => 'Érdemes leíró nevet használni, mint például a „Bérleti díj” vagy "Egészségbiztosítás".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Ki kell választani egy minimális és egy maximális összeget ehhez a számlához.',
    'bills_create_repeat_freq_holder'                         => 'A legtöbb számla havonta ismétlődik, de más rendszeresség is beállítható.',
    'bills_create_skip_holder'                                => 'Ha egy számla 2 hetente ismétlődik, akkor a „kihagyás” mezőt „1”-re kell állítani, hogy minden második hetet kihagyja.',

    // rules index
    'rules_index_intro'                                       => 'A Firefly III lehetővé teszi szabályok kezelését melyek automágikusan alkalmazva lesznek az összes létrehozott vagy szerkesztett tranzakcióra.',
    'rules_index_new_rule_group'                              => 'A szabályokat csoportokba lehet rendezni a könnyebb kezelhetőség érdekében.',
    'rules_index_new_rule'                                    => 'Bármennyi szabályt létre lehet hozni.',
    'rules_index_prio_buttons'                                => 'Bármely módon rendezni lehet őket.',
    'rules_index_test_buttons'                                => 'A szabályokat lehet tesztelni vagy alkalmazni a már meglévő tranzakciókra.',
    'rules_index_rule-triggers'                               => 'A szabályokhoz „eseményindítók” és „műveletek” tartozhatnak, melyeket húzással lehet sorba rendezni.',
    'rules_index_outro'                                       => 'A súgóoldalak a jobb felső sarokban a (?) ikon alatt találhatóak!',

    // create rule:
    'rules_create_mandatory'                                  => 'Egy informatív címet kell választani és beállítani, hogy a szabálynak mikor kell lefutnia.',
    'rules_create_ruletriggerholder'                          => 'Bármennyi eseményindító megadható, de meg kell jegyezni, hogy MINDEN eseményindítónak egyeznie kell a műveletek végrehajtása előtt.',
    'rules_create_test_rule_triggers'                         => 'Ezzel a gombbal meg lehet nézni, hogy mely tranzakciók felelnek meg a szabálynak.',
    'rules_create_actions'                                    => 'Bármennyi műveletet be lehet állítani.',

    // preferences
    'preferences_index_tabs'                                  => 'A fülek mögött több beállítása lehetőség áll rendelkezésre.',

    // currencies
    'currencies_index_intro'                                  => 'A Firefly III több pénznemet támogat, melyeket ezen az oldalon lehet módosítani.',
    'currencies_index_default'                                => 'A Firefly III-nak csak egy alapértelmezett pénzneme van.',
    'currencies_index_buttons'                                => 'Ezzel a gombbal lehet módosítani az alapértelmezés szerinti pénznemet vagy más pénznemeket engedélyezni.',

    // create currency
    'currencies_create_code'                                  => 'Ennek a kódnak ISO kompatibilisnek kell lennie (új pénznemnél érdemes a Google-t használni).',
];
