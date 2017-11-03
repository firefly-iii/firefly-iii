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
    'index_intro'                           => 'Wilkommen auf der Startseite von Firefly III. Bitte nehmen Sie sich die Zeit, um ein Gefühl dafür zu bekommen, wie Firefly III funktioniert.',
    'index_accounts-chart'                  => 'Dieses Diagramm zeigt den aktuellen Saldo Ihrer Anlagenkonten. Sie können die anzuzeigenden Konten in Ihren Einstellungen auswählen.',
    'index_box_out_holder'                  => 'Diese kleine und deren benachbarten Boxen geben Ihnen einen schnellen Überblick über Ihre finanzielle Situation.',
    'index_help'                            => 'Wenn Sie jemals Hilfe bei einer Seite oder einem Formular benötigen, drücken Sie diese Taste.',
    'index_outro'                           => 'Die meisten Seiten von Firefly III werden mit einer kleinen Tour wie dieser beginnen. Bitte kontaktieren Sie mich, wenn Sie Fragen oder Kommentare haben. Viel Spaß!',
    'index_sidebar-toggle'                  => 'Um neue Transaktionen, Konten oder andere Dinge zu erstellen, verwenden Sie das Menü unter diesem Symbol.',

    // create account:
    'accounts_create_iban'                  => 'Geben Sie Ihren Konten eine gültige IBAN. Dies könnte einen Datenimport in Zukunft sehr einfach machen.',
    'accounts_create_asset_opening_balance' => 'Anlagekonten können eine "Eröffnungsbilanz" haben, welche den Beginn der Historie dieses Kontos in Firefly angibt.',
    'accounts_create_asset_currency'        => 'Firefly III unterstützt mehrere Währungen. Anlagenkonten haben eine Hauptwährung, die Sie hier einstellen müssen.',
    'accounts_create_asset_virtual'         => 'Es kann manchmal helfen, Ihrem Konto ein virtuelles Gleichgewicht zu geben: eine zusätzliche Menge, die dem tatsächlichen Kontostand immer hinzugefügt oder daraus entfernt wird.',

    // budgets index
    'budgets_index_intro'                   => 'Mit Budgets verwalten Sie Ihre Finanzen und bilden damit eine der Kernfunktionen von Firefly III.',
    'budgets_index_set_budget'              => 'Stellen Sie Ihr Gesamtbudget für jeden Zeitraum ein, damit Firefly Ihnen mitteilen kann, ob Sie das verfügbare Geld budgetiert haben.',
    'budgets_index_see_expenses_bar'        => 'Dieser Balken wird sich langsam füllen, wenn Sie Geld ausgeben.',
    'budgets_index_navigate_periods'        => 'Navigieren Sie durch Zeiträume, um die Budgets im Voraus zu setzen.',
    'budgets_index_new_budget'              => 'Erstellen Sie neue Budgets, wie Sie es für richtig halten.',
    'budgets_index_list_of_budgets'         => 'Verwenden Sie diese Tabelle, um die Beträge für jedes Budget festzulegen und einen Überblick zu erhalten.',
    'budgets_index_outro'                   => 'Um mehr über die Budgetierung zu erfahren, klicken Sie auf das Hilfesymbol in der oberen rechten Ecke.',

    // reports (index)
    'reports_index_intro'                   => 'Verwenden Sie diese Reports, um detaillierte Einblicke in Ihre Finanzen zu erhalten.',
    'reports_index_inputReportType'         => 'Wählen Sie einen Berichtstyp aus. Sehen Sie sich die Hilfeseiten an, um zu sehen, was jeder Bericht Ihnen zeigt.',
    'reports_index_inputAccountsSelect'     => 'Sie können Anlagekonten ausschließen oder einbeziehen, wie Sie es für richtig halten.',
    'reports_index_inputDateRange'          => 'Der gewählte Datumsbereich liegt ganz bei Ihnen: von einem Tag bis 10 Jahre.',
    'reports_index_extra-options-box'       => 'Abhängig von dem ausgewählten Bericht können Sie hier zusätzliche Filter und Optionen auswählen. Sehen Sie sich dieses Feld an, wenn Sie Berichtstypen ändern.',

    // reports (reports)
    'reports_report_default_intro'          => 'Dieser Bericht gibt Ihnen einen schnellen und umfassenden Überblick über Ihre Finanzen. Wenn Sie etwas anderes sehen möchten, kontaktieren Sie mich bitte nicht!',
    'reports_report_audit_intro'            => 'In diesem Bericht erhalten Sie detaillierte Einblicke in Ihre Bestandskonten.',
    'reports_report_audit_optionsBox'       => 'Verwenden Sie diese Kontrollkästchen, um die Spalten anzuzeigen oder auszublenden, an denen Sie interessiert sind.',

    'reports_report_category_intro'                  => 'Dieser Bericht gibt Ihnen Einblick in eine oder mehrere Kategorien.',
    'reports_report_category_pieCharts'              => 'Diese Diagramme geben Ihnen Einblick in Ausgaben und Einnahmen pro Kategorie oder pro Konto.',
    'reports_report_category_incomeAndExpensesChart' => 'Diese Tabelle zeigt Ihre Ausgaben und Einnahmen pro Kategorie.',

    'reports_report_tag_intro'                  => 'Dieser Bericht gibt Ihnen Einblick in eine oder mehrere Tags.',
    'reports_report_tag_pieCharts'              => 'Diese Diagramme geben Ihnen Einblick in Ausgaben und Einnahmen pro Tag, Konto, Kategorie oder budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'Diese Tabelle zeigt Ihre Ausgaben und Einnahmen pro Tag.',

    'reports_report_budget_intro'                             => 'Dieser Bericht gibt Ihnen Einblick in eine oder mehrere Budgets.',
    'reports_report_budget_pieCharts'                         => 'Diese Diagramme geben Ihnen Einblick in Ausgaben und Einnahmen pro Budget oder pro Konto.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Diese Tabelle zeigt Ihre Ausgaben und Einnahmen pro Budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Verwenden Sie diese Schaltflächen, um schnell den Typ der Transaktion zu ändern, die Sie speichern möchten.',
    'transactions_create_ffInput_category'                    => 'Dies ist ein Freitextfeld. Zuvor erstellte Kategorien werden vorgeschlagen.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Verbinden Sie Ihre Abhebung mit einem Budget für eine bessere Kontrolle Ihrer Finanzen.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Verwenden Sie dieses Dropdown, wenn ihre Abbuchung in einer anderen Währung ist.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Verwenden Sie dieses Dropdown, wenn ihre Einzahlung in einer anderen Währung ist.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Wählen Sie ein Sparschwein aus und verbinden Sie diese Überweisung mit Ihren Ersparnissen.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Dieses Feld zeigt Ihnen, wie viel Sie in jedem Sparschwein gespart haben.',
    'piggy-banks_index_button'                                => 'Neben diesem Fortschrittsbalken befinden sich zwei Buttons (+ und -), um Geld von jedem Sparschwein hinzuzufügen oder zu entfernen.',
    'piggy-banks_index_accountStatus'                         => 'In dieser Tabelle wird der Status der Anlagenkonten aufgeführt, die mit mindestens einem Sparschwein verbunden sind.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Worauf sparen Sie? Eine neue Couch, eine Kamera, Geld für Notfälle?',
    'piggy-banks_create_date'                                 => 'Sie können ein Zieldatum oder einen Termin für Ihr Sparschwein festlegen.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Dieses Diagramm zeigt den Verlauf dieses Sparschweins.',
    'piggy-banks_show_piggyDetails'                           => 'Einige Details über Ihr Sparschwein',
    'piggy-banks_show_piggyEvents'                            => 'Hinzufügungen oder Entfernungen sind hier ebenfalls aufgeführt.',

    // bill index
    'bills_index_paid_in_period'                              => 'Dieses Feld zeigt an, wann die Rechnung zuletzt bezahlt wurde.',
    'bills_index_expected_in_period'                          => 'Dieses Feld zeigt für jede Rechnung an, ob und wann die nächste Rechnung erwartet wird.',

    // show bill
    'bills_show_billInfo'                                     => 'Diese Tabelle enthält allgemeine Informationen über diese Rechnung.',
    'bills_show_billButtons'                                  => 'Verwenden Sie diese Schaltfläche, um alte Transaktionen erneut zu scannen, sodass sie mit dieser Rechnung verglichen werden.',
    'bills_show_billChart'                                    => 'Diese Grafik zeigt die mit dieser Rechnung verknüpften Transaktionen.',

    // create bill
    'bills_create_name'                                       => 'Verwenden Sie einen aussagekräftigen Namen wie "Miete" oder "Krankenversicherung".',
    'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Wählen Sie einen Mindest- und Höchstbetrag für diese Rechnung aus.',
    'bills_create_repeat_freq_holder'                         => 'Die meisten Rechnungen wiederholen sich monatlich, aber Sie können hier eine andere Frequenz einstellen.',
    'bills_create_skip_holder'                                => 'If a bill repeats every 2 weeks for example, the "skip"-field should be set to "1" to skip every other week.',

    // rules index
    'rules_index_intro'                                       => 'Mit Firefly III können Sie Regeln verwalten, die automatisch auf alle Transaktionen angewendet werden, die Sie erstellen oder bearbeiten.',
    'rules_index_new_rule_group'                              => 'Sie können Regeln in Gruppen kombinieren, um die Verwaltung zu vereinfachen.',
    'rules_index_new_rule'                                    => 'Erstellen Sie so viele Regeln, wie Sie möchten.',
    'rules_index_prio_buttons'                                => 'Sortieren Sie sie, wie Sie es für richtig halten.',
    'rules_index_test_buttons'                                => 'Sie können Ihre Regeln testen oder sie auf vorhandene Transaktionen anwenden.',
    'rules_index_rule-triggers'                               => 'Regeln haben "Auslöser" und "Aktionen", die Sie per Drag-and-Drop sortieren können.',
    'rules_index_outro'                                       => 'Be sure to check out the help pages using the (?) icon in the top right!',

    // create rule:
    'rules_create_mandatory'                                  => 'Wählen Sie einen aussagekräftigen Titel und legen Sie fest, wann die Regel ausgelöst werden soll.',
    'rules_create_ruletriggerholder'                          => 'Add as many triggers as you like, but remember that ALL triggers must match before any actions are fired.',
    'rules_create_test_rule_triggers'                         => 'Use this button to see which transactions would match your rule.',
    'rules_create_actions'                                    => 'Legen Sie so viele Aktionen fest, wie Sie möchten.',

    // preferences
    'preferences_index_tabs'                                  => 'Weitere Optionen sind hinter diesen Registerkarten verfügbar.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III unterstützt mehrere Währungen, die Sie auf dieser Seite ändern können.',
    'currencies_index_default'                                => 'Firefly III besitzt eine Standardwährung, die mit diesen Buttons gewechselt werden können.',

    // create currency
    'currencies_create_code'                                  => 'Dieser Code sollte ISO-konform sein (Für eine neue Währung googlen).',
];
