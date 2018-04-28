<?php
declare(strict_types=1);

/**
 * import.php
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
    // status of import:
    'status_wait_title'                    => 'Per favore attendere...',
    'status_wait_text'                     => 'Questa finestra si chiuderà tra un momento.',
    'status_fatal_title'                   => 'Si è verificato un errore irreversibile',
    'status_fatal_text'                    => 'Si è verificato un errore irreversibile per cui non è stato possibile ripristinare la routine di importazione. Si prega di vedere la spiegazione in rosso qui sotto.',
    'status_fatal_more'                    => 'Se l\'errore è un timeout, l\'importazione si sarà interrotta a metà. Per alcune configurazioni del server, o semplicemente il server che si è fermato mentre l\'importazione continua a funzionare in sottofondo. Per verificare questo, controlla il file di registro. Se il problema persiste, prendere in considerazione l\'importazione sulla riga di comando.',
    'status_ready_title'                   => 'L\'importazione è pronta per iniziare',
    'status_ready_text'                    => 'L\'importazione è pronta per iniziare. È stata eseguita tutta la configurazione necessaria. Si prega di scaricare il file di configurazione. Ti aiuterà con l\'importazione se non dovesse andare come previsto. Per eseguire effettivamente l\'importazione, è possibile eseguire il seguente comando nella console o eseguire l\'importazione basata sul Web. A seconda della configurazione, l\'importazione della console ti darà più feedback.',
    'status_ready_noconfig_text'           => 'L\'importazione è pronta per iniziare. È stata eseguita tutta la configurazione necessaria. Per eseguire effettivamente l\'importazione, è possibile eseguire il seguente comando nella console o eseguire l\'importazione basata sul Web. A seconda della configurazione, l\'importazione della console ti darà più feedback.',
    'status_ready_config'                  => 'Scarica configurazione',
    'status_ready_start'                   => 'Inizia l\'importazione',
    'status_ready_share'                   => 'Ti preghiamo di considerare di scaricare la tua configurazione e di condividerla nel <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">centro di configurazione dell\'importazione</a></strong>. Ciò consentirà ad altri utenti di Firefly III di importare i propri file più facilmente.',
    'status_job_new'                       => 'Il lavoro è nuovo di zecca.',
    'status_job_configuring'               => 'L\'importazione è in fase di configurazione.',
    'status_job_configured'                => 'L\'importazione è configurata.',
    'status_job_running'                   => 'L\'importazione è in esecuzione... Attendere...',
    'status_job_error'                     => 'Il lavoro ha generato un errore.',
    'status_job_finished'                  => 'L\'importazione è finita!',
    'status_running_title'                 => 'L\'importazione è in esecuzione',
    'status_running_placeholder'           => 'Si prega di effettuare un aggiornamento...',
    'status_finished_title'                => 'Routine importa terminata',
    'status_finished_text'                 => 'La routine di importazione ha importato i tuoi dati.',
    'status_errors_title'                  => 'Errori durante l\'importazione',
    'status_errors_single'                 => 'Si è verificato un errore durante l\'importazione. Non sembra essere fatale.',
    'status_errors_multi'                  => 'Alcuni errori si sono verificati durante l\'importazione. Questi non sembrano essere fatali.',
    'status_bread_crumb'                   => 'Stato importazione',
    'status_sub_title'                     => 'Stato importazione',
    'config_sub_title'                     => 'Configura la tua importazione',
    'status_finished_job'                  => 'Le transazioni :count importate possono essere trovate nel tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> .',
    'status_finished_no_tag'               => 'Firefly III non ha raccolto alcuna transazione dal tuo file di importazione.',
    'import_with_key'                      => 'Importa con chiave \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Importa configurazione (1/4) - Carica il tuo file',
    'file_upload_text'                     => 'Questa routine ti aiuterà a importare file dalla tua banca in Firefly III. Si prega di consultare le pagine di aiuto nell\'angolo in alto a destra.',
    'file_upload_fields'                   => 'Campi',
    'file_upload_help'                     => 'Seleziona il tuo file',
    'file_upload_config_help'              => 'Se hai precedentemente importato i dati in Firefly III, potresti avere un file di configurazione, con preimpostato i valori di configurazione per te. Per alcune banche, altri utenti hanno gentilmente fornito il loro <a href="https://github.com/firefly-iii/import-configurations/wiki">configurazione file</a> ',
    'file_upload_type_help'                => 'Seleziona il tipo di file che carichi',
    'file_upload_submit'                   => 'File caricati',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (valori separati da virgola)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Importa configurazione (2/4) - Impostazione di importazione CSV di base',
    'csv_initial_text'                     => 'Per poter importare correttamente il tuo file, ti preghiamo di convalidare le opzioni di seguito.',
    'csv_initial_box'                      => 'Configurazione di importazione CSV di base',
    'csv_initial_box_title'                => 'Opzioni di impostazione dell\'importazione CSV di base',
    'csv_initial_header_help'              => 'Seleziona questa casella se la prima riga del tuo file CSV sono i titoli delle colonne.',
    'csv_initial_date_help'                => 'Formato della data e ora nel tuo CSV. Segui il formato <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">indica questa pagina</a>. Il valore predefinito analizzerà le date che assomigliano a questo: :dateExample.',
    'csv_initial_delimiter_help'           => 'Scegli il delimitatore di campo che viene utilizzato nel file di input. Se non si è sicuri, la virgola è l\'opzione più sicura.',
    'csv_initial_import_account_help'      => 'Se il tuo file CSV NON contiene informazioni sui tuoi conti di attività, utilizza questo menu a discesa per selezionare a quale conto appartengono le transazioni nel CSV.',
    'csv_initial_submit'                   => 'Continua con il passo 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Applica regole',
    'file_apply_rules_description'         => 'Applica le tue regole. Si noti che questo rallenta l\'importazione in modo significativo.',
    'file_match_bills_title'               => 'Abbina le bollette',
    'file_match_bills_description'         => 'Abbina le tue bollette ai prelievi di nuova creazione. Si noti che questo rallenta l\'importazione in modo significativo.',

    // file, roles config
    'csv_roles_title'                      => 'Importa configurazione (3/4) - Definisci il ruolo di ogni colonna',
    'csv_roles_text'                       => 'Ogni colonna nel tuo file CSV contiene determinati dati. Si prega di indicare il tipo di dati che l\'importatore dovrebbe aspettarsi. L\'opzione per "mappare" i dati significa che collegherete ogni voce trovata nella colonna con un valore nel vostro database. Una colonna spesso mappata è la colonna che contiene l\'IBAN del conto. Questo può essere facilmente abbinato all\'IBAN già presente nel tuo database.',
    'csv_roles_table'                      => 'Tabella',
    'csv_roles_column_name'                => 'Nome colonna',
    'csv_roles_column_example'             => 'Colonna dati esempio',
    'csv_roles_column_role'                => 'Significato dei dati di colonna',
    'csv_roles_do_map_value'               => 'Mappa questi valori',
    'csv_roles_column'                     => 'Colonna',
    'csv_roles_no_example_data'            => 'Nessun dato esempio disponibile',
    'csv_roles_submit'                     => 'Continua con il punto 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'Per lo meno, contrassegnare una colonna come colonna importo. Si consiglia di selezionare anche una colonna per la descrizione, la data e il conto avversario.',
    'foreign_amount_warning'               => 'Se contrassegni una colonna come contenente un importo in una valuta straniera, devi anche impostare la colonna che contiene la valuta in cui si trova.',

    // file, map data
    'file_map_title'                       => 'Importa configurazione (4/4) - Collega i dati di importazione ai dati di Firefly III',
    'file_map_text'                        => 'Nelle seguenti tabelle, il valore a sinistra mostra le informazioni trovate nel file caricato. È tuo compito mappare questo valore, se possibile, su un valore già presente nel tuo database. Firefly si atterrà a questa mappatura. Se non ci sono valori da mappare o non si desidera mappare il valore specifico, selezionare nulla.',
    'file_map_field_value'                 => 'Valore campo',
    'file_map_field_mapped_to'             => 'Mappato a',
    'map_do_not_map'                       => '(non mappare)',
    'file_map_submit'                      => 'Inizia l\'importazione',
    'file_nothing_to_map'                  => 'Non ci sono dati presenti nel tuo file che puoi mappare a valori esistenti. Si prega di premere "Inizia l\'importazione" per continuare.',

    // map things.
    'column__ignore'                       => '(ignora questa colonna)',
    'column_account-iban'                  => 'Conto patrimonio (IBAN)',
    'column_account-id'                    => 'Conto patrimonio ID (matching FF3)',
    'column_account-name'                  => 'Conto patrimonio (nome)',
    'column_amount'                        => 'Importo',
    'column_amount_foreign'                => 'Importo (in altra valuta)',
    'column_amount_debit'                  => 'Importo (colonna debito)',
    'column_amount_credit'                 => 'Importo (colonna credito)',
    'column_amount-comma-separated'        => 'Importo (virgola come separatore decimale)',
    'column_bill-id'                       => 'ID conto (matching FF3)',
    'column_bill-name'                     => 'Nome conto',
    'column_budget-id'                     => 'ID budget (matching FF3)',
    'column_budget-name'                   => 'Nome budget',
    'column_category-id'                   => 'ID Categoria (matching FF3)',
    'column_category-name'                 => 'Nome categoria',
    'column_currency-code'                 => 'Codice valuta (ISO 4217)',
    'column_foreign-currency-code'         => 'Codice valuta straniera (ISO 4217)',
    'column_currency-id'                   => 'ID valuta (matching FF3)',
    'column_currency-name'                 => 'Nome valuta (matching FF3)',
    'column_currency-symbol'               => 'Simbolo valuta (matching FF3)',
    'column_date-interest'                 => 'Data calcolo interessi',
    'column_date-book'                     => 'Data prenotazione della transazione',
    'column_date-process'                  => 'Data processo della transazione',
    'column_date-transaction'              => 'Data',
    'column_date-due'                      => 'Data di scadenza della transazione',
    'column_date-payment'                  => 'Data di pagamento della transazione',
    'column_date-invoice'                  => 'Data di fatturazione della transazione',
    'column_description'                   => 'Descrizione',
    'column_opposing-iban'                 => 'Conto opposto (IBAN)',
    'column_opposing-bic'                  => 'Conto della controparte (BIC)',
    'column_opposing-id'                   => 'ID Conto opposto (matching FF3)',
    'column_external-id'                   => 'ID esterno',
    'column_opposing-name'                 => 'Conto opposto (nome)',
    'column_rabo-debit-credit'             => 'Indicatore Rabo di addebito / accredito specifico della banca',
    'column_ing-debit-credit'              => 'Indicatore di debito / credito specifico ING',
    'column_sepa-ct-id'                    => 'ID end-to-end del bonifico SEPA',
    'column_sepa-ct-op'                    => 'Conto opposto bonifico SEPA',
    'column_sepa-db'                       => 'Addebito diretto SEPA',
    'column_sepa-cc'                       => 'SEPA Clearing Code',
    'column_sepa-ci'                       => 'SEPA Creditor Identifier',
    'column_sepa-ep'                       => 'SEPA External Purpose',
    'column_sepa-country'                  => 'SEPA Country Code',
    'column_tags-comma'                    => 'Etichette (separate da virgola)',
    'column_tags-space'                    => 'Etichette (separate con spazio)',
    'column_account-number'                => 'Conto patrimonio (numero conto)',
    'column_opposing-number'               => 'Conto opposto (numero conto)',
    'column_note'                          => 'Nota(e)',
    'column_internal-reference'            => 'Riferimento interno',

    // prerequisites
    'prerequisites'                        => 'Prerequisiti',

    // bunq
    'bunq_prerequisites_title'             => 'Prerequisiti per un\'importazione da bunq',
    'bunq_prerequisites_text'              => 'Per importare da bunq, è necessario ottenere una chiave API. Puoi farlo attraverso l\'applicazione.',
    'bunq_prerequisites_text_ip'           => 'Bunq richiede il tuo indirizzo IP esterno. Firefly III ha provato a riempire questo campo utilizzando <a href="https://www.ipify.org/">il servizio ipify</a>. Assicurati che questo indirizzo IP sia corretto altrimenti l\'importazione fallirà.',
    'bunq_do_import'                       => 'Sì, importa da questo conto',
    'bunq_accounts_title'                  => 'Conti Bunq',
    'bunq_accounts_text'                   => 'Questi sono i conti associati al tuo conto Bunq. Seleziona i conti dai quali vuoi effettuare l\'importazione e in quale conto devono essere importate le transazioni.',

    // Spectre
    'spectre_title'                        => 'Importa usando uno Spectre',
    'spectre_prerequisites_title'          => 'Prerequisiti per un\'importazione utilizzando Spectre',
    'spectre_prerequisites_text'           => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'                => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_accounts_title'               => 'Seleziona i conti dai quali importare',
    'spectre_accounts_text'                => 'Ogni account sulla sinistra in basso è stato trovato da Spectre e può essere importato in Firefly III. Seleziona il conto attività che dovrebbe contenere una determinata transazione. Se non desideri importare da un conto specifico, rimuovi il segno di spunta dalla casella di controllo.',
    'spectre_do_import'                    => 'Si, importa da questo conto',
    'spectre_no_supported_accounts'        => 'You cannot import from this account due to a currency mismatch.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Stato',
    'spectre_extra_key_card_type'          => 'Tipo carta',
    'spectre_extra_key_account_name'       => 'Nome conto',
    'spectre_extra_key_client_name'        => 'Nome cliente',
    'spectre_extra_key_account_number'     => 'Numero conto',
    'spectre_extra_key_blocked_amount'     => 'Importo bloccato',
    'spectre_extra_key_available_amount'   => 'Importo disponibile',
    'spectre_extra_key_credit_limit'       => 'Limite di credito',
    'spectre_extra_key_interest_rate'      => 'Tasso d\'interesse',
    'spectre_extra_key_expiry_date'        => 'Data scadenza',
    'spectre_extra_key_open_date'          => 'Data apertura',
    'spectre_extra_key_current_time'       => 'Ora corrente',
    'spectre_extra_key_current_date'       => 'Data corrente',
    'spectre_extra_key_cards'              => 'Carte',
    'spectre_extra_key_units'              => 'Unità',
    'spectre_extra_key_unit_price'         => 'Prezzo unità',
    'spectre_extra_key_transactions_count' => 'Conteggio delle transazioni',

    // various other strings:
    'imported_from_account'                => 'Importato da ":account"',
];
