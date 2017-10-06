<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Pengaturan impor (1/3) - Pengaturan dasar impor CSV',
    'initial_text'                  => 'Untuk dapat mengimpor file Anda dengan benar, mohon periksa pilihan-pilihan di bawah ini.',
    'initial_box'                   => 'Pengaturan dasar impor CSV',
    'initial_box_title'             => 'Opsi pengaturan dasar impor CSV',
    'initial_header_help'           => 'Centang kotak ini jika baris pertama file CSV Anda adalah judul kolom.',
    'initial_date_help'             => 'Format tanggal dan waktu dalam CSV Anda. Ikuti format seperti ditunjukkan pada <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">halaman ini</a>. Nilai default akan membaca tanggal yang serupa dengan ini: :dateExample.',
    'initial_delimiter_help'        => 'Pilih pembatas kolom yang digunakan dalam file masukan Anda. Jika tidak yakin, koma adalah pilihan teraman.',
    'initial_import_account_help'   => 'Jika file CSV Anda TIDAK berisi informasi tentang akun-akun aset Anda, gunakan daftar ini untuk memilih akun yang akan digunakan untuk menyimpan transaksi dari CSV Anda.',
    'initial_submit'                => 'Lanjutkan dengan langkah 2/3',

    // roles config
    'roles_title'                   => 'Pengaturan impor (2/3) - Tentukan peran masing-masing kolom',
    'roles_text'                    => 'Setiap kolom dalam file CSV Anda berisi data tertentu. Mohon tunjukkan jenis data yang akan dibaca oleh pengimpor. Pilihan untuk memetakan data berarti Anda akan menghubungkan setiap entri yang ditemukan di kolom ke nilai di basis data Anda. Kolom yang sering dipetakan adalah kolom yang berisi IBAN dari akun lawan. Itu dapat dengan mudah disesuaikan dengan IBAN yang sudah ada dalam basis data Anda.',
    'roles_table'                   => 'Tabel',
    'roles_column_name'             => 'Nama kolom',
    'roles_column_example'          => 'Contoh data di kolom ini',
    'roles_column_role'             => 'Arti data di kolom ini',
    'roles_do_map_value'            => 'Petakan nilai-nilai ini',
    'roles_column'                  => 'Kolom',
    'roles_no_example_data'         => 'Contoh data tidak tersedia',
    'roles_submit'                  => 'Lanjutkan dengan langkah 3/3',
    'roles_warning'                 => 'Tandai satu kolom sebagai kolom jumlah. Sebaiknya pilih juga kolom deskripsi, tanggal, dan akun lawan.',

    // map data
    'map_title'                     => 'Pengaturan impor (3/3) - Hubungkan data impor dengan data Firefly III',
    'map_text'                      => 'Pada tabel-tabel berikut, nilai kiri menunjukkan informasi yang ada dalam file CSV yang Anda unggah. Tugas Anda adalah memetakan nilai ini, jika memungkinkan, dengan nilai yang sudah ada dalam basis data Anda. Firefly akan terus menggunakan pemetaan ini. Jika tidak ada nilai untuk dipetakan, atau jika Anda tidak ingin memetakan nilai tertentu, jangan memilih apa-apa.',
    'map_field_value'               => 'Nilai kolom',
    'map_field_mapped_to'           => 'Dipetakan ke',
    'map_do_not_map'                => '(jangan petakan)',
    'map_submit'                    => 'Mulai impor',

    // map things.
    'column__ignore'                => '(abaikan kolom ini)',
    'column_account-iban'           => 'Akun aset (IBAN)',
    'column_account-id'             => 'ID akun aset (yang cocok dengan Firefly)',
    'column_account-name'           => 'Akun aset (nama)',
    'column_amount'                 => 'Jumlah',
    'column_amount-comma-separated' => 'Jumlah (koma sebagai tanda desimal)',
    'column_bill-id'                => 'ID Tagihan (yang cocok dengan Firefly)',
    'column_bill-name'              => 'Bill name',
    'column_budget-id'              => 'Budget ID (matching Firefly)',
    'column_budget-name'            => 'Budget name',
    'column_category-id'            => 'Category ID (matching Firefly)',
    'column_category-name'          => 'Category name',
    'column_currency-code'          => 'Currency code (ISO 4217)',
    'column_currency-id'            => 'Currency ID (matching Firefly)',
    'column_currency-name'          => 'Currency name (matching Firefly)',
    'column_currency-symbol'        => 'Currency symbol (matching Firefly)',
    'column_date-interest'          => 'Interest calculation date',
    'column_date-book'              => 'Transaction booking date',
    'column_date-process'           => 'Transaction process date',
    'column_date-transaction'       => 'Date',
    'column_description'            => 'Description',
    'column_opposing-iban'          => 'Opposing account (IBAN)',
    'column_opposing-id'            => 'Opposing account ID (matching Firefly)',
    'column_external-id'            => 'External ID',
    'column_opposing-name'          => 'Opposing account (name)',
    'column_rabo-debet-credit'      => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'       => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                => 'SEPA Direct Debet',
    'column_tags-comma'             => 'Tags (comma separated)',
    'column_tags-space'             => 'Tags (space separated)',
    'column_account-number'         => 'Asset account (account number)',
    'column_opposing-number'        => 'Opposing account (account number)',
];
