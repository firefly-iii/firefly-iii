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
    'greeting'                                => 'Halo',
    'closing'                                 => 'Bip bip,',
    'signature'                               => 'Robot pesan Firefly III',
    'footer_ps'                               => 'NB: Pesan ini dikirim karena ada permintaan dari IP :ipAddress: yang memicunya.',

    // admin test
    'admin_test_subject'                      => 'Sebuah pesan tes dari instalasi Firefly III Anda',
    'admin_test_body'                         => 'Ini adalah sebuah pesan tes dari instans Firefly III Anda. Pesan ini dikirim ke :email.',

    // new IP
    'login_from_new_ip'                       => 'Masuk baru pada Firefly III',
    'new_ip_body'                             => 'Firefly III mendeteksi adanya percobaan masuk baru pada akun Anda dari alamat IP yang tidak diketahui. Jika Anda tidak pernah masuk dari alamat IP di bawah, atau jika sudah lebih dari enam bulan lalu, Firefly III akan memperingatkan Anda.',
    'new_ip_warning'                          => 'Jika Anda mengenali alamat IP atau percobaan masuk ini, Anda dapat mengabaikan pesan ini. Jika Anda tidak masuk ke akun Anda, atau Anda tidak tahu arti pesan ini, ubah keamanan kata sandi Anda, dan keluar dari semua sesi lain. Untuk melakukan ini, masuk ke halaman profil Anda. Tentu saja Anda sudah memiliki otentikasi dua faktor, bukan? Tetaplah aman!',
    'ip_address'                              => 'Alamat IP',
    'host_name'                               => 'Tuan rumah',
    'date_time'                               => 'Tanggal + waktu',

    // access token created
    'access_token_created_subject'            => 'Token akses telah dibuat',
    'access_token_created_body'               => 'Seseorang (semoga Anda) baru saja membuat sebuah token akses API Firefly III pada akun pengguna Anda.',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Selamat Datang di Firefly III!',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'Ada ikon bantuan di pojok kanan atas di setiap halaman. Jika Anda membutuhkannya, klik ikonnya!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'Selamat menikmati!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Atur ulang kata sandi:',
    'registered_doc_link'                     => 'Dokumentasi:',

    // email change
    'email_change_subject'                    => 'Alamat surel Firefly III Anda telah diubah',
    'email_change_body_to_new'                => 'Anda atau seseorang dengan akses ke akun Firefly III Anda telah mengubah alamat surel Anda. Jika Anda tidak merasa Anda membutuhkan pesan ini, mohon abaikan dan hapus.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'Jika Anda yang melakukan perubahan, Anda dapat mengabaikan pesan ini.',
    'email_change_old'                        => 'Alamat surel yang lama adalah :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'Alamat surel yang baru adalah :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'Anda tidak dapat menggunakan Firefly III hingga Anda mengonfirmasi perubahan ini. Mohon ikuti tautan di bawah untuk melakukannya.',
    'email_change_undo_link'                  => 'Untuk membatalkan perubahan, ikuti tautan ini:',

    // OAuth token created
    'oauth_created_subject'                   => 'Klien OAuth telah dibuat',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'Permintaan atur ulang kata sandi Anda',
    'reset_pw_instructions'                   => 'Seseorang mencoba mengatur ulang kata sandi Anda. Jika itu adalah Anda, mohon ikuti tautan di bawah untuk melakukannya.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Mendapati kesalahan pada Firefly III',
    'error_intro'                             => 'Firefly III v:version mendapati kesalahan: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'Kesalahan bertipe ":class".',
    'error_timestamp'                         => 'Kesalahan terjadi pada: :time.',
    'error_location'                          => 'Kesalahan ini terjadi pada file "<span style="font-family: monospace;">:file</span>" pada baris :line dengan kode :code.',
    'error_user'                              => 'Kesalahan terjadi pada pengguna #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Tidak ada pengguna masuk untuk kesalahan ini atau tidak ada pengguna terdeteksi.',
    'error_ip'                                => 'Alamat IP yang berhubungan dengan kesalahan ini adalah: :ip',
    'error_url'                               => 'URL adalah: :url',
    'error_user_agent'                        => 'User agent: :userAgent',
    'error_stacktrace'                        => 'Jejak tumpukan lengkap ada di bawah. Jika Anda merasa ada kutu di Firefly III, Anda dapat meneruskan pesan ini ke <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. Hal ini dapat membantu memperbaiki kutu yang baru saja Anda alami.',
    'error_github_html'                       => 'Jika Anda mau, Anda juga dapat membuka isu baru di <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'Jika Anda mau, Anda juga dapat membuka isu baru di https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'Jejak tumpukan lengkap ada di bawah:',
    'error_headers'                           => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III telah membuat transaksi baru|Firefly III telah membuat :count transaksi baru',
    'new_journals_header'                     => 'Firefly III telah membuat transaksi untuk Anda. Anda dapat menemukannya di instalasi Firefly III Anda:|Firefly telah membuat :count transaksi untuk Anda. Anda dapat menemukannya di instalasi Firefly III Anda:',

    // bill warning
    'bill_warning_subject_end_date'           => 'Your bill ":name" is due to end in :diff days',
    'bill_warning_subject_now_end_date'       => 'Your bill ":name" is due to end TODAY',
    'bill_warning_subject_extension_date'     => 'Your bill ":name" is due to be extended or cancelled in :diff days',
    'bill_warning_subject_now_extension_date' => 'Your bill ":name" is due to be extended or cancelled TODAY',
    'bill_warning_end_date'                   => 'Your bill **":name"** is due to end on :date. This moment will pass in about **:diff days**.',
    'bill_warning_extension_date'             => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass in about **:diff days**.',
    'bill_warning_end_date_zero'              => 'Your bill **":name"** is due to end on :date. This moment will pass **TODAY!**',
    'bill_warning_extension_date_zero'        => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass **TODAY!**',
    'bill_warning_please_action'              => 'Please take the appropriate action.',

];
