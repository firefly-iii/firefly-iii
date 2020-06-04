<?php

/**
 * import.php
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
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Importa i dati in Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prerequisiti per il fornitore di importazione fittizio',
    'prerequisites_breadcrumb_spectre'    => 'Prerequisiti per Spectre',
    'job_configuration_breadcrumb'        => 'Configurazione per ":key"',
    'job_status_breadcrumb'               => 'Stato di importazione per ":key"',
    'disabled_for_demo_user'              => 'disabilitata nella demo',

    // index page:
    'general_index_intro'                 => 'Benvenuti nella routine di importazione di Firefly III. Esistono alcuni modi per importare dati in Firefly III, visualizzati qui come pulsanti.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Come descritto in <a href="https://www.patreon.com/posts/future-updates-30012174">questo post Patreon</a>, il modo in cui Firefly III gestisce l\'importazione dei dati cambierà. Ciò significa che l\'importatore CSV sarà spostato in un nuovo strumento separato. Puoi già testare questo strumento visitando <a href="https://github.com/firefly-iii/csv-importer">questo repository GitHub</a>. Prova il nuovo importatore e fammi sapere cosa ne pensi.',
    'final_csv_import'     => 'Come descritto in <a href="https://www.patreon.com/posts/future-updates-30012174">questo post Patreon</a>, il modo in cui Firefly III gestisce l\'importazione dei dati cambierà. Ciò significa che questa è l\'ultima versione di Firefly III che presenta l\'importatore CSV. È disponibile uno strumento separato che puoi provare tu stesso: <a href="https://github.com/firefly-iii/csv-importer">l\'importatore CSV di Firefly III</a>. Ti sarei molto grato se lo provassi e mi facessi sapere cosa ne pensi.',

    // import provider strings (index):
    'button_fake'                         => 'Esegui un\'importazione fittizia',
    'button_file'                         => 'Importa un file',
    'button_spectre'                      => 'Importa usando Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Prerequisiti di importazione',
    'need_prereq_intro'                   => 'Alcuni metodi di importazione richiedono la tua attenzione prima che possano essere utilizzati. Ad esempio, potrebbero richiedere speciali chiavi API o segreti dell\'applicazione. Puoi configurarli qui. L\'icona indica se questi prerequisiti sono stati soddisfatti.',
    'do_prereq_fake'                      => 'Prerequisiti per il fornitore fittizio',
    'do_prereq_file'                      => 'Prerequisiti per le importazioni da file',
    'do_prereq_spectre'                   => 'Prerequisiti per le importazioni usando Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Prerequisiti per un\'importazione dal fornitore di importazione fittizio',
    'prereq_fake_text'                    => 'Questo provider fittizio richiede una chiave API fittizia. Deve contenere 32 caratteri. È possibile utilizzare questa: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prerequisiti per un\'importazione utilizzando le API di Spectre',
    'prereq_spectre_text'                 => 'Per l\'importazione dei dati attraverso le API Spectre (v4), devi fornire a Firefly III due valori segreti. Questi si possono trovare nella <a href="https://www.saltedge.com/clients/profile/secrets">pagina dei segreti</a>.',
    'prereq_spectre_pub'                  => 'Allo stesso modo, l\'API Spectre deve conoscere la chiave pubblica che vedi qui sotto. Senza di essa, non ti riconoscerà. Per favore inserisci questa chiave pubblica nella tua <a href="https://www.saltedge.com/clients/profile/secrets">pagina dei segreti</a>.',
    'callback_not_tls'                    => 'Firefly III ha rilevato il seguenti URI di callback. Sembra che il tuo server non sia impostato per accettare le connessioni TLS (https). YNAB non accetterà questo URI. Puoi continuare con l\'importazione (poiché Firefly III potrebbe sbagliarsi) ma tienilo a mente.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Chiave API fittizia memorizzata correttamente!',
    'prerequisites_saved_for_spectre'     => 'ID dell\'app e segreto memorizzati!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Configurazione dell\'operazione - applicare le tue regole?',
    'job_config_apply_rules_text'         => 'Una volta avviato il fornitore fittizio, le tue regole possono essere applicate alle transazioni. Questo aggiunge del tempo all\'importazione.',
    'job_config_input'                    => 'Il tuo input',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Inserisci il nome dell\'album',
    'job_config_fake_artist_text'         => 'Molte routine di importazione presentano alcuni passaggi di configurazione da eseguire. Nel caso del fornitore di importazione fittizio, è necessario rispondere ad alcune domande strane. In questo caso, inserire "David Bowie" per continuare.',
    'job_config_fake_song_title'          => 'Inserisci il nome del brano',
    'job_config_fake_song_text'           => 'Menziona la canzone "Golden years" per continuare con l\'importazione fittizia.',
    'job_config_fake_album_title'         => 'Inserisci il nome dell\'album',
    'job_config_fake_album_text'          => 'Alcune routine di importazione richiedono dati aggiuntivi a metà dell\'importazione. Nel caso del fornitore di importazione fittizio, è necessario rispondere ad alcune domande strane. Inserire "Station to station" per continuare.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Configurazione importazione (1/4) - Carica il tuo file',
    'job_config_file_upload_text'         => 'Questa routine ti aiuterà a importare i file dalla tua banca in Firefly III. ',
    'job_config_file_upload_help'         => 'Seleziona il tuo file. Assicurati che il file sia codificato in UTF-8.',
    'job_config_file_upload_config_help'  => 'Se hai precedentemente importato i dati in Firefly III, potresti avere un file di configurazione, che preimposterà i valori di configurazione per te. Per alcune banche, altri utenti hanno gentilmente fornito il loro <a href="https://github.com/firefly-iii/import-configurations/wiki">file di configurazione</a>',
    'job_config_file_upload_type_help'    => 'Seleziona il tipo di file che caricherai',
    'job_config_file_upload_submit'       => 'Carica i file',
    'import_file_type_csv'                => 'CSV (valori separati da virgola)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Il file che hai caricato non è codificato come UTF-8 o ASCII. Firefly III non può gestire tali file. Utilizzare Notepad++ o Sublime per convertire il file in UTF-8.',
    'job_config_uc_title'                 => 'Configurazione di importazione (2/4) - Impostazione di base dei file',
    'job_config_uc_text'                  => 'Per poter importare correttamente il tuo file, ti preghiamo di convalidare le opzioni di seguito.',
    'job_config_uc_header_help'           => 'Seleziona questa casella se la prima riga del tuo file CSV sono i titoli delle colonne.',
    'job_config_uc_date_help'             => 'Formato della data e ora nel tuo file. Segui il formato indicato in <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">questa pagina</a>. Il valore predefinito analizzerà le date che assomigliano a questa: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Scegli il delimitatore di campo che viene utilizzato nel file di ingresso. Se non si è sicuri, la virgola è l\'opzione più sicura.',
    'job_config_uc_account_help'          => 'Se il tuo file NON contiene informazioni sui tuoi conti di attività, utilizza questo menu a discesa per selezionare a quale conto appartengono le transazioni nel file.',
    'job_config_uc_apply_rules_title'     => 'Applica regole',
    'job_config_uc_apply_rules_text'      => 'Applica le tue regole ad ogni transazione importata. Si noti che questo rallenta l\'importazione in modo significativo.',
    'job_config_uc_specifics_title'       => 'Opzioni specifiche della banca',
    'job_config_uc_specifics_txt'         => 'Alcune banche forniscono file formattati in modo errato. Firefly III può sistemarli automaticamente. Se la tua banca rende disponibili tali file ma non è elencata qui, ti preghiamo di segnalare il problema su GitHub.',
    'job_config_uc_submit'                => 'Continua',
    'invalid_import_account'              => 'Hai selezionato un conto non valido su cui effettuare l\'importazione.',
    'import_liability_select'             => 'Passività',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Scegli il tuo login',
    'job_config_spectre_login_text'       => 'Firefly III ha rilevato :count login esistenti nel tuo account Spectre. Quale vorresti usare per l\'importazione?',
    'spectre_login_status_active'         => 'Attivo',
    'spectre_login_status_inactive'       => 'Inattivo',
    'spectre_login_status_disabled'       => 'Disabilitato',
    'spectre_login_new_login'             => 'Accedi con un\'altra banca o con una di queste banche con credenziali diverse.',
    'job_config_spectre_accounts_title'   => 'Seleziona i conti dai quali importare',
    'job_config_spectre_accounts_text'    => 'Hai selezionato ":name" (:country). Hai :count conti disponibili da questo fornitore. Seleziona i conti attività di Firefly III in cui devono essere memorizzate le transazioni da questi conti. Ricorda che, per importare i dati, sia il conto di Firefly III sia il conto ":name" devono avere la stessa valuta.',
    'spectre_do_not_import'               => '(non importare)',
    'spectre_no_mapping'                  => 'Sembra che tu non abbia selezionato nessun account da cui importare.',
    'imported_from_account'               => 'Importato da ":account"',
    'spectre_account_with_number'         => 'Conto :number',
    'job_config_spectre_apply_rules'      => 'Applica regole',
    'job_config_spectre_apply_rules_text' => 'Per impostazione predefinita le tue regole verranno applicate alle transazioni create durante questa procedura di importazione. Se non vuoi che questo accada, deseleziona questa casella di controllo.',

    // job configuration for bunq:
    'should_download_config'              => 'Ti consigliamo di scaricare <a href=":route">il file di configurazione</a> per questa operazione. Ciò renderà le importazioni future più facili.',
    'share_config_file'                   => 'Se hai importato dati da una banca pubblica, dovresti <a href="https://github.com/firefly-iii/import-configurations/wiki">condividere il tuo file di configurazione</a> così da rendere più facile per gli altri utenti importare i loro dati. La condivisione del file di configurazione non espone i tuoi dettagli finanziari.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Stato',
    'spectre_extra_key_card_type'          => 'Tipo carta',
    'spectre_extra_key_account_name'       => 'Nome conto',
    'spectre_extra_key_client_name'        => 'Nome client',
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
    'spectre_extra_key_unit_price'         => 'Prezzo unitario',
    'spectre_extra_key_transactions_count' => 'Conteggio transazioni',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Configurazione di importazione (4/4) - Collega i dati importati con i dati di Firefly III',
    'job_config_map_text'             => 'Nelle seguenti tabelle, il valore a sinistra mostra le informazioni trovate nel file caricato. È tuo compito mappare questo valore, se possibile, su un valore già presente nel tuo database. Firefly si atterrà a questa mappatura. Se non ci sono valori da mappare o non si desidera mappare il valore specifico, non selezionare niente.',
    'job_config_map_nothing'          => 'Non ci sono dati presenti nel tuo file che puoi mappare a valori esistenti. Si prega di premere "Inizia l\'importazione" per continuare.',
    'job_config_field_value'          => 'Valore campo',
    'job_config_field_mapped'         => 'Mappato a',
    'map_do_not_map'                  => '(non mappare)',
    'job_config_map_submit'           => 'Inizia l\'importazione',


    // import status page:
    'import_with_key'                 => 'Importa con chiave \':key\'',
    'status_wait_title'               => 'Per favore attendere...',
    'status_wait_text'                => 'Questa finestra si chiuderà tra un momento.',
    'status_running_title'            => 'L\'importazione è in esecuzione',
    'status_job_running'              => 'Attendere, importazione in corso...',
    'status_job_storing'              => 'Attendere, memorizzazione dei dati...',
    'status_job_rules'                => 'Attendere, applicazione delle regole...',
    'status_fatal_title'              => 'Errore fatale',
    'status_fatal_text'               => 'L\'importazione ha subito un errore irreversibile. Scusa!',
    'status_fatal_more'               => 'Questo messaggio di errore (probabilmente molto criptico) è completato dai file di log, che puoi trovare sul tuo disco rigido, o nel contenitore Docker da cui esegui Firefly III.',
    'status_finished_title'           => 'Importazione completata',
    'status_finished_text'            => 'L\'importazione è finita.',
    'finished_with_errors'            => 'Si sono verificati alcuni errori durante l\'importazione. Controllali attentamente.',
    'unknown_import_result'           => 'Risultato di importazione sconosciuto',
    'result_no_transactions'          => 'Nessuna transazione è stata importata. Forse erano tutte dei duplicati o semplicemente non c\'era nessuna transazione da importare. Forse i file di log possono dirti cosa è successo. Questo è normale se importi i dati regolarmente.',
    'result_one_transaction'          => 'È stata importata esattamente una transazione. È memorizzata sotto l\'etichetta <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> dove è possibile ispezionarla ulteriormente.',
    'result_many_transactions'        => 'Firefly III ha importato :count transazioni. Sono memorizzate sotto l\'etichetta <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> dove è possibile ispezionarle ulteriormente.',

    // general errors and warnings:
    'bad_job_status'                  => 'Per accedere a questa pagina l\'operazione di importazione non può avere lo stato ":status".',

    // error message
    'duplicate_row'                   => 'La riga #:row (":description") non può essere importato poiché è già esistente.',

];
