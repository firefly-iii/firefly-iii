<?php
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
declare(strict_types=1);

return [
    // status of import:
    'status_wait_title'                    => 'Tolong tunggu sebentar...',
    'status_wait_text'                     => 'Kotak ini akan hilang dalam sekejap.',
    'status_fatal_title'                   => 'Sebuah kesalahan fatal terjadi',
    'status_fatal_text'                    => 'Kesalahan fatal terjadi, dimana rutinitas impor tidak dapat dipulihkan. Silakan lihat penjelasannya di bawah ini.',
    'status_fatal_more'                    => 'Jika kesalahannya adalah time-out, impor akan berhenti setengah jalan. Untuk beberapa konfigurasi server, hanya server yang berhenti sementara impor terus berjalan di latar belakang. Untuk memverifikasi ini, periksa file log. Jika masalah berlanjut, pertimbangkan untuk mengimpor lebih dari baris perintah.',
    'status_ready_title'                   => 'Impor sudah siap untuk memulai',
    'status_ready_text'                    => 'Impor sudah siap dimulai. Semua konfigurasi yang perlu Anda lakukan sudah selesai. Silahkan download file konfigurasi. Ini akan membantu Anda dengan impor seandainya tidak berjalan seperti yang direncanakan. Untuk benar-benar menjalankan impor, Anda dapat menjalankan perintah berikut di konsol Anda, atau menjalankan impor berbasis web. Bergantung pada konfigurasi Anda, impor konsol akan memberi Anda lebih banyak umpan balik.',
    'status_ready_noconfig_text'           => 'Impor sudah siap dimulai. Semua konfigurasi yang perlu Anda lakukan sudah selesai. Untuk benar-benar menjalankan impor, Anda dapat menjalankan perintah berikut di konsol Anda, atau menjalankan impor berbasis web. Bergantung pada konfigurasi Anda, impor konsol akan memberi Anda lebih banyak umpan balik.',
    'status_ready_config'                  => 'Download konfigurasi',
    'status_ready_start'                   => 'Mulai impor',
    'status_ready_share'                   => 'Harap pertimbangkan untuk mendownload konfigurasi Anda dan membagikannya di <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">pusat konfigurasi impor</a></strong>. Ini akan memungkinkan pengguna Firefly III lainnya untuk mengimpor file mereka dengan lebih mudah.',
    'status_job_new'                       => 'Pekerjaan itu baru.',
    'status_job_configuring'               => 'Impor sedang dikonfigurasi.',
    'status_job_configured'                => 'Impor dikonfigurasi.',
    'status_job_running'                   => 'Impor sedang berjalan.. mohon menunggu..',
    'status_job_error'                     => 'Pekerjaan telah menimbulkan kesalahan.',
    'status_job_finished'                  => 'Impor telah selesai!',
    'status_running_title'                 => 'Impor sedang berjalan',
    'status_running_placeholder'           => 'Silakan tunggu update...',
    'status_finished_title'                => 'Rutin impor selesai',
    'status_finished_text'                 => 'Rutin impor telah mengimpor data Anda.',
    'status_errors_title'                  => 'Kesalahan selama impor',
    'status_errors_single'                 => 'Terjadi kesalahan saat mengimpor. Itu tidak tampak berakibat fatal.',
    'status_errors_multi'                  => 'Beberapa kesalahan terjadi saat impor. Ini tidak tampak berakibat fatal.',
    'status_bread_crumb'                   => 'Status impor',
    'status_sub_title'                     => 'Status impor',
    'config_sub_title'                     => 'Siapkan impor Anda',
    'status_finished_job'                  => 'The :count transactions imported can be found in tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III has not collected any journals from your import file.',
    'import_with_key'                      => 'Impor dengan kunci \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Impor setup (1/4) - Upload file Anda',
    'file_upload_text'                     => 'Rutin ini akan membantu Anda mengimpor file dari bank Anda ke Firefly III. Silakan periksa halaman bantuan di pojok kanan atas.',
    'file_upload_fields'                   => 'Bidang',
    'file_upload_help'                     => 'Pilih file anda',
    'file_upload_config_help'              => 'Jika sebelumnya Anda mengimpor data ke Firefly III, Anda mungkin memiliki file konfigurasi, yang akan menetapkan nilai konfigurasi untuk Anda. Untuk beberapa bank, pengguna lain dengan ramah memberikan <a href="https://github.com/firefly-iii/import-configurations/wiki">berkas konfigurasi</a> mereka',
    'file_upload_type_help'                => 'Pilih jenis file yang akan anda upload',
    'file_upload_submit'                   => 'Unggah berkas',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (nilai yang dipisahkan koma)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Penyiapan impor (2/4) - Penyiapan impor CSV dasar',
    'csv_initial_text'                     => 'Untuk dapat mengimpor file Anda dengan benar, mohon validasi pilihan di bawah ini.',
    'csv_initial_box'                      => 'Penyiapan impor CSV dasar',
    'csv_initial_box_title'                => 'Opsi penyiapan impor CSV dasar',
    'csv_initial_header_help'              => 'Centang kotak ini jika baris pertama file CSV Anda adalah judul kolom.',
    'csv_initial_date_help'                => 'Format waktu tanggal di CSV Anda. Ikuti format seperti <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">laman ini</a> menunjukkan. Nilai default akan mengurai tanggal yang terlihat seperti ini: :dateExample.',
    'csv_initial_delimiter_help'           => 'Pilih pembatas lapangan yang digunakan dalam file masukan Anda. Jika tidak yakin, koma adalah pilihan teraman.',
    'csv_initial_import_account_help'      => 'Jika file CSV TIDAK berisi informasi tentang akun aset Anda, gunakan dropdown ini untuk memilih akun mana yang menjadi tempat transaksi di CSV.',
    'csv_initial_submit'                   => 'Lanjutkan dengan langkah 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Terapkan aturan',
    'file_apply_rules_description'         => 'Terapkan peraturan Anda Perhatikan bahwa ini memperlambat impor secara signifikan.',
    'file_match_bills_title'               => 'Cocokkan tagihan',
    'file_match_bills_description'         => 'Cocokkan tagihan Anda dengan penarikan yang baru dibuat. Perhatikan bahwa ini memperlambat impor secara signifikan.',

    // file, roles config
    'csv_roles_title'                      => 'Pengaturan impor (3/4) - Tentukan peran masing-masing kolom',
    'csv_roles_text'                       => 'Setiap kolom dalam file CSV Anda berisi data tertentu. Tolong tunjukkan jenis data yang harus diharapkan oleh importir. Pilihan untuk "memetakan" data berarti Anda akan menghubungkan setiap entri yang ditemukan di kolom ke nilai di database Anda. Kolom yang sering dipetakan adalah kolom yang berisi IBAN dari akun lawan. Itu bisa dengan mudah disesuaikan dengan keberadaan IBAN di database Anda.',
    'csv_roles_table'                      => 'Meja',
    'csv_roles_column_name'                => 'Nama kolom',
    'csv_roles_column_example'             => 'Kolom contoh data',
    'csv_roles_column_role'                => 'Data kolom berarti',
    'csv_roles_do_map_value'               => 'Peta nilai-nilai ini',
    'csv_roles_column'                     => 'Kolom',
    'csv_roles_no_example_data'            => 'Tidak ada data contoh yang tersedia',
    'csv_roles_submit'                     => 'Lanjutkan dengan langkah 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'foreign_amount_warning'               => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    // file, map data
    'file_map_title'                       => 'Pengaturan impor (4/4) - Sambungkan data impor ke data Firefly III',
    'file_map_text'                        => 'Pada tabel berikut, nilai kiri menunjukkan informasi yang Anda temukan di file yang Anda upload. Adalah tugas Anda untuk memetakan nilai ini, jika mungkin, ke nilai yang sudah ada di database Anda. Firefly akan menempel pada pemetaan ini. Jika tidak ada nilai untuk dipetakan, atau Anda tidak ingin memetakan nilai spesifiknya, pilih yang tidak ada.',
    'file_map_field_value'                 => 'Nilai lapangan',
    'file_map_field_mapped_to'             => 'Dipetakan ke',
    'map_do_not_map'                       => '(jangan memetakan)',
    'file_map_submit'                      => 'Mulai impor',
    'file_nothing_to_map'                  => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',

    // map things.
    'column__ignore'                       => '(abaikan kolom ini)',
    'column_account-iban'                  => 'Akun aset (IBAN)',
    'column_account-id'                    => 'Asset account ID (matching FF3)',
    'column_account-name'                  => 'Akun aset (nama)',
    'column_amount'                        => 'Jumlah',
    'column_amount_foreign'                => 'Amount (in foreign currency)',
    'column_amount_debit'                  => 'Jumlah (kolom debit)',
    'column_amount_credit'                 => 'Jumlah (kolom kredit)',
    'column_amount-comma-separated'        => 'Jumlah (koma sebagai pemisah desimal)',
    'column_bill-id'                       => 'Bill ID (matching FF3)',
    'column_bill-name'                     => 'Nama tagihan',
    'column_budget-id'                     => 'Budget ID (matching FF3)',
    'column_budget-name'                   => 'Nama anggaran',
    'column_category-id'                   => 'Category ID (matching FF3)',
    'column_category-name'                 => 'Nama Kategori',
    'column_currency-code'                 => 'Kode mata uang (ISO 4217)',
    'column_foreign-currency-code'         => 'Foreign currency code (ISO 4217)',
    'column_currency-id'                   => 'Currency ID (matching FF3)',
    'column_currency-name'                 => 'Currency name (matching FF3)',
    'column_currency-symbol'               => 'Currency symbol (matching FF3)',
    'column_date-interest'                 => 'Tanggal perhitungan bunga',
    'column_date-book'                     => 'Tanggal pemesanan transaksi',
    'column_date-process'                  => 'Tanggal proses transaksi',
    'column_date-transaction'              => 'Tanggal',
    'column_description'                   => 'Deskripsi',
    'column_opposing-iban'                 => 'Akun lawan (IBAN)',
    'column_opposing-id'                   => 'Opposing account ID (matching FF3)',
    'column_external-id'                   => 'ID eksternal',
    'column_opposing-name'                 => 'Akun lawan (nama)',
    'column_rabo-debit-credit'             => 'Indikator debit / kredit khusus Rabobank',
    'column_ing-debit-credit'              => 'Indikator debit / kredit ING yang spesifik',
    'column_sepa-ct-id'                    => 'ID Transfer Kredit SEPA end-to-end',
    'column_sepa-ct-op'                    => 'Akun lawan kredit SEPA yang berlawanan',
    'column_sepa-db'                       => 'SEPA Direct Debit',
    'column_tags-comma'                    => 'Tag (dipisahkan koma)',
    'column_tags-space'                    => 'Tag (spasi terpisah)',
    'column_account-number'                => 'Akun aset (nomor rekening)',
    'column_opposing-number'               => 'Akun lawan (nomor rekening)',
    'column_note'                          => 'Catatan (s)',

    // prerequisites
    'prerequisites'                        => 'Prerequisites',

    // bunq
    'bunq_prerequisites_title'             => 'Prasyarat untuk impor dari bunq',
    'bunq_prerequisites_text'              => 'Untuk mengimpor dari bunq, Anda perlu mendapatkan kunci API. Anda bisa melakukan ini melalui aplikasi.',

    // Spectre
    'spectre_title'                        => 'Impor menggunakan momok',
    'spectre_prerequisites_title'          => 'Prasyarat untuk impor menggunakan momok',
    'spectre_prerequisites_text'           => 'In order to import data using the Spectre API, you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'                => 'Impor hanya akan berfungsi saat Anda memasukkan kunci publik ini di <a href="https://www.saltedge.com/clients/security/edit">halaman keamanan</a> Anda.',
    'spectre_accounts_title'               => 'Select accounts to import from',
    'spectre_accounts_text'                => 'Each account on the left below has been found by Spectre and can be imported into Firefly III. Please select the asset account that should hold any given transactions. If you do not wish to import from any particular account, remove the check from the checkbox.',
    'spectre_do_import'                    => 'Yes, import from this account',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Card type',
    'spectre_extra_key_account_name'       => 'Account name',
    'spectre_extra_key_client_name'        => 'Client name',
    'spectre_extra_key_account_number'     => 'Account number',
    'spectre_extra_key_blocked_amount'     => 'Blocked amount',
    'spectre_extra_key_available_amount'   => 'Available amount',
    'spectre_extra_key_credit_limit'       => 'Credit limit',
    'spectre_extra_key_interest_rate'      => 'Interest rate',
    'spectre_extra_key_expiry_date'        => 'Expiry date',
    'spectre_extra_key_open_date'          => 'Open date',
    'spectre_extra_key_current_time'       => 'Current time',
    'spectre_extra_key_current_date'       => 'Current date',
    'spectre_extra_key_cards'              => 'Cards',
    'spectre_extra_key_units'              => 'Units',
    'spectre_extra_key_unit_price'         => 'Unit price',
    'spectre_extra_key_transactions_count' => 'Transaction count',

    // various other strings:
    'imported_from_account'                => 'Imported from ":account"',
];

