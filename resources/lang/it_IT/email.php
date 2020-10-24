<?php

/**
 * email.php
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
    // common items
    'greeting'                         => 'Ciao,',
    'closing'                          => 'Bip bop,',
    'signature'                        => 'Il Mail Robot di Firefly III',
    'footer_ps'                        => 'PS: Questo messaggio è stato inviato perché generato da una richiesta da :ipAddress.',

    // admin test
    'admin_test_subject'               => 'Un messaggio di prova dalla tua installazione di Firefly III',
    'admin_test_body'                  => 'Questo è un messaggio di prova dalla tua istanza Firefly III. È stato inviato a :email.',

    // new IP
    'login_from_new_ip'                => 'Nuovo accesso a Firefly III',
    'new_ip_body'                      => 'Firefly III ha rilevato un nuovo accesso al tuo account da un indirizzo IP sconosciuto. Se non hai mai effettuato l\'accesso dall\'indirizzo sottostante, o se è stato effettuato più di sei mesi fa, Firefly III ti avviserà.',
    'new_ip_warning'                   => 'Se riconosci questo indirizzo IP o l\'accesso, puoi ignorare questo messaggio. Se non hai effettuato l\'accesso, o se non hai idea di cosa si tratti, verifica la sicurezza della tua password, cambiala e disconnetti tutte le altre sessioni. Per fare questo, vai alla pagina del tuo profilo. Naturalmente hai già attivato l\'autenticazione a due fattori, giusto? Resta al sicuro!',
    'ip_address'                       => 'Indirizzo IP',
    'host_name'                        => 'Host',
    'date_time'                        => 'Date e ora',

    // access token created
    'access_token_created_subject'     => 'È stato creato un nuovo token di accesso',
    'access_token_created_body'        => 'Qualcuno (si spera tu) ha appena creato un nuovo token di accesso personale per le API di Firefly III per il tuo account utente.',
    'access_token_created_explanation' => 'Con questo token possono accedere a <strong>tutte</strong> le tuo informazioni finanziarie tramite l\'API di Firefly III.',
    'access_token_created_revoke'      => 'Se non sei stato tu, per favore revoca questo token il prima possibile all\'indirizzo :url.',

    // registered
    'registered_subject'               => 'Benvenuto in Firefly III!',
    'registered_welcome'               => 'Benvenuto in <a style="color:#337ab7" href=":address">Firefly III</a>. La tua registrazione è avvenuta e questa email è qui per confermarlo. Urrà!',
    'registered_pw'                    => 'Se hai già dimenticato la tua password, reimpostala usando <a style="color:#337ab7" href=":address/password/reset">lo strumento di reimpostazione password</a>.',
    'registered_help'                  => 'C\'è un\'icona di aiuto nell\'angolo in alto a destra di ogni pagina. Se hai bisogno di aiuto, clicca su di essa!',
    'registered_doc_html'              => 'If you haven\'t already, please read the <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">grand theory</a>.',
    'registered_doc_text'              => 'Se non lo hai già fatto, leggi la prima guida d\'uso e la descrizione completa.',
    'registered_closing'               => 'Divertiti!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Reimposta la password:',
    'registered_doc_link'              => 'Documentazione:',

    // email change
    'email_change_subject'             => 'Il tuo indirizzo email Firefly III è cambiato',
    'email_change_body_to_new'         => 'Tu o qualcuno che ha accesso al tuo account Firefly III ha cambiato il tuo indirizzo email. Se non ti aspettavi questo messaggio, ignoralo ed eliminalo.',
    'email_change_body_to_old'         => 'Tu o qualcuno che ha accesso al tuo account di Firefly III ha cambiato il tuo indirizzo email. Se non ti aspettavi che ciò accadesse, <strong>devi</strong> seguire il link di annullamento qui sotto per proteggere il tuo account!',
    'email_change_ignore'              => 'Se hai effettuato tu questa modifica, puoi ignorare questo messaggio.',
    'email_change_old'                 => 'Il vecchio indirizzo email era: :email',
    'email_change_old_strong'          => 'Il vecchio indirizzo email era: <strong>:email</strong>',
    'email_change_new'                 => 'Il nuovo indirizzo email è: :email',
    'email_change_new_strong'          => 'Il nuovo indirizzo email è: <strong>:email</strong>',
    'email_change_instructions'        => 'Non puoi usare Firefly III finché non confermi questa modifica. Per favore, segui il link qui sotto per farlo.',
    'email_change_undo_link'           => 'Per annullare la modifica, segui questo link:',

    // OAuth token created
    'oauth_created_subject'            => 'È stato creato un nuovo client OAuth',
    'oauth_created_body'               => 'Qualcuno (si spera tu) ha appena creato un nuovo client OAuth per le API di Firefly III per il tuo account utente. È etichettato ":name" e ha come URL di callback <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Con questo client possono accedere a <strong>tutte</strong> le tue informazioni finanziarie tramite l\'API di Firefly III.',
    'oauth_created_undo'               => 'Se non sei stato tu, per favore revoca questo client il prima possibile all\'indirizzo :url.',

    // reset password
    'reset_pw_subject'                 => 'La tua richiesta di reimpostazione password',
    'reset_pw_instructions'            => 'Qualcuno ha provato a reimpostare la password. Se è stato tu, segui il link qui sotto per farlo.',
    'reset_pw_warning'                 => '<strong>PER FAVORE</strong> controlla che il link ti porti al Firefly III che ti aspetti!',

    // error
    'error_subject'                    => 'Incontrato un errore in Firefly III',
    'error_intro'                      => 'Firefly III v:version ha riscontrato un errore: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'L\'errore era di tipo ":class".',
    'error_timestamp'                  => 'L\'errore si è verificato il: :time.',
    'error_location'                   => 'Questo errore si è verificato nel file <span style="font-family: monospace;">:file</span> alla riga :line con codice :code.',
    'error_user'                       => 'L\'errore è stato riscontrato dall\'utente #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Non c\'era alcun utente loggato per questo errore o nessun utente è stato rilevato.',
    'error_ip'                         => 'L\'indirizzo IP relativo a questo errore è: :ip',
    'error_url'                        => 'L\'URL è: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'Lo stacktrace completo è qui sotto. Se pensi che questo sia un bug in Firefly III, puoi inoltrare questo messaggio a <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Questo può aiutare a risolvere il bug che hai appena incontrato.',
    'error_github_html'                => 'Se preferisci puoi anche aprire una nuova issue su <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Se preferisci puoi anche aprire una nuova issue su https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Lo stacktrace completo è qui sotto:',

    // report new journals
    'new_journals_subject'             => 'Firefly III ha creato una nuova transazione|Firefly III ha creato :count nuove transazioni',
    'new_journals_header'              => 'Firefly III ha creato una transazione per te. Puoi trovarla nella tua installazione di Firefly III:|Firefly III ha creato :count transazioni per te. Puoi trovarle nella tua installazione di Firefly III:',
];
