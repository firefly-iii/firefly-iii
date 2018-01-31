<?php
/**
 * intro.php
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
    // index
    'index_intro'                           => 'Selamat datang di halaman indeks Firefly III. Mohon luangkan waktu untuk menelusuri pengantar ini melihat bagaimana Firefly III bekerja.',
    'index_accounts-chart'                  => 'Bagan ini menunjukkan saldo akun aset anda saat ini, Anda dapat memilih akun yang terlihat di sini dalam preferensi anda.',
    'index_box_out_holder'                  => 'Kotak kecil dan kotak di samping kotak ini akan memberi anda gambaran singkat tentang situasi keuangan anda.',
    'index_help'                            => 'Jika anda memerlukan bantuan dengan halaman atau formulir, tekan tombol ini.',
    'index_outro'                           => 'Sebagian besar halaman Firefly III akan dimulai dengan petunjuk kecil seperti ini. Silahkan hubungi saya bila ada pertanyaan atau komentar. Selamat mencoba!',
    'index_sidebar-toggle'                  => 'Untuk membuat transakisi baru, akun atau hal lainnya, gunakan menu di bawah gambar ini.',

    // create account:
    'accounts_create_iban'                  => 'Berikan akun anda IBAN yang benar. Hal ini bisa membuat data impor sangat mudah di masa yang akan datang.',
    'accounts_create_asset_opening_balance' => 'Assets accounts may have an "opening balance", indicating the start of this account\'s history in Firefly III.',
    'accounts_create_asset_currency'        => 'Firelfly III mendukung berbagai mata uang. Akun aset memiliki satu mata uang utama, yang yang harus anda tetapkan.',
    'accounts_create_asset_virtual'         => 'Kadang-kadang itu dapat membantu memberi akun anda saldo virtual: jumlah tambahan yang selalu ditambahkan atau dihapus dari saldo sebenarnya.',

    // budgets index
    'budgets_index_intro'                   => 'Anggaran yang digunakan untuk mengelola keuangan anda dan membentuk salah satu fungsi inti dari Firefly III.',
    'budgets_index_set_budget'              => 'Set your total budget for every period so Firefly III can tell you if you have budgeted all available money.',
    'budgets_index_see_expenses_bar'        => 'Menghabiskan uang secara perlahan dengan mengisi di bar ini.',
    'budgets_index_navigate_periods'        => 'Menavigasi melalui periode dengan mudah menetapkan anggaraan sebelumnya.',
    'budgets_index_new_budget'              => 'Buat anggaran baru sesuai keinginan anda.',
    'budgets_index_list_of_budgets'         => 'Gunakan tabel ini untuk menetapkan jumlah setiap anggaran daan melihat bagaimana keadaan anda.',
    'budgets_index_outro'                   => 'Untuk mempelajari lebih lanjut tentang anggaran, periksa ikon bantuan di pojok kanan atas.',

    // reports (index)
    'reports_index_intro'                   => 'Gunakan laporan ini untuk mendapatkan wawasan terperinci dalam keuangan anda.',
    'reports_index_inputReportType'         => 'Pilih sebuah jenis laporan. Periksa bantuan halaman untuk melihat apa yang ditunjukkan pada laporan anda.',
    'reports_index_inputAccountsSelect'     => 'Anda dapat mengecualikan atau menyertakan akun aset sesuai keinginan anda.',
    'reports_index_inputDateRange'          => 'Rentang tanggal yang dipilih sepenuhnya terserah anda: dari satu hari sampai 10 tahun.',
    'reports_index_extra-options-box'       => 'Bergantung pada laporan yang anda pilih, anda dapat memilih filter dan opsi tambahan di sini. Lihat kotak ini saat anda mengubah jenis laporan.',

    // reports (reports)
    'reports_report_default_intro'          => 'Laporan ini akan memberi gambaran singkat tentang keuanggan anda secara cepat dan menyeluruh. Jika anda ingin melihat yang lain, jangan ragu untuk menghubungi saya!',
    'reports_report_audit_intro'            => 'Laporan ini memberikan anda pengetahuan rnci dalam akun aset anda.',
    'reports_report_audit_optionsBox'       => 'Gunakan kotak centang ini untuk menampilkan atau menyembunyikan kolom yang anda suka.',

    'reports_report_category_intro'                  => 'Laporan ini akan memberi anda pengetahuan pada satu atau beberapa kategori.',
    'reports_report_category_pieCharts'              => 'Bagan ini memberi anda pengetahuan tentang biaya dan pendapatan per kategori atau per akun.',
    'reports_report_category_incomeAndExpensesChart' => 'Bagan ini menunjukkan pengeluaran dan pendapatan anda per kategori.',

    'reports_report_tag_intro'                  => 'Laporan ini memberi anda pengetahuan dalam satu atau beberapa label.',
    'reports_report_tag_pieCharts'              => 'Bagan ini memberikan anda pengetahuan tentang biaya dan pendapatan per label, akun, kategori atau anggaran.',
    'reports_report_tag_incomeAndExpensesChart' => 'Bagan ini menunjukkan pengeluaran dan pendapatan anda per label.',

    'reports_report_budget_intro'                             => 'Laporan ini memberikan anda pengetahuan tentang satu atau beberapa anggaran.',
    'reports_report_budget_pieCharts'                         => 'Bagan ini memberikan anda pengetahuan tentang biaya per anggaran atau per akun.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Bagan ini menunjukkan pengeluaran anda per anggaran.',

    // create transaction
    'transactions_create_switch_box'                          => 'Gunakan tombol-tombol ini dengan cepat untuk mengganti jenis transaksi yang ingin anda simpan.',
    'transactions_create_ffInput_category'                    => 'Anda bisa dengan bebas mengetik di bidang ini. Kategori yang dibuat sebelumnya yang akan disarankan.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Tautkan penarikan anda ke anggaraan untuk mendapatkan kontrol keuangan yang lebih baik.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Gunakan dropdown ini saat penarikan anda dalam mata uang lainnya.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Gunakan dropdown ini saat deposit anda berada dalam mata uang lainnya.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Pilihlah celengan dan hubungkan transfer ini ke tabungan anda.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Bagian ini menunjukkan berapa banyak yang telah anda simpan di setiap celengan.',
    'piggy-banks_index_button'                                => 'Di samping bilah kemajuan ini ada dua tombol (+ dan -) untuk menanmbahkan atau menghapus uang dari masing-masing celengan.',
    'piggy-banks_index_accountStatus'                         => 'Untuk setiap akun aset dengan sedikitnya satu celenggan, statusnya terdaftar dalam tabel ini.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Apa tujuanmu? Sofa baru, kamera baru, uang untuk keadaan darurat?',
    'piggy-banks_create_date'                                 => 'Anda dapat menetapkan tanggal target atau tenggat waktu untuk celengan anda.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Bagan ini akan menunjukkan riwayat celengan.',
    'piggy-banks_show_piggyDetails'                           => 'Beberapa rincian tentang celengan anda',
    'piggy-banks_show_piggyEvents'                            => 'Setiap tambahan atau kepindahan juga tercantum di sini.',

    // bill index
    'bills_index_paid_in_period'                              => 'Bagian ini menunjukkan kapan tagihan terakhir dibayarkan.',
    'bills_index_expected_in_period'                          => 'Bagian ini menunjukkan setiap tagihan jika dan kapan tagihan berikutnya diperkirakan akan dibayar.',

    // show bill
    'bills_show_billInfo'                                     => 'Tabel ini menunjukkan beberapa informasi umum tentang tagihan ini.',
    'bills_show_billButtons'                                  => 'Gunakan tombol ini untuk memindai ulang transaksi lama sehingga cocok dengan tagihan ini.',
    'bills_show_billChart'                                    => 'Bagan ini menunjukkan transaksi yang terkait dengan tagihan ini.',

    // create bill
    'bills_create_name'                                       => 'Gunakan nama yang deskriptif seperti "Rent" atau "Health insurance".',
    'bills_create_match'                                      => 'Untuk mencocokan transaksi, gunakan persyaratan dari transaksi tersebut atau akun biaya yang terlibat. Semua kata harus sesuai.',
    'bills_create_amount_min_holder'                          => 'Pilih jumlah minimum dan maksimum untuk tagihan ini.',
    'bills_create_repeat_freq_holder'                         => 'Sebagian besar tagihan diulang setiap bulannya, tapi anda bisa mengatur frekuensi lain di sini.',
    'bills_create_skip_holder'                                => 'Jika tagihan berulang setiap 2 minggu misalnya, bagian "lewati" harus ditetapkan ke "1" untuk melewati setiap minggu lainnya.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III memungkinkan anda mengatur peraturan-peraturan, yang otomatis akan diterapkan pada transaksi yang anda buat atau edit.',
    'rules_index_new_rule_group'                              => 'Anda dapat menggabungkan aturan dalam kelompok untuk memudahkan pengelolaan.',
    'rules_index_new_rule'                                    => 'Buat aturan sebanyak yang anda suka.',
    'rules_index_prio_buttons'                                => 'Memesannya sesuai keinginan anda.',
    'rules_index_test_buttons'                                => 'Anda bisa menguji peraturan anda atau menerapkannya pada transaksi yang ada.',
    'rules_index_rule-triggers'                               => 'Aturan memiliki "pemicu" dan "tindakan" yang dapat anda pesan dengan drag-and-drop.',
    'rules_index_outro'                                       => 'Pastikan untuk periksa halaman bantuan menggunakan ikon (?) di bagian kanan atas!',

    // create rule:
    'rules_create_mandatory'                                  => 'Pilih judul deskriptif dan tentukan kapan aturan harus dijalankan.',
    'rules_create_ruletriggerholder'                          => 'Tambahkan sebanyak mungkin trigger sebanyak yang kamu sukai, tap ingat bahwa SEMUA trigger harus cocok sebelum tindakan dijalankan.',
    'rules_create_test_rule_triggers'                         => 'Gunakan tombol ini untuk melihat transaksi mana yang sesuai dengan peraturan anda.',
    'rules_create_actions'                                    => 'Tentukan sebanyak mungkin tindakan yang anda inginkan.',

    // preferences
    'preferences_index_tabs'                                  => 'Pilihan lainnya tersedia dibalik tab ini.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III mendukung banyak mata uang, yang dapat anda ubah di halaman ini.',
    'currencies_index_default'                                => 'Firefly III memiliki satu mata uang standar. Anda selalu dapat beralih ke mata uang lainnya dengan menggunakan tombol-tombol ini.',

    // create currency
    'currencies_create_code'                                  => 'Kode ini harus sesuai dengan ISO (Google untuk mata uang baru anda).',
];
