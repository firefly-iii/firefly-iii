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
    'index_breadcrumb'                    => 'Εισαγωγή δεδομένων στο Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Προϋποθέσεις για τον πάροχο ψευδής εισαγωγής',
    'prerequisites_breadcrumb_spectre'    => 'Προϋποθέσεις για το Spectre',
    'job_configuration_breadcrumb'        => 'Παραμετροποίηση για ":key"',
    'job_status_breadcrumb'               => 'Εισαγωγή κατάσταστης για ":key"',
    'disabled_for_demo_user'              => 'απενεργοποιημένο στο demo',

    // index page:
    'general_index_intro'                 => 'Καλωσορίσατε στην ρουτίνα εισαγωγής του Firefly III. Υπάρχουν διάφοροι τρόποι εισαγωγής δεδομένων στο Firefly III, που απεικονίζονται εδώ ως κουμπιά.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Όπως αποτυπώνεται σε <a href="https://www.patreon.com/posts/future-updates-30012174"> αυτό το Patreon post</a>, ο τρόπος που το Firefly III διαχειρίζεται την εισαγωγή δεδομένων πρόκειται να αλλάξει. Αυτό σημαίνει ότι η εισαγωγή CSV θα μετακινηθεί σε ένα νέο, ξεχωριστό εργαλείο. Μπορείτε να δοκιμάσετε ήδη αυτό το beta εργαλείο εάν επισκεφτείτε <a href="https://github.com/firefly-iii/csv-importer"> αυτό το Github repository</a>. Θα το εκτιμούσα εάν δοκιμάζατε τη νέα εισαγωγή και μου κοινοποιούσατε τις απόψεις σας.',
    'final_csv_import'     => 'Όπως περιγράφεται σε αυτή την <a href="https://www.patreon.com/posts/future-updates-30012174">ανάρτηση στο Patreon</a>, ο τρόπος με τον οποίο το Firefly III χειρίζεται τα δεδομένα κατά τη διαδικασία εισαγωγής πρόκειται να αλλάξει. Αυτό σημαίνει ότι αυτή είναι η τελευταία έκδοση του Firefly III που θα περιλαμβάνει αυτό τον εισαγωγέα αρχείων CSV. Διατίθεται τώρα ένα ξεχωριστό εργαλείο που θα πρέπει να το δοκιμάσετε: <a href="https://github.com/firefly-iii/csv-importer">ο εισαγωγέας CSV για το Firefly III</a>. Θα το εκτιμούσα αν δοκιμάσετε το νέο εργαλείο εισαγωγής και μοιραστείτε τις εντυπώσεις σας.',

    // import provider strings (index):
    'button_fake'                         => 'Προσποιηθήτε μία εισαγωγή',
    'button_file'                         => 'Εισαγωγή ενός αρχείου',
    'button_spectre'                      => 'Εισαγωγή με τη χρήση Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Προϋποθέσεις εισαγωγής',
    'need_prereq_intro'                   => 'Κάποιες μέθοδοι εισαγωγής χρειάζονται την προσοχή σας πριν τη χρήση. Για παράδειγμα, μπορεί να απαιτούν ειδικά κλειδιά API ή κωδικούς εφαρμογής. Μπορείτε να τις ρυθμίσετε εδώ. Το εικονίδιο καταδεικνύει εάν οι προϋποθέσεις έχουν ικανοποιηθεί.',
    'do_prereq_fake'                      => 'Προϋποθέσεις για τον ψευδή πάροχο',
    'do_prereq_file'                      => 'Προϋποθέσεις για εισαγωγές αρχείων',
    'do_prereq_spectre'                   => 'Προϋποθέσεις για εισαγωγές με χρήση Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Προϋποθέσεις για μία εισαγωγή από τον πάροχο ψευδής εισαγωγής',
    'prereq_fake_text'                    => 'Αυτός ο ψευδής πάροχος απαιτεί ένα ψευδές κλειδί API. Πρέπει να είναι μεγέθους 32 χαρακτήρων. Μπορείτε να χρησιμοποιήσετε το εξής: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Προϋποθέσεις για μία εισαγωγή με τη χρήση του API Spectre',
    'prereq_spectre_text'                 => 'Για να μπορέσετε να εισάγετε δεδομένα με τη χρήση του Spectre API (v4), πρέπει να παρέχετε το Firefly III με δύο μυστικές τιμές. Μπορούν να βρεθούν στη <a href="https://www.saltedge.com/clients/profile/secrets">σελίδα μυστικών</a>.',
    'prereq_spectre_pub'                  => 'Επίσης, το Spectre API χρειάζεται να γνωρίζει το δημόσιο κλειδί που βλέπετε παρακάτω. Χωρίς αυτό, δε θα σας αναγνωρίζει. Παρακαλούμε εισάγετε αυτό το δημόσιο κλειδί στη <a href="https://www.saltedge.com/clients/profile/secrets">σελίδα μυστικών</a> σας.',
    'callback_not_tls'                    => 'Το Firefly III ανίχνευσε την ακόλουθη επανάκληση URI. Φαίνεται πως ο εξυπηρετητής σας δεν έχει ρυθμιστεί να δέχεται συνδέσεις TLS (https). Το YNAB δε δέχεται αυτό το URI. Μπορείτε να συνεχίσετε με την εισαγωγή (επειδή το Firefly III μπορεί να κάνει λάθος) αλλά κρατήστε το στο μυαλό σας.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Το ψευδές κλειδί API αποθηκεύτηκε επιτυχώς!',
    'prerequisites_saved_for_spectre'     => 'Το αναγνωριστικό ID και το μυστικό της εφαρμογής αποθηκεύτηκαν!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Παραμετροποίηση εργασίας - εφαρμογή των κανόνων σας;',
    'job_config_apply_rules_text'         => 'Μόλις ο ψευδής πάροχος εκτελεστεί, οι κανόνες σας μπορούν να εφαρμοστούν στις συναλλαγές. Αυτό προσθέτει χρόνο στην εισαγωγή.',
    'job_config_input'                    => 'Η εισαγωγή σας',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Εισάγετε όνομα άλμπουμ',
    'job_config_fake_artist_text'         => 'Πολλές ρουτίνες εισαγωγής έχουν μερικά βήματα παραμετροποίησης που πρέπει να ολοκληρώσετε. Στην περίπτωση του ψευδούς παρόχου, πρέπει να απαντήσετε σε κάποιες περίεργες ερωτήσεις. Σε αυτή την περίπτωση, εισάγετε "David Bowie" για να συνεχίσετε.',
    'job_config_fake_song_title'          => 'Εισάγετε όνομα τραγουδιού',
    'job_config_fake_song_text'           => 'Αναφέρετε το τραγούδι "Golden years" για να συνεχίσετε με την ψευδή εισαγωγή.',
    'job_config_fake_album_title'         => 'Εισάγετε όνομα άλμπουμ',
    'job_config_fake_album_text'          => 'Κάποιες ρουτίνες εισαγωγής απαιτούν επιπλέον δεδομένα ενδιάμεσα στην εισαγωγή. Στην περίπτωση του ψευδούς παρόχου, πρέπει να απαντήσετε σε κάποιες περίεργες ερωτήσεις. Εισάγετε "Station to station" για να συνεχίσετε.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Ρύθμιση εισαγωγής (1/4) - Μεταφορτώστε το αρχείο σας',
    'job_config_file_upload_text'         => 'Αυτή η ρουτίνα θα σας βοηθήσει να εισάγετε αρχεία από την τράπεζά σας στο Firefly III. ',
    'job_config_file_upload_help'         => 'Επιλέξτε το αρχείο σας. Παρακαλώ σιγουρευτείτε ότι το αρχείο είναι κωδικοποιημένο σε UTF-8.',
    'job_config_file_upload_config_help'  => 'Εάν έχετε ήδη εισάγει δεδομένα στο Firefly III, μπορεί να έχετε ένα αρχείο παραμετροποίησης, το οποίο θα προ-ρυθμίσει τιμές παραμετροποίησης για εσάς. Για κάποιες τράπεζες, άλλοι χρήστες έχουν παρέχει ευγενικά το <a href="https://github.com/firefly-iii/import-configurations/wiki">αρχείο παραμετροποίησης</a> τους',
    'job_config_file_upload_type_help'    => 'Επιλέξτε τον τύπο του αρχείου που θα μεταφορτώσετε',
    'job_config_file_upload_submit'       => 'Μεταφόρτωση αρχείων',
    'import_file_type_csv'                => 'CSV (τιμές διαχωρισμένες με ερωτηματικό)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Το αρχείο που μεταφορτώσατε δεν είναι κωδικοποιημένο σε UTF-8 ή ASCII. Το Firefly III δεν μπορεί να χειριστεί τέτοια αρχεία. Παρακαλούμε χρησιμοποιήστε το Notepad++ ή το Sublime για να μετατρέψετε το αρχείο σας σε UTF-8.',
    'job_config_uc_title'                 => 'Ρύθμιση εισαγωγής (2/4) - Ρύθμιση βασικού αρχείου',
    'job_config_uc_text'                  => 'Για να μπορέσετε να εισάγετε το αρχείο σας ορθώς, παρακαλώ επιβεβαιώστε τις παρακάτω επιλογές.',
    'job_config_uc_header_help'           => 'Τσεκάρετε αυτό το πλαίσιο εάν η πρώτη γραμμή στο αρχείο σας CSV είναι η τίτλοι των στηλών.',
    'job_config_uc_date_help'             => 'Μορφή ημερομηνίας και ώρας στο αρχείο σας. Ακολουθήστε τη μορφή όπως <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">αυτή η σελίδα</a> ορίζει. Η προεπιλεγμένη τιμή θα αναλύσει ημερομηνίες που μοιάζουν έτσι: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Επιλέξτε το διαχωριστή πεδίου που χρησιμοποιήται στο αρχείο εισαγωγής σας. Εάν δεν είστε σίγουροι, το ερωτηματικό είναι η ασφαλέστερη επιλογή.',
    'job_config_uc_account_help'          => 'Εάν το αρχείο σας ΔΕΝ περιέχει πληροφορίες για τον(ους) αποταμιευτικό(ούς) λογαριασμό(ούς) σας, χρησιμοποιήστε αυτή τη λίστα για να επιλέξετε σε ποιόν λογαριασμό οι συναλλαγές του αρχείου ανήκουν.',
    'job_config_uc_apply_rules_title'     => 'Εφαρμογή κανόνων',
    'job_config_uc_apply_rules_text'      => 'Εφαρμόζει τους κανόνες σας σε κάθε εισαγώμενη συναλλαγή. Σημειώστε ότι αυτή καθυστερεί την εισαγωγή σημαντικά.',
    'job_config_uc_specifics_title'       => 'Ειδικές ρυθμίσεις τράπεζας',
    'job_config_uc_specifics_txt'         => 'Κάποιες τράπεζες παρέχουν κακώς μορφοποιημένα αρχεία. Το Firefly III μπορεί να τα διορθώσει αυτόματα. Εάν η τράπεζα σας παρέχει τέτοια αρχεία αλλά δεν αναφέρεται εδώ, παρακαλούμε ανοίξτε ένα θέμα στο GitHub.',
    'job_config_uc_submit'                => 'Συνέχεια',
    'invalid_import_account'              => 'Δεν έχετε επιλέξει έναν έγκυρο λογαριασμό για να εισάγετε σε αυτόν.',
    'import_liability_select'             => 'Υποχρέωση',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Επιλέξτε τη σύνδεσή σας',
    'job_config_spectre_login_text'       => 'Το Firefly III βρήκε :count συνδέσεις στο λογαριασμό Spectre σας. Από ποιά θέλετε να γίνει η εισαγωγή;',
    'spectre_login_status_active'         => 'Ενεργό',
    'spectre_login_status_inactive'       => 'Ανενεργό',
    'spectre_login_status_disabled'       => 'Απενεργοποιημένο',
    'spectre_login_new_login'             => 'Συνδεθείτε με άλλη τράπεζα, ή μία από αυτές τις τράπεζες με διαφορετικά διαπιστευτήρια.',
    'job_config_spectre_accounts_title'   => 'Επιλέξτε λογαριασμούς από τους οποίους θα εισάγετε',
    'job_config_spectre_accounts_text'    => 'Έχετε επιλέξει ":name" (:country). Έχετε :count λογαριασμό(ούς) διαθέσιμους από αυτόν το πάροχο. Παρακαλώ επιλέξτε τους αποταμιευτικούς λογαριασμούς Firefly III που θα αποθηκευτηκούν οι συναλλαγές από αυτούς τους λογαριασμούς. Θυμηθείτε, ότι για την εισαγωγή δεδομένων πρέπει και o λογαριασμός Firefly III και λογαριασμός-":name" να έχουν το ίδιο νόμισμα.',
    'spectre_do_not_import'               => '(να μη γίνει εισαγωγή)',
    'spectre_no_mapping'                  => 'Φαίνεται πως δεν έχετε επιλέξει κανέναν λογαριασμό για να εισάγετε από αυτόν.',
    'imported_from_account'               => 'Εισάχθηκε από ":account"',
    'spectre_account_with_number'         => 'Λογαριασμός :number',
    'job_config_spectre_apply_rules'      => 'Εφαρμογή κανόνων',
    'job_config_spectre_apply_rules_text' => 'Από προεπιλογή, οι κανόνες σας θα εφαρμοστούν στις συναλλαγές που δημιουργήθηκαν κατά τη διάρκεια αυτής της ρουτίνας εισαγωγής. Εάν δεν επιθυμείτε να συμβεί αυτό, αποεπιλέξτε αυτό το πλαίσιο ελέγχου.',

    // job configuration for bunq:
    'should_download_config'              => 'Θα πρέπει να κατεβάσετε <a href=":route">το αρχείο παραμετροποίησης</a> για αυτή την εργασία. Αυτό θα κάνει τις μελλοντικές εισαγωγές ευκολότερες.',
    'share_config_file'                   => 'Εάν έχετε εισάγει δεδομένα από μία δημόσια τράπεζα, καλό θα ήταν να <a href="https://github.com/firefly-iii/import-configurations/wiki">μοιραστείτε το αρχείο παραμετροποίησης</a> ώστε να διευκολύνετε άλλους χρήστες με την εισαγωγή των δεδομένων τους. Η διαμοίραση τους αχρείου παραμετροποίησης δεν αποκαλύπτει τις οικονομικές σας λεπτομέρειες.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Κατάσταση',
    'spectre_extra_key_card_type'          => 'Τύπος κάρτας',
    'spectre_extra_key_account_name'       => 'Όνομα λογαριασμού',
    'spectre_extra_key_client_name'        => '\'Ονομα πελάτη',
    'spectre_extra_key_account_number'     => 'Αριθμός λογαριασμού',
    'spectre_extra_key_blocked_amount'     => 'Δεσμευμένο ποσό',
    'spectre_extra_key_available_amount'   => 'Διαθέσιμο ποσό',
    'spectre_extra_key_credit_limit'       => 'Πιστωτικό όριο',
    'spectre_extra_key_interest_rate'      => 'Επιτόκιο',
    'spectre_extra_key_expiry_date'        => 'Ημερομηνία λήξης',
    'spectre_extra_key_open_date'          => 'Ημερομηνία ανοίγματος',
    'spectre_extra_key_current_time'       => 'Τρέχουσα ώρα',
    'spectre_extra_key_current_date'       => 'Τρέχουσα ημερομηνία',
    'spectre_extra_key_cards'              => 'Κάρτες',
    'spectre_extra_key_units'              => 'Μονάδες',
    'spectre_extra_key_unit_price'         => 'Τιμή μονάδας',
    'spectre_extra_key_transactions_count' => 'Μετρητής συναλλαγής',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Ρύθμιση εισαγωγής (4/4) - Σύνδεση δεδομένων εισαγωγής στα δεδομένα Firefly III',
    'job_config_map_text'             => 'Στους ακόλουθους πίνακες, η αριστερή τιμή σας δείχνει πληροφορίες που βρέθηκαν στο μεταφορτωμένο σας αρχείο. Δουλειά σας είναι να αντιστοιχήσετε αυτή τη τιμή, εάν είναι δυνατόν, σε μία τιμή που υπάρχει ήδη στη βάση δεδομένων σας. Το Firefly θα επιμείνει σε αυτή την αντιστοίχηση. Εάν δεν υπάρχουν τιμή προς αντιστοίχηση, ή δεν επιθυμείτε να αντιστοιχήσετε τη συγκεκριμένη τιμή, επιλέξτε τίποτα.',
    'job_config_map_nothing'          => 'Δεν υπάρχουν δεδομένα στο αρχείο σας που μπορείτε να αντιστοιχήσετε σε υπάρχουσες τιμές. Παρακαλώ πιέστε την "Έναρξη της εισαγωγής" για να συνεχίσετε.',
    'job_config_field_value'          => 'Τιμή πεδίου',
    'job_config_field_mapped'         => 'Έχει αντιστοιχισθεί σε',
    'map_do_not_map'                  => '(δεν έχει αντιστοιχηθεί)',
    'job_config_map_submit'           => 'Έναρξη της εισαγωγής',


    // import status page:
    'import_with_key'                 => 'Εισαγωγή με κλειδί \':key\'',
    'status_wait_title'               => 'Παρακαλώ αναμείνατε...',
    'status_wait_text'                => 'Αυτό το πλάισιο θα εξαφανιστεί σε μία στιγμή.',
    'status_running_title'            => 'Η εισαγωγή εκτελείται',
    'status_job_running'              => 'Παρακαλώ περιμένετε, εκτελείται η εισαγωγή...',
    'status_job_storing'              => 'Παρακαλώ περιμένετε, αποθήκευση δεδομένων...',
    'status_job_rules'                => 'Παρακαλώ περιμένετε, εκτέλεση κανόνων...',
    'status_fatal_title'              => 'Ανεπανόρθωτο σφάλμα',
    'status_fatal_text'               => 'Η εισαγωγή αντιμετώπισε ένα σφάλμα που δεν μπόρεσε να αντιπαρέλθει. Συγγνώμη!',
    'status_fatal_more'               => 'Αυτό το (πιθανώς πολύ αινιγματικό) μήνυμα σφάλματος συνοδεύεται από αρχεία καταγραφής, τα οποία μπορείτε να βρείτε στο σκληρό σας δίσκο, ή στο δοχείο Docker από το οποίο εκτελέσατε το Firefly III.',
    'status_finished_title'           => 'Η εισαγωγή ολοκληρώθηκε',
    'status_finished_text'            => 'Η εισαγωγή έχει ολοκληρωθεί.',
    'finished_with_errors'            => 'Παρουσιάστηκαν κάποια σφάλματα κατά την εισαγωγή. Παρακαλώ ανασκοπήστε τα προσεκτικά.',
    'unknown_import_result'           => 'Άγνωστο αποτέλεσμα εισαγωγής',
    'result_no_transactions'          => 'Καμία συναλλαγή δεν εισάχθηκε. Ίσως ήταν όλες διπλότυπες ή δεν υπήρχε καμία συναλλαγή προς εισαγωγή. Ίσως τα αρχεία καταγραφής μπορούν να σας πουν τί έγινε. Εάν εισάγετε δεδομένα συχνά, αυτό είναι φυσιολογικό.',
    'result_one_transaction'          => 'Ακριβώς μία συναλλαγή εισάχθηκε. Αποθηκεύτηκε κάτω από την ετικέτα <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> από όπου μπορείτε να την επιθεωρήσετε περαιτέρω.',
    'result_many_transactions'        => 'Το Firefly III έχει εισάγει :count συναλλαγές. Αποθηκεύτηκαν κάτω από την ετικέτα <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> από όπου μπορείτε να τις επιθεωρήσετε επιπλέον.',

    // general errors and warnings:
    'bad_job_status'                  => 'Για να έχετε πρόσβαση σε αυτή τη σελίδα, η εργασία εισαγωγής δεν μπορεί να έχει κατάσταση ":status".',

    // error message
    'duplicate_row'                   => 'Η σειρά #:row (":description") δεν μπόρεσε να εισαχθεί. Υπάρχει ήδη.',

];
