<?php

/**
 * import.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    'prerequisites_breadcrumb_bunq'       => 'Προϋποθέσεις για το bunq',
    'prerequisites_breadcrumb_ynab'       => 'Προϋποθέσεις για το YNAB',
    'job_configuration_breadcrumb'        => 'Παραμετροποίηση για ":key"',
    'job_status_breadcrumb'               => 'Εισαγωγή κατάσταστης για ":key"',
    'disabled_for_demo_user'              => 'απενεργοποιημένο στο demo',

    // index page:
    'general_index_intro'                 => 'Καλωσορίσατε στην ρουτίνα εισαγωγής του Firefly III. Υπάρχουν διάφοροι τρόποι εισαγωγής δεδομένων στο Firefly III, που απεικονίζονται εδώ ως κουμπιά.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Προσποιηθήτε μία εισαγωγή',
    'button_file'                         => 'Εισαγωγή ενός αρχείου',
    'button_bunq'                         => 'Εισαγωγή από το bunq',
    'button_spectre'                      => 'Εισαγωγή με τη χρήση Spectre',
    'button_plaid'                        => 'Εισαγωγή με τη χρήση Plaid',
    'button_yodlee'                       => 'Εισαγωγή με τη χρήση Yodlee',
    'button_quovo'                        => 'Εισαγωγή με τη χρήση Quovo',
    'button_ynab'                         => 'Εισαγωγή από Χρειάζεστε Έναν Προϋπολογισμό',
    'button_fints'                        => 'Εισαγωγή με τη χρήση FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Εισαγωγή προϋποθέσεων',
    'need_prereq_intro'                   => 'Κάποιες μέθοδοι εισαγωγής χρειάζονται την προσοχή σας πριν τη χρήση. Για παράδειγμα, μπορεί να απαιτούν ειδικά κλειδιά API ή κωδικούς εφαρμογής. Μπορείτε να τις ρυθμίσετε εδώ. Το εικονίδιο καταδεικνύει εάν οι προϋποθέσεις έχουν ικανοποιηθεί.',
    'do_prereq_fake'                      => 'Προϋποθέσεις για τον ψευδή πάροχο',
    'do_prereq_file'                      => 'Προϋποθέσεις για εισαγωγές αρχείων',
    'do_prereq_bunq'                      => 'Προϋποθέσεις για εισαγωγές από bunq',
    'do_prereq_spectre'                   => 'Προϋποθέσεις για εισαγωγές με χρήση Spectre',
    'do_prereq_plaid'                     => 'Προϋποθέσεις για εισαγωγές με χρήση Plaid',
    'do_prereq_yodlee'                    => 'Προϋποθέσεις για εισαγωγές με χρήση Yodlee',
    'do_prereq_quovo'                     => 'Προϋποθέσεις για εισαγωγές με χρήση Quovo',
    'do_prereq_ynab'                      => 'Προϋποθέσεις για εισαγωγές από YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Προϋποθέσεις για μία εισαγωγή από τον πάροχο ψευδής εισαγωγής',
    'prereq_fake_text'                    => 'Αυτός ο ψευδής πάροχος απαιτεί ένα ψευδές κλειδί API. Πρέπει να είναι μεγέθους 32 χαρακτήρων. Μπορείτε να χρησιμοποιήσετε το εξής: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Προϋποθέσεις για μία εισαγωγή με τη χρήση του API Spectre',
    'prereq_spectre_text'                 => 'Για να μπορέσετε να εισάγετε δεδομένα με τη χρήση του Spectre API (v4), πρέπει να παρέχετε το Firefly III με δύο μυστικές τιμές. Μπορούν να βρεθούν στη <a href="https://www.saltedge.com/clients/profile/secrets">σελίδα μυστικών</a>.',
    'prereq_spectre_pub'                  => 'Επίσης, το Spectre API χρειάζεται να γνωρίζει το δημόσιο κλειδί που βλέπετε παρακάτω. Χωρίς αυτό, δε θα σας αναγνωρίζει. Παρακαλούμε εισάγετε αυτό το δημόσιο κλειδί στη <a href="https://www.saltedge.com/clients/profile/secrets">σελίδα μυστικών</a> σας.',
    'prereq_bunq_title'                   => 'Προϋποθέσεις για μία εισαγωγή από το bunq',
    'prereq_bunq_text'                    => 'Για να είστε σε θέση να εισάγετε από bunq, χρειάζεται να προμηθευτείτε ένα κλειδί API. Μπορείτε να το κάνετε αυτό μέσω της εφαρμογής. Παρακαλώ σημειώστε ότι η λειτουργία εισαγωγής από bunq είναι σε BETA. Έχει δοκιμαστεί μόνο με το sandbox API.',
    'prereq_bunq_ip'                      => 'Το bunq απαιτεί την εξωτερική σας διεύθυνση IP. Το Firefly III δοκίμασε να τη συμπληρώσει χρησιμοποιώντας <a href="https://www.ipify.org/">την ipify υπηρεσία</a>. Σιγουρευτείτε ότι αυτή η διεύθυνση IP είναι σωστή, αλλιώς η εισαγωγή θα αποτύχει.',
    'prereq_ynab_title'                   => 'Προϋποθέσεις για μία εισαγωγή από YNAB',
    'prereq_ynab_text'                    => 'Για να είστε σε θέση να κατεβάσετε συναλλαγές από YNAB, παρακαλώ δημιουργήστε μία νέα εφαρμογή στη <a href="https://app.youneedabudget.com/settings/developer">Σελίδα Ρυθμίσεων Προγραμματιστή</a> και εισάγετε το αναγνωριστικό ID και το μυστικό του client σε αυτή τη σελίδα.',
    'prereq_ynab_redirect'                => 'Για την ολοκλήρωση αυτής της παραμετροποίησης, εισάγετε το ακόλουθο URL στη <a href="https://app.youneedabudget.com/settings/developer">Σελίδα Ρυθμίσεων Προγραμματιστή</a> κάτω από το "Επανακατεύθυνση URI(s)".',
    'callback_not_tls'                    => 'Το Firefly III ανίχνευσε την ακόλουθη επανάκληση URI. Φαίνεται πως ο εξυπηρετητής σας δεν έχει ρυθμιστεί να δέχεται συνδέσεις TLS (https). Το YNAB δε δέχεται αυτό το URI. Μπορείτε να συνεχίσετε με την εισαγωγή (επειδή το Firefly III μπορεί να κάνει λάθος) αλλά κρατήστε το στο μυαλό σας.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Το ψευδές κλειδί API αποθηκεύτηκε επιτυχώς!',
    'prerequisites_saved_for_spectre'     => 'Το αναγνωριστικό ID και το μυστικό της εφαρμογής αποθηκεύτηκαν!',
    'prerequisites_saved_for_bunq'        => 'Το κλειδί API και η IP αποθηκεύτηκαν!',
    'prerequisites_saved_for_ynab'        => 'Το αναγνωριστικό ID πελάτη και το μυστικό YNAB αποθηκεύτηκαν!',

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
    'job_config_bunq_accounts_title'      => 'λογαριασμοί bunq',
    'job_config_bunq_accounts_text'       => 'Αυτοί είναι οι λογαριασμοί που σχετίζονται με τον λογαριασμό σας bunq. Παρακαλώ επιλέξτε τους λογαριασμούς από τους οποίους θέλετε να εισάγετε, και σε ποιόν λογαριασμό οι συναλλαγές αυτές θα εισαχθούν.',
    'bunq_no_mapping'                     => 'Φαίνεται πως δεν έχετε επιλέξει κανένα λογαριασμό.',
    'should_download_config'              => 'Θα πρέπει να κατεβάσετε <a href=":route">το αρχείο παραμετροποίησης</a> για αυτή την εργασία. Αυτό θα κάνει τις μελλοντικές εισαγωγές ευκολότερες.',
    'share_config_file'                   => 'Εάν έχετε εισάγει δεδομένα από μία δημόσια τράπεζα, καλό θα ήταν να <a href="https://github.com/firefly-iii/import-configurations/wiki">μοιραστείτε το αρχείο παραμετροποίησης</a> ώστε να διευκολύνετε άλλους χρήστες με την εισαγωγή των δεδομένων τους. Η διαμοίραση τους αχρείου παραμετροποίησης δεν αποκαλύπτει τις οικονομικές σας λεπτομέρειες.',
    'job_config_bunq_apply_rules'         => 'Εφαρμογή κανόνων',
    'job_config_bunq_apply_rules_text'    => 'Από προεπιλογή, οι κανόνες σας θα εφαρμοστούν στις συναλλαγές που δημιουργήθηκαν κατά τη διάρκεια αυτής της ρουτίνας εισαγωγής. Εάν δεν επιθυμείτε να συμβεί αυτό, αποεπιλέξτε αυτό το πλαίσιο ελέγχου.',
    'bunq_savings_goal'                   => 'Στόχος αποταμιεύσεων: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Ο λογαριασμός bunq έκλεισε',

    'ynab_account_closed'                  => 'Ο λογαριασμός έκλεισε!',
    'ynab_account_deleted'                 => 'Ο λογαριασμός διεγράφη!',
    'ynab_account_type_savings'            => 'λογαριασμός ταμιευτηρίου',
    'ynab_account_type_checking'           => 'λογαριασμός όψεως',
    'ynab_account_type_cash'               => 'λογαριασμός μετρητών',
    'ynab_account_type_creditCard'         => 'πιστωτική κάρτα',
    'ynab_account_type_lineOfCredit'       => 'γραμμή πίστωσης',
    'ynab_account_type_otherAsset'         => 'άλλος αποταμιευτικός λογαριασμός',
    'ynab_account_type_otherLiability'     => 'άλλες υποχρεώσεις',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'εμπορικός λογαριασμός',
    'ynab_account_type_investmentAccount'  => 'επενδυτικός λογαριασμός',
    'ynab_account_type_mortgage'           => 'υποθήκη',
    'ynab_do_not_import'                   => '(να μη γίνει εισαγωγή)',
    'job_config_ynab_apply_rules'          => 'Εφαρμογή κανόνων',
    'job_config_ynab_apply_rules_text'     => 'Από προεπιλογή, οι κανόνες σας θα εφαρμοστούν στις συναλλαγές που δημιουργήθηκαν κατά τη διάρκεια αυτής της ρουτίνας εισαγωγής. Εάν δεν επιθυμείτε να συμβεί αυτό, αποεπιλέξτε αυτό το πλαίσιο ελέγχου.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Επιλέξτε τον προϋπολογισμό σας',
    'job_config_ynab_select_budgets_text'  => 'Έχετε :count προϋπολογισμούς αποθηκευμένους σε YNAB. Παρακαλώ επιλέξτε έναν από τον οποίο το Firefly III θα εισάγει τις συναλλαγές.',
    'job_config_ynab_no_budgets'           => 'Δεν υπάρχουν διαθέσιμοι προϋπολογισμοί από τους οποίους να γίνει εισαγωγή.',
    'ynab_no_mapping'                      => 'Φαίνεται πως δεν έχετε επιλέξει κανέναν λογαριασμό για να εισάγετε από αυτόν.',
    'job_config_ynab_bad_currency'         => 'Δεν μπορείτε να κάνετε εισαγωγή από τον(τους) ακόλουθο(ους) προϋπολογισμό(ούς), γιατί δεν έχετε λογαριασμούς με το ίδιο νόμισμα όπως αυτοί οι προϋπολογισμοί.',
    'job_config_ynab_accounts_title'       => 'Επιλογή λογαριασμών',
    'job_config_ynab_accounts_text'        => 'Έχετε τους ακόλουθους λογαριασμού διαθέσιμούς σε αυτόν τον προϋπολογισμό. Παρακαλώ επιλέξτε από ποιούς λογαριασμούς θέλετε να εισάγετε, και πού θέλετε να αποθηκευτούν οι συναλλαγές.',


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

    //job configuration for finTS
    'fints_connection_failed'              => 'Ένα σφάλμα παρουσιάστηκε κατά τη σύνδεση στη τράπεζά σας. Παρακαλώ σιγουρευτείτε ότι όλα τα δεδομένα που εισάγατε είναι σωστά. Αρχικό μήνυμα σφάλματος: :originalError',

    'job_config_fints_url_help'       => 'Π.χ. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Για πολλές τράπεζες αυτός είναι ο αριθμός λογαριασμού σας.',
    'job_config_fints_port_help'      => 'Η προεπιλεγμένη πόρτα είναι η 443.',
    'job_config_fints_account_help'   => 'Επιλέξτε τον τραπεζικό λογαριασμό για τον οποίο θέλετε να εισάγετε συναλλαγές.',
    'job_config_local_account_help'   => 'Επιλέξτε τον λογαριασμό Firefly II που αντιστοιχεί στον τραπεζικό σας λογαριασμό που επιλέξατε παραπάνω.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Δημιουργία καλύτερων περιγραφών στις εξαγωγές ING',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Τακτοποίηση παραθέσεων από αρχεία εξαγωγής SNS / Volksbank',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Διορθώνει πιθανά προβλήματα με τα αρχεία ABN AMRO',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Διορθώνει πιθανά προβλήματα με τα αρχεία Rabobank',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Διορθώνει πιθανά προβλήματα με τα αρχεία PC',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Διορθώνει πιθανά προβλήματα με τα αρχεία Belfius',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Διορθώνει πιθανά προβλήματα με τα αρχεία ING Belgium',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Ρύθμιση εισαγωγής (3/4) - Ορίστε το ρόλο κάθε στήλης',
    'job_config_roles_text'           => 'Κάθε στήλη στο CSV αρχείο σας περιέχει συγκεκριμένα δεδομένα. Παρακαλώ υποδείξτε τον τύπο δεδεομένων που πρέπει να περιμένει ο εισαγωγέας. Η επιλογή της "αντιστοίχησης" δεδομένων σημαίνει ότι θα συνδέσετε κάθε εισαγωγή που βρίσκεται σε μία στήλη σε μία τιμή στη βάση δεδομένων σας. Μια συχνά αντιστοιχούμενη στήλη είναι η στήλη που περιέχει το IBAN του αντιπαραθέμενου λογαριασμού. Αυτή μπορεί να αντιστοιχήσει έυκολα σε IBAN που υπάρχουν ήδη στη βάση δεδομένων σας.',
    'job_config_roles_submit'         => 'Συνέχεια',
    'job_config_roles_column_name'    => 'Όνομα της στήλης',
    'job_config_roles_column_example' => 'Παράδειγμα δεδομένων στήλης',
    'job_config_roles_column_role'    => 'Σημασία δεδομένων στήλης',
    'job_config_roles_do_map_value'   => 'Αντιστοίχηση αυτών των τιμών',
    'job_config_roles_no_example'     => 'Δεν είναι διαθέσιμα δεδομένα παραδειγμάτων',
    'job_config_roles_fa_warning'     => 'Εάν σημειώσετε μια στήλη ότι περιέχει ποσό σε ξένο νόμισμα, πρέπει επίσης να ορίσετε τη στήλη που περιέχει ποιό νόμισμα είναι αυτό.',
    'job_config_roles_rwarning'       => 'Τουλάχιστον, σημειώστε μία στήλη ως τη στήλη ποσού. Είναι σκόπιμο να επιλέξετε επίσης μία στήλη για την περιγραφή, ημερομηνία και του αντιπαραθέμενου λογαριασμού.',
    'job_config_roles_colum_count'    => 'Στήλη',
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

    // column roles for CSV import:
    'column__ignore'                  => '(αγνόηση αυτής της στήλης)',
    'column_account-iban'             => 'Αποταμιευτικός λογαριασμός (IBAN)',
    'column_account-id'               => 'Αναγνωριστικό ID αποταμιευτικού λογαριασμού (σε αντιστοίχιση FF3)',
    'column_account-name'             => 'Αποταμιευτικός λογαριασμός (όνομα)',
    'column_account-bic'              => 'Αποταμιευτικός λογαριασμός (BIC)',
    'column_amount'                   => 'Ποσό',
    'column_amount_foreign'           => 'Ποσό (σε ξένο νόμισμα)',
    'column_amount_debit'             => 'Ποσό (στήλη χρέους)',
    'column_amount_credit'            => 'Ποσό (στήλη πίστωσης)',
    'column_amount_negated'           => 'Ποσό (στήλη αναίρεσης)',
    'column_amount-comma-separated'   => 'Ποσό (κόμμα ως δεκαδικός διαχωριστής)',
    'column_bill-id'                  => 'Αναγνωριστικό ID λογαριασμού (αντιστοίχιση με FF3)',
    'column_bill-name'                => 'Όνομα λογαριασμού',
    'column_budget-id'                => 'Αναγνωριστικό ID προϋπολογισμού (αντιστοίχιση με FF3)',
    'column_budget-name'              => 'Όνομα προϋπολογισμού',
    'column_category-id'              => 'Αναγνωριστικό ID κατηγορίας (αντιστοίχιση με FF3)',
    'column_category-name'            => 'Όνομα κατηγορίας',
    'column_currency-code'            => 'Κωδικός νομίσματος (ISO 4217)',
    'column_foreign-currency-code'    => 'Κωδικός ξένου νομίσματος (ISO 4217)',
    'column_currency-id'              => 'Αναγνωριστικό ID νομίσματος (αντιστοίχιση με FF3)',
    'column_currency-name'            => 'Όνομα νομίσματος (αντιστοίχιση με FF3)',
    'column_currency-symbol'          => 'Σύμβολο νομίσματος (αντιστοίχιση με FF3)',
    'column_date-interest'            => 'Ημερομηνία υπολογισμού τοκισμού',
    'column_date-book'                => 'Ημερομηνία εγγραφής συνναλαγής',
    'column_date-process'             => 'Ημερομηνία επεξεργασίας συναλλαγής',
    'column_date-transaction'         => 'Ημερομηνία',
    'column_date-due'                 => 'Ημερομηνία λήξης συναλλαγής',
    'column_date-payment'             => 'Ημερομηνία πληρωμής συναλλαγής',
    'column_date-invoice'             => 'Ημερομηνία τιμολόγησης συναλλαγής',
    'column_description'              => 'Περιγραφή',
    'column_opposing-iban'            => 'Αντιπαραθέμενος λογαριασμός (IBAN)',
    'column_opposing-bic'             => 'Αντιπαραθέμενος λογαριασμός (BIC)',
    'column_opposing-id'              => 'Αναγνωριστικό ID αντιπαραθέμενου λογαριασμού (αντιστοίχιση με FF3)',
    'column_external-id'              => 'Εξωτερικό αναγνωριστικό ID',
    'column_opposing-name'            => 'Αντιπαραθέμενος λογαριασμός (όνομα)',
    'column_rabo-debit-credit'        => 'Συγκεκριμένος δείκτης χρέωσης/πίστωσης Rabobank',
    'column_ing-debit-credit'         => 'Συγκεκριμένος δείκτης χρέωσης/πίστωσης ING',
    'column_generic-debit-credit'     => 'Δείκτης χρέωσης/πίστωσης γενικής τράπεζας',
    'column_sepa_ct_id'               => 'Αναγνωριστικό SEPA από άκρη σε άκρη',
    'column_sepa_ct_op'               => 'Αναγνωριστικό Αντιπαραθέμενου Λογαριασμού SEPA',
    'column_sepa_db'                  => 'Αναγνωριστικό Εντολής SEPA',
    'column_sepa_cc'                  => 'Κωδικός εκκαθάρισης SEPA',
    'column_sepa_ci'                  => 'Αναγωριστικό Πιστωτή SEPA',
    'column_sepa_ep'                  => 'Εξωτερικός Σκοπός SEPA',
    'column_sepa_country'             => 'Κωδικός Χώρας SEPA',
    'column_sepa_batch_id'            => 'Αναγνωριστικό ID Παρτίδας SEPA',
    'column_tags-comma'               => 'Ετικέτες (διαχωριζόμενες με ερωτηματικό)',
    'column_tags-space'               => 'Ετικέτες (διαχωριζόμενες με κενό)',
    'column_account-number'           => 'Αποταμιευτικός λογαριασμός (αριθμός λογαριασμού)',
    'column_opposing-number'          => 'Αντιπαραθέμενος λογαριασμός (αριθμός λογαριασμού)',
    'column_note'                     => 'Σημείωση(εις)',
    'column_internal-reference'       => 'Εσωτερική αναφορά',

    // error message
    'duplicate_row'                   => 'Η σειρά #:row (":description") δεν μπόρεσε να εισαχθεί. Υπάρχει ήδη.',

];
