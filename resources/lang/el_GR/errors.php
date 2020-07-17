<?php

/**
 * firefly.php
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
    '404_header'              => 'Το Firefly III δεν μπορεί να βρει αυτή τη σελίδα.',
    '404_page_does_not_exist' => 'Η σελίδα που ζητήσατε δεν υπάρχει. Βεβαιωθείτε ότι δεν έχετε εισαγάγει λάθος διεύθυνση URL. Μήπως κάνατε τυπογραφικό λάθος;',
    '404_send_error'          => 'Εάν καταλήξατε σε αυτή τη σελίδα από αυτόματη ανακατεύθυνση, σας ζητώ συγγνώμη. Υπάρχει αναφορά αυτού του σφάλματος στα αρχεία καταγραφής σας και θα ήμουν ευγνώμων εάν μου στείλετε το σφάλμα.',
    '404_github_link'         => 'Εάν είστε βέβαιοι ότι αυτή η σελίδα πρέπει να υπάρχει, ανοίξτε ένα νέο θέμα στο <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Ούπς',
    'fatal_error'             => 'Παρουσιάστηκε σοβαρό σφάλμα. Ελέγξτε τα αρχεία καταγραφής στο "storage/logs" ή χρησιμοποιήστε την εντολή "docker logs -f [container]" για να δείτε τι συμβαίνει.',
    'maintenance_mode'        => 'Το Firefly III βρίσκεται σε λειτουργία συντήρησης.',
    'be_right_back'           => 'Επιστρέφω αμέσως!',
    'check_back'              => 'Το Firefly III είναι εκτός λειτουργίας για κάποια απαραίτητη συντήρηση. Ελέγξτε ξανά σε ένα δευτερόλεπτο.',
    'error_occurred'          => 'Ωχ! Παρουσιάστηκε σφάλμα.',
    'error_not_recoverable'   => 'Δυστυχώς, αυτό το σφάλμα δεν ήταν δυνατό να ξεπεραστεί :(. Το Firefly III δε λειτουργεί. Το σφάλμα είναι:',
    'error'                   => 'Σφάλμα',
    'error_location'          => 'Αυτό το σφάλμα προέκυψε στο αρχείο <span style="font-family: monospace;">:file</span> στη γραμμή :line με κώδικα :code.',
    'stacktrace'              => 'Ιχνηλάτηση στοίβας',
    'more_info'               => 'Περισσότερες πληροφορίες',
    'collect_info'            => 'Συλλέξτε περισσότερες πληροφορίες στον κατάλογο <code>storage/logs</code> όπου θα βρείτε αρχεία καταγραφής. Εάν χρησιμοποιείτε το Docker, χρησιμοποιήστε το <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Μπορείτε να διαβάσετε περισσότερα σχετικά με τη συλλογή πληροφοριών σφάλματος <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">στο FAQ</a>.',
    'github_help'             => 'Λάβετε βοήθεια στο GitHub',
    'github_instructions'     => 'Είστε ευπρόσδεκτοι να ανοίξετε ένα νέο θέμα <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">στο GitHub</a></strong>.',
    'use_search'              => 'Χρησιμοποιήστε την αναζήτηση!',
    'include_info'            => 'Συμπεριλάβετε τις πληροφορίες <a href=":link">από αυτή τη σελίδα εντοπισμού σφαλμάτων</a>.',
    'tell_more'               => 'Πείτε μας λίγα περισσότερα από το "μου λέει Ουπς!"',
    'include_logs'            => 'Συμπεριλάβετε αρχεία καταγραφής σφαλμάτων (δείτε παραπάνω).',
    'what_did_you_do'         => 'Πείτε μας τι κάνατε.',

];
