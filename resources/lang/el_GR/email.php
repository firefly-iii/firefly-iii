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
    'greeting'                         => 'Γεια σου,',
    'closing'                          => 'Beep boop,',
    'signature'                        => 'Το Ρομπότ Αλληλογραφίας του Firefly III',
    'footer_ps'                        => 'PS: This message was sent because a request from IP :ipAddress triggered it.',

    // admin test
    'admin_test_subject'               => 'Ένα δοκιμαστικό μήνυμα από την εγκατάσταση του Firefly III',
    'admin_test_body'                  => 'Αυτό είναι ένα δοκιμαστικό μήνυμα από την εγκατάσταση του Firefly III. Αποστάλθηκε στο :email.',

    // new IP
    'login_from_new_ip'                => 'Νέα σύνδεση χρήστη στο Firefly III',
    'new_ip_body'                      => 'Το Firefly III εντόπισε μια νέα σύνδεση στο λογαριασμό σας από μια άγνωστη διεύθυνση IP. Αν δεν συνδεθήκατε ποτέ από την παρακάτω διεύθυνση IP ή έγινε πριν από περισσότερο από έξι μήνες, το Firefly III θα σας προειδοποιήσει.',
    'new_ip_warning'                   => 'If you recognize this IP address or the login, you can ignore this message. If you didn\'t login, of if you have no idea what this is about, verify your password security, change it, and log out all other sessions. To do this, go to your profile page. Of course you have 2FA enabled already, right? Stay safe!',
    'ip_address'                       => 'Διεύθυνση IP',
    'host_name'                        => 'Διακομιστής',
    'date_time'                        => 'Ημερομηνία και ώρα',

    // access token created
    'access_token_created_subject'     => 'Δημιουργήθηκε ένα νέο διακριτικό πρόσβασης',
    'access_token_created_body'        => 'Somebody (hopefully you) just created a new Firefly III API Access Token for your user account.',
    'access_token_created_explanation' => 'With this token, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'access_token_created_revoke'      => 'If this wasn\'t you, please revoke this token as soon as possible at :url.',

    // registered
    'registered_subject'               => 'Καλωσήρθατε στο Firefly III!',
    'registered_welcome'               => 'Welcome to <a style="color:#337ab7" href=":address">Firefly III</a>. Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                    => 'If you have forgotten your password already, please reset it using <a style="color:#337ab7" href=":address/password/reset">the password reset tool</a>.',
    'registered_help'                  => 'There is a help-icon in the top right corner of each page. If you need help, click it!',
    'registered_doc_html'              => 'If you haven\'t already, please read the <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">grand theory</a>.',
    'registered_doc_text'              => 'If you haven\'t already, please read the first use guide and the full description.',
    'registered_closing'               => 'Καλή Διασκέδαση!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Επαναφορά κωδικού πρόσβασης:',
    'registered_doc_link'              => 'Τεκμηρίωση:',

    // email change
    'email_change_subject'             => 'Η διεύθυνση email σας στο Firefly III έχει αλλάξει',
    'email_change_body_to_new'         => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this message, please ignore and delete it.',
    'email_change_body_to_old'         => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you <strong>must</strong> follow the "undo"-link below to protect your account!',
    'email_change_ignore'              => 'If you initiated this change, you may safely ignore this message.',
    'email_change_old'                 => 'Η παλιά διεύθυνση ηλεκτρονικού ταχυδρομείου ήταν: :email',
    'email_change_old_strong'          => 'Η παλιά διεύθυνση ηλεκτρονικού ταχυδρομείου ήταν: <strong>:email</strong>',
    'email_change_new'                 => 'Η νέα διεύθυνση ηλεκτρονικού ταχυδρομείου είναι: :email',
    'email_change_new_strong'          => 'Η νέα διεύθυνση ηλεκτρονικού ταχυδρομείου είναι: <strong>:email</strong>',
    'email_change_instructions'        => 'You cannot use Firefly III until you confirm this change. Please follow the link below to do so.',
    'email_change_undo_link'           => 'Για να αναιρέσετε την αλλαγή, ακολουθήστε αυτόν τον σύνδεσμο:',

    // OAuth token created
    'oauth_created_subject'            => 'Δημιουργήθηκε ένας νέος πελάτης OAuth',
    'oauth_created_body'               => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'With this client, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'oauth_created_undo'               => 'If this wasn\'t you, please revoke this client as soon as possible at :url.',

    // reset password
    'reset_pw_subject'                 => 'Αίτημα επαναφοράς κωδικού πρόσβασης',
    'reset_pw_instructions'            => 'Κάποιος θέλει να κάνει επαναφορά για τον κωδικό πρόσβασής σας. Αν ήσασταν εσείς, παρακαλούμε ακολουθήστε τον παρακάτω σύνδεσμο για να το κάνετε.',
    'reset_pw_warning'                 => '<strong>ΠΑΡΑΚΑΛΩ</strong> βεβαιωθείτε ότι ο σύνδεσμος πηγαίνει πραγματικά στη διεύθυνση του Firefly III που χρησιμοποιείτε!',

    // error
    'error_subject'                    => 'Βρέθηκε ένα σφάλμα στο Firefly III',
    'error_intro'                      => 'Το Firefly III v:version συνάντησε ένα σφάλμα: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Το σφάλμα ήταν του τύπου ":class".',
    'error_timestamp'                  => 'Το σφάλμα προέκυψε την/στις: :time.',
    'error_location'                   => 'Αυτό το σφάλμα προέκυψε στο αρχείο "<span style="font-family: monospace;">:file</span>" στη γραμμή :line με τον κωδικό :code.',
    'error_user'                       => 'Το σφάλμα προέκυψε στο χρήστη #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Δεν υπήρξε κανένας συνδεδεμένος χρήστης για αυτό το σφάλμα ή κανένας χρήστης δεν εντοπίστηκε.',
    'error_ip'                         => 'Η διεύθυνση IP που σχετίζεται με αυτό το σφάλμα είναι: :ip',
    'error_url'                        => 'Το URL είναι: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                => 'If you prefer, you can also open a new issue on <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'If you prefer, you can also open a new issue on https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'The full stacktrace is below:',

    // report new journals
    'new_journals_subject'             => 'Firefly III has created a new transaction|Firefly III has created :count new transactions',
    'new_journals_header'              => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',
];
