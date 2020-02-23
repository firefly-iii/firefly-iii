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
    'index_intro'                                     => 'Tervetuloa Firefly III:n hakemistosivulle. Käytä hetki aikaa käydäksesi läpi tämän esittelyn ja saadaksesi kuvan siitä, kuinka Firefly III toimii.',
    'index_accounts-chart'                            => 'Tämä kaavio näyttää omaisuustiliesi tämänhetkisen saldon. Voit valita täällä näkyvät tilit asetuksista.',
    'index_box_out_holder'                            => 'Tämä pieni laatikko ja tämän vieressä olevat laatikot antavat sinulle nopean yleiskuvan taloudellisesta tilanteestasi.',
    'index_help'                                      => 'Jos koskaan tarvitset apua sivun tai lomakkeen kanssa, paina tätä painiketta.',
    'index_outro'                                     => 'Useimmat Firefly III -sivut alkavat tällaisella pienellä opastuksella. Ota minuun yhteyttä, kun sinulla on kysyttävää tai kommentteja. Nauti!',
    'index_sidebar-toggle'                            => 'Luo uusia tapahtumia, tilejä tai muita juttuja käyttämällä tämän kuvakkeen alla olevaa valikkoa.',
    'index_cash_account'                              => 'Tässä ovat tähän mennessä luodut tilit. Käteistilillä voit seurata käteiskuluja, mutta se ei tietenkään ole pakollista.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Valitse suosikkiomaisuustilisi tai -vastuutilisi tästä alasvetovalikosta.',
    'transactions_create_withdrawal_destination'      => 'Valitse tästä kulutustili. Jätä se tyhjäksi, jos haluat tehdä käteismaksun.',
    'transactions_create_withdrawal_foreign_currency' => 'Tämän kentän avulla voit asettaa valuutan ja summan.',
    'transactions_create_withdrawal_more_meta'        => 'Paljon muita lisätietoja joita voit asettaa näissä kentissä.',
    'transactions_create_withdrawal_split_add'        => 'Jos haluat jakaa tapahtuman useampaan osaan, lisää osia tällä painikkeella',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Valitse tai kirjoita maksunsaaja tässä automaattitäydennys-pudotusvalikko-tekstikentässä. Jätä kenttä tyhjäksi jos haluat tehdä käteistalletuksen.',
    'transactions_create_deposit_destination'         => 'Valitse omaisuus- tai vastuutili täältä.',
    'transactions_create_deposit_foreign_currency'    => 'Tämän kentän avulla voit asettaa ulkomaan valuutan ja summan.',
    'transactions_create_deposit_more_meta'           => 'Paljon muita lisätietoja joita voit asettaa näissä kentissä.',
    'transactions_create_deposit_split_add'           => 'Jos haluat jakaa tapahtuman useampaan osaan, lisää tapahtumaan osia tällä painikkeella',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Valitse lähdetili täältä.',
    'transactions_create_transfer_destination'        => 'Valitse kohdetili täältä.',
    'transactions_create_transfer_foreign_currency'   => 'Tämän kentän avulla voit asettaa ulkomaan valuutan ja summan.',
    'transactions_create_transfer_more_meta'          => 'Paljon muita lisätietoja joita voit asettaa näissä kentissä.',
    'transactions_create_transfer_split_add'          => 'Jos haluat jakaa tapahtuman useampaan osaan, lisää tapahtumaan osia tällä painikkeella',

    // create account:
    'accounts_create_iban'                            => 'Anna tilillesi kelvollinen IBAN-tunnus. Tämä voi tehdä tietojen automaattisesta tuonnista jatkossa tosi helppoa.',
    'accounts_create_asset_opening_balance'           => 'Omaisuustilillä voi olla "alkusaldo", joka ilmaisee tilin historian alkamisen Firefly III:ssa.',
    'accounts_create_asset_currency'                  => 'Firefly III tukee useita valuuttoja. Omaisuustilillä on yksi päävaluutta, joka täytyy asettaa tässä.',
    'accounts_create_asset_virtual'                   => 'Joskus voi olla hyödyllistä antaa tilille virtuaalinen saldo: lisäsumma joka aina lisätään tai vähennetään todellisesta saldosta.',

    // budgets index
    'budgets_index_intro'                             => 'Budjetteja käytetään talouden hallintaan ja ne muodostavat yhden Firefly III:n ydintoiminnoista.',
    'budgets_index_set_budget'                        => 'Aseta kokonaisbudjettisi jokaiselle jaksolle, jotta Firefly III voi kertoa, oletko budjetoinut kaikki käytettävissä olevat rahat.',
    'budgets_index_see_expenses_bar'                  => 'Rahan kulutus täyttää hitaasti tämän palkin.',
    'budgets_index_navigate_periods'                  => 'Selaa ajanjaksoja ja määritä helposti budjetteja etukäteen.',
    'budgets_index_new_budget'                        => 'Luo uusia budjetteja mielesi mukaan.',
    'budgets_index_list_of_budgets'                   => 'Tämän taulukon avulla voit asettaa summat jokaiselle budjetille ja nähdä miten sinulla menee.',
    'budgets_index_outro'                             => 'Lisätietoja budjetoinnista saat tutustumalla oikeassa yläkulmassa olevaan ohjekuvakkeeseen.',

    // reports (index)
    'reports_index_intro'                             => 'Näiden raporttien avulla saat yksityiskohtaista tietoa taloudestasi.',
    'reports_index_inputReportType'                   => 'Valitse raporttityyppi. Katso ohjesivuilta, mitä kukin raportti näyttää sinulle.',
    'reports_index_inputAccountsSelect'               => 'Voit sisällyttää tai olla näyttämättä tilejä mielesi mukaan.',
    'reports_index_inputDateRange'                    => 'Valittu ajanjakso on täysin sinun hallinnassasi: yhdestä päivästä 10 vuoteen.',
    'reports_index_extra-options-box'                 => 'Valitsemastasi raportista riippuen voit valita täältä lisäsuodattimia ja -vaihtoehtoja. Katso tätä ruutua, kun muutat raporttityyppejä.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Tämä raportti antaa sinulle nopean ja kattavan yleiskatsauksen taloudestasi. Jos haluat nähdä jotain muuta, älä epäröi ottaa minuun yhteyttä!',
    'reports_report_audit_intro'                      => 'Tämä raportti antaa sinulle yksityiskohtaisen kuvan omaisuustileistäsi.',
    'reports_report_audit_optionsBox'                 => 'Näiden valintaruutujen avulla voit näyttää kiinnostavat tai piilottaa vähemmän kiinnostavat sarakkeet.',

    'reports_report_category_intro'                  => 'Tällä raportilla pääset tarkastelemaan talouttasi yhden tai useamman kategorian kannalta.',
    'reports_report_category_pieCharts'              => 'Nämä kaaviot antavat sinulle tietoa kustannuksista ja tuloista kategorioittain tai tileittäin.',
    'reports_report_category_incomeAndExpensesChart' => 'Tämä taulukko näyttää kulut ja tulot kategorioittain.',

    'reports_report_tag_intro'                  => 'Tämä raportti antaa sinulle tietoa yhdestä tai useammasta tägistä.',
    'reports_report_tag_pieCharts'              => 'Nämä kaaviot antavat sinulle tietoa kustannuksista ja tuloista tägeittäin, tileittäin, kategorioittain tai budjeteittain.',
    'reports_report_tag_incomeAndExpensesChart' => 'Tämä taulukko näyttää kulusi ja tulosi tägeittäin.',

    'reports_report_budget_intro'                             => 'Tämä raportti antaa sinulle tietoa yhdestä tai useammasta budjetista.',
    'reports_report_budget_pieCharts'                         => 'Nämä kaaviot antavat sinulle kuvan kustannuksista budjettia tai tiliä kohden.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Tämä taulukko näyttää kulut budjeteittain.',

    // create transaction
    'transactions_create_switch_box'                          => 'Näillä painikkeilla saat nopeasti vaihdettua tallennettavan tapahtuman tyypin.',
    'transactions_create_ffInput_category'                    => 'Voit vapaasti kirjoittaa tähän kenttään. Saat kirjoittaessasi automaattiseti ehdotuksia, joista voit valita aikaisemmin luotuja kategorioita.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Yhdistämällä nostosi budjettiin helpotat taloudenhoitoasi.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Tästä pudotusvalikosta voit valita nostollesi toisen valuutan.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Tästä pudotusvalikosta voit valita talletuksellesi toisen valuutan.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Valitse säästöpossu ja yhdistä tämä siirto säästöihisi.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Tämä kenttä näyttää kuinka paljon olet säästänyt kuhunkin säästöpossuun.',
    'piggy-banks_index_button'                                => 'Tämän edistymispalkin vieressä on kaksi painiketta (+ ja -) lisäämään tai poistamaan rahaa kustakin säästöpossusta.',
    'piggy-banks_index_accountStatus'                         => 'Tässä taulukossa listataan kaikkien niiden omaisuustilien tila, joilla on vähintään yksi säästöpossu.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Mikä on tavoitteesi? Uusi sohva, kamera, rahaa hätätilanteisiin?',
    'piggy-banks_create_date'                                 => 'Voit asettaa tavoitepäivän tai takarajan säästöpossullesi.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Tämä kuvaaja näyttää tämän possun kehityksen.',
    'piggy-banks_show_piggyDetails'                           => 'Joitain yksityiskohtia säästöpossustasi',
    'piggy-banks_show_piggyEvents'                            => 'Mahdolliset lisäykset tai poistot luetellaan myös tässä.',

    // bill index
    'bills_index_rules'                                       => 'Täältä näet tämän laskun ehtojen tarkistussäännöt',
    'bills_index_paid_in_period'                              => 'Tämä kenttä osoittaa, milloin lasku viimeksi maksettiin.',
    'bills_index_expected_in_period'                          => 'Tämä kenttä näyttää jokaiselle laskulle milloin tämän jakson seuraava eräpäivä on.',

    // show bill
    'bills_show_billInfo'                                     => 'Tämä taulukko näyttää joitain yleisiä tietoja tästä laskusta.',
    'bills_show_billButtons'                                  => 'Tällä painikkeella voit skannata vanhat tapahtumat uudelleen jotta ne linkitetään tähän laskuun.',
    'bills_show_billChart'                                    => 'Tämä kaavio näyttää tähän laskuun liittyvät tapahtumat.',

    // create bill
    'bills_create_intro'                                      => 'Käytä laskuja seurataksesi rahan määrää, jonka joudut maksamaan jokaisella jaksolla. Ajattele kuluja, kuten vuokria, vakuutuksia tai asuntolainan maksuja.',
    'bills_create_name'                                       => 'Käytä kuvaavaa nimeä, kuten "Vuokra" tai "Sairausvakuutus".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Aseta laskun vähimmäis- ja enimmäissumma.',
    'bills_create_repeat_freq_holder'                         => 'Useimmat laskut toistuvat kuukausittain, mutta voit asettaa täällä niille myös erilaisia jaksoja.',
    'bills_create_skip_holder'                                => 'Jos lasku toistuu kahden viikon välein, "ohita"-kenttään tulee asettaa arvoksi "1", jolloin joka toinen viikko ohitetaan.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III antaa sinun hallita sääntöjä, jota sovelletaan automaagisesti kaikkiin luomiisi tai muokkaamiisi tapahtumiin.',
    'rules_index_new_rule_group'                              => 'Voit yhdistää sääntöjä ryhmiin hallinnan helpottamiseksi.',
    'rules_index_new_rule'                                    => 'Luo niin monta sääntöä kuin haluat.',
    'rules_index_prio_buttons'                                => 'Järjestä ne kuten haluat.',
    'rules_index_test_buttons'                                => 'Voit testata sääntöjäsi tai soveltaa niitä olemassa oleviin tapahtumiin.',
    'rules_index_rule-triggers'                               => 'Säännöillä on "ehtoja" ja "toimintoja" joita voit järjestää vetämällä ja pudottamalla.',
    'rules_index_outro'                                       => 'Muista tarkistaa ohjeet oikealla yläkulmassa olevalla (?) -Kuvakkeella!',

    // create rule:
    'rules_create_mandatory'                                  => 'Valitse kuvaava otsikko, ja aseta milloin säännön tulisi liipaistua.',
    'rules_create_ruletriggerholder'                          => 'Lisää niin monta ehtoa kuin haluat, mutta muista että KAIKKIEN ehtojen täytyy täyttyä ennen kuin toiminnot toteutuvat.',
    'rules_create_test_rule_triggers'                         => 'Käytä tätä painiketta nähdäksesi, mitkä tapahtumat vastaisivat sääntöäsi.',
    'rules_create_actions'                                    => 'Aseta niin monta toimintoa kuin haluat.',

    // preferences
    'preferences_index_tabs'                                  => 'Lisää vaihtoehtoja on saatavana näiden välilehtien takana.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III tukee useita valuuttoja, joita voit muuttaa tällä sivulla.',
    'currencies_index_default'                                => 'Firefly III:lla on yksi oletusvaluutta.',
    'currencies_index_buttons'                                => 'Näillä painikkeilla voit muuttaa oletusvaluuttaa tai ottaa käyttöön muita valuuttoja.',

    // create currency
    'currencies_create_code'                                  => 'Tämän koodin tulisi olla ISO-yhteensopiva (Googlaa se uutta valuuttaasi varten).',
];
