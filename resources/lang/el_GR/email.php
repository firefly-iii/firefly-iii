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
    'closing'                          => 'Μπιπ μπιπ,',
    'signature'                        => 'Το Ρομπότ Αλληλογραφίας του Firefly III',
    'footer_ps'                        => 'ΥΓ: Αυτό το μήνυμα στάλθηκε επειδή μια αίτηση από την IP :ipAddress το ενεργοποίησε.',

    // admin test
    'admin_test_subject'               => 'Ένα δοκιμαστικό μήνυμα από την εγκατάσταση του Firefly III',
    'admin_test_body'                  => 'Αυτό είναι ένα δοκιμαστικό μήνυμα από την εγκατάσταση του Firefly III. Αποστάλθηκε στο :email.',

    // new IP
    'login_from_new_ip'                => 'Νέα σύνδεση χρήστη στο Firefly III',
    'new_ip_body'                      => 'Το Firefly III εντόπισε μια νέα σύνδεση στο λογαριασμό σας από μια άγνωστη διεύθυνση IP. Αν δεν συνδεθήκατε ποτέ από την παρακάτω διεύθυνση IP ή έγινε πριν από περισσότερο από έξι μήνες, το Firefly III θα σας προειδοποιήσει.',
    'new_ip_warning'                   => 'Αν αναγνωρίζετε αυτή τη διεύθυνση IP ή τη σύνδεση χρήστη, μπορείτε να αγνοήσετε αυτό το μήνυμα. Αν δεν συνδεθήκατε, αν δεν έχετε ιδέα για το τι είναι αυτό, επαληθεύστε ένα ασφαλή κωδικό πρόσβασης, αλλάξτε τον και αποσυνδεθείτε από όλες τις άλλες συνεδρίες. Για να το κάνετε αυτό, πηγαίνετε στη σελίδα του προφίλ σας. Φυσικά έχετε ήδη ενεργοποιημένο το 2FactorAuthentication, έτσι? Μείνετε ασφαλείς!',
    'ip_address'                       => 'Διεύθυνση IP',
    'host_name'                        => 'Διακομιστής',
    'date_time'                        => 'Ημερομηνία και ώρα',

    // access token created
    'access_token_created_subject'     => 'Δημιουργήθηκε ένα νέο διακριτικό πρόσβασης',
    'access_token_created_body'        => 'Κάποιος (ελπίζω εσείς) μόλις δημιούργησε ένα νέο Διακριτικό Πρόσβασης Firefly III API για το δικό σας λογαριασμό χρήστη.',
    'access_token_created_explanation' => 'Με αυτό το διακριτικό μπορούν να έχουν πρόσβαση σε <strong>όλες</strong> τις οικονομικές σας εγγραφές μέσω του Firefly III API.',
    'access_token_created_revoke'      => 'Εάν δεν είστασταν εσείς, παρακαλώ να ανακαλέσετε αυτό το διακριτικό το συντομότερο δυνατό στο :url.',

    // registered
    'registered_subject'               => 'Καλωσήρθατε στο Firefly III!',
    'registered_welcome'               => 'Καλώς ήρθατε στο <a style="color:#337ab7" href=":address">Firefly III</a>. Η εγγραφή σας έχει ολοκληρωθεί και αυτό το email είναι εδώ για επιβεβαίωση. Ναι!',
    'registered_pw'                    => 'Εάν έχετε ήδη ξεχάσει τον κωδικό πρόσβασής σας, παρακαλούμε να τον επαναφέρετε χρησιμοποιώντας το <a style="color:#337ab7" href=":address/password/reset">εργαλείο επαναφοράς κωδικού πρόσβασης</a>.',
    'registered_help'                  => 'Υπάρχει ένα εικονίδιο βοήθειας στην επάνω δεξιά γωνία κάθε σελίδας. Αν χρειάζεστε βοήθεια, κάντε κλικ σε αυτό!',
    'registered_doc_html'              => 'Αν δεν το έχετε ήδη κάνει, παρακαλώ διαβάστε το <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">θεωρητικό πλάνο</a>.',
    'registered_doc_text'              => 'Αν δεν το έχετε ήδη κάνει, διαβάστε τον οδηγό πρώτης χρήσης και την πλήρη περιγραφή.',
    'registered_closing'               => 'Καλή Διασκέδαση!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Επαναφορά κωδικού πρόσβασης:',
    'registered_doc_link'              => 'Τεκμηρίωση:',

    // email change
    'email_change_subject'             => 'Η διεύθυνση email σας στο Firefly III έχει αλλάξει',
    'email_change_body_to_new'         => 'Εσείς ή κάποιος με πρόσβαση στο λογαριασμό σας στο Firefly III έχει αλλάξει τη διεύθυνση ηλεκτρονικού ταχυδρομείου σας. Αν δεν περιμένατε αυτό το μήνυμα, παρακαλώ αγνοήστε και διαγράψτε το.',
    'email_change_body_to_old'         => 'Εσείς ή κάποιος με πρόσβαση στο λογαριασμό σας στο Firefly III έχει αλλάξει τη διεύθυνση ηλεκτρονικού ταχυδρομείου σας. Αν δεν το περιμένατε αυτό, <strong>πρέπει</strong> να ακολουθήσετε τον παρακάτω "σύνδεσμο αναίρεσης" για να προστατεύσετε τον λογαριασμό σας!',
    'email_change_ignore'              => 'Αν ξεκινήσατε αυτήν την αλλαγή, μπορείτε να αγνοήσετε με ασφάλεια αυτό το μήνυμα.',
    'email_change_old'                 => 'Η παλιά διεύθυνση ηλεκτρονικού ταχυδρομείου ήταν: :email',
    'email_change_old_strong'          => 'Η παλιά διεύθυνση ηλεκτρονικού ταχυδρομείου ήταν: <strong>:email</strong>',
    'email_change_new'                 => 'Η νέα διεύθυνση ηλεκτρονικού ταχυδρομείου είναι: :email',
    'email_change_new_strong'          => 'Η νέα διεύθυνση ηλεκτρονικού ταχυδρομείου είναι: <strong>:email</strong>',
    'email_change_instructions'        => 'Δεν μπορείτε να χρησιμοποιήσετε το Firefly III μέχρι να επιβεβαιώσετε αυτήν την αλλαγή. Ακολουθήστε τον παρακάτω σύνδεσμο για να το κάνετε.',
    'email_change_undo_link'           => 'Για να αναιρέσετε την αλλαγή, ακολουθήστε αυτόν τον σύνδεσμο:',

    // OAuth token created
    'oauth_created_subject'            => 'Δημιουργήθηκε ένας νέος πελάτης OAuth',
    'oauth_created_body'               => 'Κάποιος (ελπίζω εσείς) μόλις δημιούργησε ένα νέο πελάτη API Firefly III OAuth για το λογαριασμό χρήστη σας. Έχει την ένδειξη ":name" και έχει URL επιστροφής κλήσης <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Με αυτόν τον πελάτη, μπορούν να έχουν πρόσβαση σε <strong>όλες</strong> τις οικονομικες σας εγγραφές μέσω του Firefly III API.',
    'oauth_created_undo'               => 'Αν δεν ήσασταν εσείς, παρακαλώ να ανακαλέσετε αυτόν τον πελάτη το συντομότερο δυνατό στο :url.',

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
    'error_stacktrace'                 => 'Το πλήρες stacktrace είναι παρακάτω. Αν νομίζετε ότι αυτό είναι ένα σφάλμα στο Firefly III, μπορείτε να προωθήσετε αυτό το μήνυμα στο <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. Αυτό μπορεί να βοηθήσει στη διόρθωση του σφάλματος που μόλις αντιμετωπίσατε.',
    'error_github_html'                => 'Αν προτιμάτε, μπορείτε επίσης να ανοίξετε ένα νέο ζήτημα στο <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Αν προτιμάτε, μπορείτε επίσης να ανοίξετε ένα νέο ζήτημα στο https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Το πλήρες stacktrace είναι παρακάτω:',

    // report new journals
    'new_journals_subject'             => 'Το Firefly III έχει δημιουργήσει μια νέα συναλλαγή|Το Firefly III έχει δημιουργήσει :count νέες συναλλαγές',
    'new_journals_header'              => 'Το Firefly III έχει δημιουργήσει μια συναλλαγή για εσάς. Μπορείτε να τη βρείτε στην εγκατάσταση Firefly ΙΙΙ:|Το Firefly III έχει δημιουργήσει :count συναλλαγές για εσάς. Μπορείτε να τις βρείτε στην εγκατάσταση Firefly III:',
];
