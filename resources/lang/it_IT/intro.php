<?php
declare(strict_types=1);
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

return [
    // index
    'index_intro'                           => 'Benvenuti nella pagina indice di Firefly III. Si prega di prendersi il tempo necessario per questa introduzione per avere un\'idea di come funziona Firefly III.',
    'index_accounts-chart'                  => 'Questo grafico mostra il saldo attuale dei contit risorse. Puoi selezionare gli conti visibili qui nelle tue preferenze.',
    'index_box_out_holder'                  => 'Questa piccola casella e le caselle accanto a questa ti daranno una rapida panoramica della tua situazione finanziaria.',
    'index_help'                            => 'Se hai bisogno di aiuto per una pagina o un modulo, premi questo pulsante.',
    'index_outro'                           => 'La maggior parte delle pagine di Firefly III inizieranno con un piccolo tour come questo. Vi prego di contattarci quando avete domande o commenti. Grazie!',
    'index_sidebar-toggle'                  => 'Per creare nuove transazioni, conto o altre cose, usa il menu sotto questa icona.',

    // create account:
    'accounts_create_iban'                  => 'Dai ai tuoi conti un IBAN valido. Ciò potrebbe rendere molto facile l\'importazione dei dati in futuro.',
    'accounts_create_asset_opening_balance' => 'I conti attività possono avere un "saldo di apertura", che indica l\'inizio della cronologia di questo conto in Firefly III.',
    'accounts_create_asset_currency'        => 'Firefly III supporta più valute. I conti attività hanno una valuta principale, che devi impostare qui.',
    'accounts_create_asset_virtual'         => 'A volte può aiutare a fornire al tuo conto un saldo virtuale: un importo aggiuntivo sempre aggiunto o rimosso dal saldo effettivo.',

    // budgets index
    'budgets_index_intro'                   => 'I bilanci sono usati per gestire le tue finanze e formano una delle funzioni principali di Firefly III.',
    'budgets_index_set_budget'              => 'Imposta il tuo bilancio totale per ogni periodo in modo che Firefly III possa dirti se hai messo a bilancio tutti i soldi disponibili.',
    'budgets_index_see_expenses_bar'        => 'Le spese effettuate riempiranno lentamente questa barra.',
    'budgets_index_navigate_periods'        => 'Naviga attraverso i periodi per impostare facilmente i bilanci in anticipo.',
    'budgets_index_new_budget'              => 'Crea nuovi bilanci come meglio credi.',
    'budgets_index_list_of_budgets'         => 'Usa questa tabella per impostare gli importi per ciascun bilancio e vedere l\'andamento.',
    'budgets_index_outro'                   => 'Per saperne di più sul bilancio, controlla l\'icona della guida nell\'angolo in alto a destra.',

    // reports (index)
    'reports_index_intro'                   => 'Utilizza queste stampe per ottenere informazioni dettagliate sulle tue finanze.',
    'reports_index_inputReportType'         => 'Scegli un tipo di stampa. Consulta le pagine della guida per vedere cosa ti mostra ciascuna stampa.',
    'reports_index_inputAccountsSelect'     => 'Puoi escludere o includere i conti attività come ritieni opportuno.',
    'reports_index_inputDateRange'          => 'L\'intervallo di date selezionato dipende interamente da te: da un giorno a 10 anni.',
    'reports_index_extra-options-box'       => 'A seconda della stampao che hai selezionato, puoi selezionare filtri e opzioni aggiuntive qui. Guarda questa casella quando cambi i tipi di stampa.',

    // reports (reports)
    'reports_report_default_intro'          => 'Questa stampa ti fornirà una panoramica rapida e completa delle tue finanze. Se desideri vedere qualcos\'altro, per favore non esitare a contattarmi!',
    'reports_report_audit_intro'            => 'Questa stampa ti fornirà approfondimenti dettagliati sui tuoi conti attività.',
    'reports_report_audit_optionsBox'       => 'Utilizza queste caselle di controllo per mostrare o nascondere le colonne che ti interessano.',

    'reports_report_category_intro'                  => 'Questa stampa ti fornirà informazioni su una o più categorie.',
    'reports_report_category_pieCharts'              => 'Questi grafici ti daranno un\'idea delle spese e del reddito per categoria o per conto.',
    'reports_report_category_incomeAndExpensesChart' => 'Questo grafico mostra le tue spese e il reddito per categoria.',

    'reports_report_tag_intro'                  => 'Questa stampa ti fornirà informazioni su uno o più etichette.',
    'reports_report_tag_pieCharts'              => 'Questi grafici ti daranno un\'idea delle spese e del reddito per etichetta, conto, categoria o bilancio.',
    'reports_report_tag_incomeAndExpensesChart' => 'Questo grafico mostra le tue spese e entrate per etichetta.',

    'reports_report_budget_intro'                             => 'Questa stampa ti fornirà informazioni su uno o più bilancio(i).',
    'reports_report_budget_pieCharts'                         => 'Questi grafici ti daranno un\'idea delle spese per bilancio o per conto.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Questo grafico mostra le tue spese per bilancio.',

    // create transaction
    'transactions_create_switch_box'                          => 'Usa questi pulsanti per cambiare rapidamente il tipo di transazione che desideri salvare.',
    'transactions_create_ffInput_category'                    => 'Puoi scrivere liberamente in questo campo. Saranno suggerite categorie precedentemente create.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Collega il tuo prelievo a un bilancio per un migliore controllo finanziario.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Usa questo menu a discesa quando il prelievo è in un\'altra valuta.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Usa questo menu a discesa quando il tuo deposito è in un\'altra valuta.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Seleziona un salvadanaio e collega questo trasferimento ai tuoi risparmi.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Questo campo ti mostra quanto hai salvato in ogni salvadanaio.',
    'piggy-banks_index_button'                                => 'Accanto a questa barra di avanzamento ci sono due pulsanti (+ e -) per aggiungere o rimuovere denaro da ogni salvadanaio.',
    'piggy-banks_index_accountStatus'                         => 'Per ogni conto attività con almeno un salvadanaio lo stato è elencato in questa tabella.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Qual è il tuo obiettivo? Un nuovo divano, una macchina fotografica, soldi per le emergenze?',
    'piggy-banks_create_date'                                 => 'È possibile impostare una data di destinazione o una scadenza per il salvadanaio.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Questo grafico mostrerà lo storico di questo salvadanaio.',
    'piggy-banks_show_piggyDetails'                           => 'Alcuni dettagli sul tuo salvadanaio',
    'piggy-banks_show_piggyEvents'                            => 'Anche eventuali aggiunte o rimozioni sono elencate qui.',

    // bill index
    'bills_index_paid_in_period'                              => 'Questo campo indica quando il conto è stato pagato l\'ultima volta.',
    'bills_index_expected_in_period'                          => 'Questo campo indica per ciascuna fattura se e quando ci si aspetta che la fattura successiva arrivi.',

    // show bill
    'bills_show_billInfo'                                     => 'Questa tabella mostra alcune informazioni generali su questa fattura.',
    'bills_show_billButtons'                                  => 'Utilizzare questo pulsante per rieseguire la scansione delle vecchie transazioni in modo che corrispondano a questa fattura.',
    'bills_show_billChart'                                    => 'Questo grafico mostra le transazioni collegate a questa fattura.',

    // create bill
    'bills_create_name'                                       => 'Utilizzare un nome descrittivo come "Affitto" o "Assicurazione sanitaria".',
    'bills_create_match'                                      => 'Per abbinare le transazioni, utilizzare i termini di tali transazioni o il conto spese coinvolto. Tutte le parole devono corrispondere',
    'bills_create_amount_min_holder'                          => 'Seleziona un importo minimo e massimo per questo conto.',
    'bills_create_repeat_freq_holder'                         => 'La maggior parte dei pagamenti si ripetono mensilmente, ma qui puoi impostare un\'altra frequenza.',
    'bills_create_skip_holder'                                => 'Ad esempio, se un conto si ripete ogni 2 settimane, il campo "Salta" dovrebbe essere impostato su "1" per saltare ogni altra settimana.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III ti consente di gestire le regole, che verranno automaticamente applicate a qualsiasi transazione creata o modificata.',
    'rules_index_new_rule_group'                              => 'È possibile combinare le regole in gruppi per una gestione più semplice.',
    'rules_index_new_rule'                                    => 'Crea quante regole desideri.',
    'rules_index_prio_buttons'                                => 'Ordinali come meglio credi.',
    'rules_index_test_buttons'                                => 'Puoi testare le tue regole o applicarle a transazioni esistenti.',
    'rules_index_rule-triggers'                               => 'Le regole hanno "trigger" e "azioni" che puoi ordinare trascinandole.',
    'rules_index_outro'                                       => 'Assicurati di controllare le pagine della guida usando l\'icona (?) In alto a destra!',

    // create rule:
    'rules_create_mandatory'                                  => 'Scegli un titolo descrittivo e imposta quando deve essere attivata la regola.',
    'rules_create_ruletriggerholder'                          => 'Aggiungi tutti i trigger che desideri, ma ricorda che TUTTI i trigger devono corrispondere prima che vengano attivate azioni.',
    'rules_create_test_rule_triggers'                         => 'Usa questo pulsante per vedere quali transazioni corrispondono alla tua regola.',
    'rules_create_actions'                                    => 'Imposta tutte le azioni che vuoi.',

    // preferences
    'preferences_index_tabs'                                  => 'Altre opzioni sono disponibili dietro queste schede.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III supporta più valute, che è possibile modificare su questa pagina.',
    'currencies_index_default'                                => 'Firefly III ha una valuta predefinita. Puoi sempre cambiare valuta usando questi pulsanti.',

    // create currency
    'currencies_create_code'                                  => 'Questo codice dovrebbe essere conforme ISO (Google per la tua nuova valuta).',
];
