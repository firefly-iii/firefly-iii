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
    'greeting'                                => 'Selam',
    'closing'                                 => 'Bip bop',
    'signature'                               => 'Firefly III Posta Robotu',
    'footer_ps'                               => 'Not: Bu ileti, IP:ıpaddress\'den gelen bir istek tetiklediği için gönderildi.',

    // admin test
    'admin_test_subject'                      => 'Firefly III kurulumunuzdan bir test mesajı',
    'admin_test_body'                         => 'Bu, Firefly III örneğinizden gelen bir test mesajıdır. Şu adrese gönderildi: e-posta.',

    // new IP
    'login_from_new_ip'                       => 'Firefly III yeni giriş',
    'new_ip_body'                             => 'Firefly III, hesabınızda bilinmeyen bir IP adresinden yeni bir giriş tespit etti. Aşağıdaki IP adresinden hiç giriş yapmadıysanız veya altı aydan daha uzun bir süre önce yapıldıysa, Firefly III sizi uyaracaktır.',
    'new_ip_warning'                          => 'Bu IP adresini veya oturum açmayı tanıyorsanız, bu iletiyi yoksayabilirsiniz. Eğer giriş eğer konuyla ilgili hiçbir fikriniz varsa, şifre güvenliğinizi doğrulamak, ve çıkış tüm oturumlar bu değişiklik olmadıysa. Bunu yapmak için profil sayfanıza gidin. Tabii ki zaten 2FA etkin, değil mi? Güvende kalın!',
    'ip_address'                              => 'IP adresi',
    'host_name'                               => 'Host',
    'date_time'                               => 'Tarih + saat',

    // access token created
    'access_token_created_subject'            => 'Yeni bir erişim belirteci oluşturuldu',
    'access_token_created_body'               => 'Birisi (umarız sensindir) hesabın için yeni bir Firefly III API Erişim Anahtarı oluşturdu.',
    'access_token_created_explanation'        => 'Bu belirteçle, Firefly III API\'sı aracılığıyla tüm finansal kayıtlarınıza erişebilirler.',
    'access_token_created_revoke'             => 'Bu siz değilseniz, lütfen bu belirteci mümkün olan en kısa sürede iptal edin :url',

    // registered
    'registered_subject'                      => 'Firefly III\'e hoşgeldiniz!',
    'registered_welcome'                      => '[Firefly III] \'e hoş geldiniz(:address). Kaydınız yapıldı ve bu e-posta onaylamak için burada. Yay!',
    'registered_pw'                           => 'Parolanızı zaten unuttuysanız, lütfen [parola sıfırlama aracı] (:adres/parola/sıfırla) kullanarak sıfırlayın.',
    'registered_help'                         => 'Her sayfanın sağ üst köşesinde bir yardım simgesi bulunur. Yardıma ihtiyacınız olursa, tıklayın!',
    'registered_doc_html'                     => 'Henüz yapmadıysanız, lütfen [büyük teori] \'yi okuyun (https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'Henüz yapmadıysanız, lütfen ilk kullanım kılavuzunu ve tam açıklamayı da okuyun.',
    'registered_closing'                      => 'Tadını çıkarın!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Şifre sıfırlama:',
    'registered_doc_link'                     => 'Belge:',

    // email change
    'email_change_subject'                    => 'Firefly III e-posta adresiniz değişti',
    'email_change_body_to_new'                => 'Siz veya Firefly III hesabınıza erişimi olan biri e-posta adresinizi değiştirdi. Bu iletiyi beklemediyseniz, lütfen yoksayın ve silin.',
    'email_change_body_to_old'                => 'Siz veya Firefly III hesabınıza erişimi olan biri e-posta adresinizi değiştirdi. Bunun olmasını beklemediyseniz, hesabınızı korumak için aşağıdaki "geri al" bağlantısını takip etmeniz gerekir!',
    'email_change_ignore'                     => 'Bu değişikliği başlattıysanız, bu iletiyi güvenle yoksayabilirsiniz.',
    'email_change_old'                        => 'Önceki e-posta adresi: :email',
    'email_change_old_strong'                 => 'Eski e-posta adresi: **: e-posta**',
    'email_change_new'                        => 'Yeni e-posta adresi: :email',
    'email_change_new_strong'                 => 'Yeni e-posta adresi: **: e-posta**',
    'email_change_instructions'               => 'Bu değişikliği onaylayana kadar Firefly Iıı\'ü kullanamazsınız. Lütfen bunu yapmak için aşağıdaki bağlantıyı takip edin.',
    'email_change_undo_link'                  => 'Değişikliği geri almak için bu bağlantıyı takip edin:',

    // OAuth token created
    'oauth_created_subject'                   => 'Yeni bir OAuth istemcisi oluşturuldu',
    'oauth_created_body'                      => 'Birisi (umarım siz) kullanıcı hesabınız için yeni bir Firefly III API OAuth İstemcisi oluşturmuştur. ":name" etiketli ve `:url\' geri arama URL\'sine sahip.',
    'oauth_created_explanation'               => 'Bu müşteri ile Firefly III API\'sı aracılığıyla **tüm ** finansal kayıtlarınıza erişebilirler.',
    'oauth_created_undo'                      => 'Bu siz değilseniz, lütfen bu istemciyi mümkün olan en kısa sürede `:url\' adresinden iptal edin',

    // reset password
    'reset_pw_subject'                        => 'Parola sıfırlama isteğin',
    'reset_pw_instructions'                   => 'Birisi şifrenizi sıfırlamaya çalıştı. Siz olsaydınız, bunu yapmak için lütfen aşağıdaki bağlantıyı takip edin.',
    'reset_pw_warning'                        => '** LÜTFEN ** bağlantının gerçekten gitmesini beklediğiniz Firefly III\'e gittiğinden emin olun!',

    // error
    'error_subject'                           => 'Firefly III\'te bir hata yakalandı',
    'error_intro'                             => 'Firefly III v: sürüm bir hatayla karşılaştı: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'Hata ":class türündeydi.',
    'error_timestamp'                         => 'Hata açık / kapalı olarak oluştu: :time.',
    'error_location'                          => 'Bu hata dosyada oluştu "<span style="font-family: monospace;">:file</span>" on line :line with code :code.',
    'error_user'                              => 'Hatayla kullanıcı karşılaştı #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Bu hata için kullanıcı oturum açmadı veya kullanıcı algılanmadı.',
    'error_ip'                                => 'Bu hatayla ilgili IP adresi: :ip',
    'error_url'                               => 'Link: :url',
    'error_user_agent'                        => 'User agent: :userAgent',
    'error_stacktrace'                        => 'Tam stacktrace aşağıdadır. Bunun Firefly III\'TE bir hata olduğunu düşünüyorsanız, bu iletiyi şu adrese iletebilirsiniz: <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. Bu, az önce karşılaştığınız hatayı düzeltmenize yardımcı olabilir.',
    'error_github_html'                       => 'İsterseniz, yeni bir sayı da açabilirsiniz <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'İsterseniz, yeni bir sayı da açabilirsiniz https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'Tam stacktrace aşağıdadır:',
    'error_headers'                           => 'Aşağıdaki başlıklar da alakalı olabilir:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III yeni bir işlem yarattı / Firefly III yarattı :count yeni işlemler',
    'new_journals_header'                     => 'Firefly III sizin için bir anlaşma yaptı. Firefly III kurulumunuzda bulabilirsiniz: / Firefly III sizin için :count sayım işlemleri. Bunları Firefly III kurulumunuzda bulabilirsiniz:',

    // bill warning
    'bill_warning_subject_end_date'           => 'Faturanız ":name" is due to end in :diff days',
    'bill_warning_subject_now_end_date'       => 'Faturanız ":name" BUGÜN sona erecek',
    'bill_warning_subject_extension_date'     => 'Faturanız ":name" farklı :diff günlerde uzatılacak veya iptal edilecektir',
    'bill_warning_subject_now_extension_date' => 'Faturanız ":name" BUGÜN uzatılacak veya iptal edilecek',
    'bill_warning_end_date'                   => 'Faturanız ** ":name"** tarihinde sona ermelidir :date. Bu an yaklaşık **:diff** içinde geçecek.',
    'bill_warning_extension_date'             => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass in about **:diff days**.',
    'bill_warning_end_date_zero'              => 'Your bill **":name"** is due to end on :date. This moment will pass **TODAY!**',
    'bill_warning_extension_date_zero'        => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass **TODAY!**',
    'bill_warning_please_action'              => 'Please take the appropriate action.',

];
