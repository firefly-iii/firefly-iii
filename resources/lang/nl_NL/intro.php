<?php
declare(strict_types=1);

/**
 * intro.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

return [
    // index
    'index_intro'                           => 'Welkom op de homepage van Firefly III. Neem even de tijd voor deze introductie zodat je Firefly III leert kennen.',
    'index_accounts-chart'                  => 'Deze grafiek toont het saldo van je betaalrekening(en). Welke rekeningen zichtbaar zijn kan je aangeven bij de instellingen.',
    'index_box_out_holder'                  => 'Dit vakje en de vakjes er naast geven een snel overzicht van je financiële situatie.',
    'index_help'                            => 'Als je ooit hulp nodig hebt, klik dan hier.',
    'index_outro'                           => 'De meeste pagina\'s in Firefly III beginnen met een kleine rondleiding zoals deze. Zoek me op als je vragen of commentaar hebt. Veel plezier!',
    'index_sidebar-toggle'                  => 'Nieuwe transacties, rekeningen en andere dingen maak je met het menu onder deze knop.',

    // create account:
    'accounts_create_iban'                  => 'Geef je rekeningen een geldige IBAN. Dat scheelt met importeren van data.',
    'accounts_create_asset_opening_balance' => 'Betaalrekeningen kunnen een startsaldo hebben, waarmee het begin van deze rekening in Firefly wordt aangegeven.',
    'accounts_create_asset_currency'        => 'Firefly III ondersteunt meerdere valuta. Hier stel je de valuta in van je betaalrekening.',
    'accounts_create_asset_virtual'         => 'Soms is het handig om je betaalrekening een virtueel saldo te geven: een extra bedrag dat altijd bij het daadwerkelijke saldo wordt opgeteld.',

    // budgets index
    'budgets_index_intro'                   => 'Budgetten worden gebruikt om je financiën te beheren en vormen een van de kernfuncties van Firefly III.',
    'budgets_index_set_budget'              => 'Stel je totale budget voor elke periode in, zodat Firefly je kan vertellen of je alle beschikbare geld hebt gebudgetteerd.',
    'budgets_index_see_expenses_bar'        => 'Het besteden van geld zal deze balk langzaam vullen.',
    'budgets_index_navigate_periods'        => 'Navigeer door periodes heen om je budget vooraf te bepalen.',
    'budgets_index_new_budget'              => 'Maak nieuwe budgetten naar wens.',
    'budgets_index_list_of_budgets'         => 'Gebruik deze tabel om de bedragen voor elk budget vast te stellen en te zien hoe je er voor staat.',
    'budgets_index_outro'                   => 'To learn more about budgeting, checkout the help icon in the top right corner.',

    // reports (index)
    'reports_index_intro'                   => 'Gebruik deze rapporten om gedetailleerde inzicht in je financiën te krijgen.',
    'reports_index_inputReportType'         => 'Kies een rapporttype. Bekijk de helppagina\'s om te zien wat elk rapport laat zien.',
    'reports_index_inputAccountsSelect'     => 'Je kunt naar keuze betaalrekeningen meenemen (of niet).',
    'reports_index_inputDateRange'          => 'Kies zelf een datumbereik: van een dag tot tien jaar.',
    'reports_index_extra-options-box'       => 'Sommige rapporten bieden extra filters en opties. Kies een rapporttype en kijk of hier iets verandert.',

    // reports (reports)
    'reports_report_default_intro'          => 'Dit rapport geeft je een snel en uitgebreid overzicht van je financiën. Laat het me weten als je hier dingen mist!',
    'reports_report_audit_intro'            => 'Dit rapport geeft je gedetailleerde inzichten in je betaalrekeningen.',
    'reports_report_audit_optionsBox'       => 'Gebruik deze vinkjes om voor jou interessante kolommen te laten zien of te verbergen.',

    'reports_report_category_intro'                  => 'Dit rapport geeft je inzicht in één of meerdere categorieën.',
    'reports_report_category_pieCharts'              => 'Deze grafieken geven je inzicht in de uitgaven en inkomsten per categorie of per rekening.',
    'reports_report_category_incomeAndExpensesChart' => 'Deze grafiek toont je uitgaven en inkomsten per categorie.',

    'reports_report_tag_intro'                  => 'Dit rapport geeft je inzicht in één of meerdere tags.',
    'reports_report_tag_pieCharts'              => 'Deze grafieken geven je inzicht in de uitgaven en inkomsten per tag, rekening, categorie of budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'Deze grafiek toont je uitgaven en inkomsten per tag.',

    'reports_report_budget_intro'                             => 'Dit rapport geeft je inzicht in één of meerdere budgetten.',
    'reports_report_budget_pieCharts'                         => 'Deze grafieken geven je inzicht in de uitgaven en inkomsten per budget of per rekening.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Deze grafiek toont je uitgaven per budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Gebruik deze knoppen om snel van transactietype te wisselen.',
    'transactions_create_ffInput_category'                    => 'Je kan in dit veld vrij typen. Eerder gemaakte categorieën komen als suggestie naar boven.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Link je uitgave aan een budget voor een beter financieel overzicht.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Gebruik deze dropdown als je uitgave in een andere valuta is.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Gebruik deze dropdown als je inkomsten in een andere valuta zijn.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Selecteer een spaarpotje en link deze overschrijving aan je spaargeld.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Dit veld laat zien hoeveel geld er in elk spaarpotje zit.',
    'piggy-banks_index_button'                                => 'Naast deze balk zitten twee knoppen (+ en -) om geld aan je spaarpotje toe te voegen, of er uit te halen.',
    'piggy-banks_index_accountStatus'                         => 'Voor elke betaalrekening met minstens één spaarpotje zie je hier de status.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Wat is je doel? Een nieuwe zithoek, een camera of geld voor noodgevallen?',
    'piggy-banks_create_date'                                 => 'Je kan een doeldatum of een deadline voor je spaarpot instellen.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Deze grafiek toont de geschiedenis van dit spaarpotje.',
    'piggy-banks_show_piggyDetails'                           => 'Enkele details over je spaarpotje',
    'piggy-banks_show_piggyEvents'                            => 'Eventuele stortingen (van en naar) worden hier ook vermeld.',

    // bill index
    'bills_index_paid_in_period'                              => 'Dit veld geeft aan wanneer het contract het laatst is betaald.',
    'bills_index_expected_in_period'                          => 'Dit veld geeft aan voor elk contract of en wanneer je hem weer moet betalen.',

    // show bill
    'bills_show_billInfo'                                     => 'Deze tabel bevat wat algemene informatie over dit contract.',
    'bills_show_billButtons'                                  => 'Gebruik deze knop om oude transacties opnieuw te scannen, zodat ze aan dit contract worden gekoppeld.',
    'bills_show_billChart'                                    => 'Deze grafiek toont de transacties gekoppeld aan dit contract.',

    // create bill
    'bills_create_name'                                       => 'Gebruik een beschrijvende naam zoals "huur" of "zorgverzekering".',
    'bills_create_match'                                      => 'Om transacties te koppelen gebruik je termen uit de transacties of de bijbehorende crediteur. Alle termen moeten overeen komen.',
    'bills_create_amount_min_holder'                          => 'Stel ook een minimum- en maximumbedrag in.',
    'bills_create_repeat_freq_holder'                         => 'De meeste contracten herhalen maandelijks, maar dat kan je eventueel veranderen.',
    'bills_create_skip_holder'                                => 'Als een contract elke twee weken herhaalt, zet je het "skip"-veld op 1 om elke andere week over te slaan.',

    // rules index
    'rules_index_intro'                                       => 'In Firefly III kan je regels maken die automagisch op transacties worden toegepast.',
    'rules_index_new_rule_group'                              => 'Je kan regels combineren in groepen voor makkelijker beheer.',
    'rules_index_new_rule'                                    => 'Maak zoveel regels als je wilt.',
    'rules_index_prio_buttons'                                => 'Zet ze in elke willekeurige volgorde.',
    'rules_index_test_buttons'                                => 'Je kan je regels testen of toepassen op bestaande transacties.',
    'rules_index_rule-triggers'                               => 'Regels hebben "triggers" en "acties" die je kan sorteren met drag-en-drop.',
    'rules_index_outro'                                       => 'Check ook de helppagina\'s met het (?)-icoontje rechtsboven!',

    // create rule:
    'rules_create_mandatory'                                  => 'Kies een beschrijvende titel en wanneer de regel af moet gaan.',
    'rules_create_ruletriggerholder'                          => 'Voeg zoveel triggers toe als je wilt, maar denk er aan dat ALLE triggers moeten matchen voor de acties worden uitgevoerd.',
    'rules_create_test_rule_triggers'                         => 'Gebruik deze knop om te zien welke bestaande transacties overeen zouden komen.',
    'rules_create_actions'                                    => 'Stel zoveel acties in als je wilt.',

    // preferences
    'preferences_index_tabs'                                  => 'Meer opties zijn beschikbaar achter deze tabbladen.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III ondersteunt meerdere valuta, die je op deze pagina kunt wijzigen.',
    'currencies_index_default'                                => 'Firefly III heeft één standaardvaluta. Je kan natuurlijk altijd wisselen met deze knoppen.',

    // create currency
    'currencies_create_code'                                  => 'Deze code moet ISO-compatibel zijn (Google die code voor je nieuwe valuta).',
];
