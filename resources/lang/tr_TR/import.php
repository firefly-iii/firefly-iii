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
    'status_wait_title'                    => 'Lütfen bekleyin...',
    'status_wait_text'                     => 'Bu kutu bir dakika içinde kaybolacak.',
    'status_fatal_title'                   => 'Önemli bir hata oluştu',
    'status_fatal_text'                    => 'İçe aktarma rutininin kurtaramadığı önemli bir hata oluştu. Lütfen aşağıdaki kırmızı renkli açıklamaları okuyun.',
    'status_fatal_more'                    => 'Eğer hata zaman aşımı ise, içe aktarma yarısında durdurulur. Bazı sunucu ayarlarında sadece sunucu durdurulurken içe aktarım arka planda devam eder. Bunu sağlamak için kayıt dosyalarını kontrol edin. Eğer sorun devam ederse komut satırı üzerinden içe aktarımı deneyin.',
    'status_ready_title'                   => 'İçe aktarım başlamaya hazır',
    'status_ready_text'                    => 'İçe aktarım başlamaya hazır. Yapmanız gereken tüm ayarlar yapıldı. Lütfen ayar dosyasını indirin. İçe aktarım planlandığı gibi gitmezse size yardım edecektir. İçe aktarımı başlatmak için takip eden komutu konsolunuza girebilir ya da web tabanlı içe aktarımı kullanabilirsiniz. Ayarlarınıza bağlı olarak göre konsol içe aktarımı size daha fazla geri bildirim verecektir.',
    'status_ready_noconfig_text'           => 'İçe aktarım başlamaya hazır. Yapmanız gereken tüm ayarlar yapıldı. İçe aktarımı başlatmak için takip eden komutu konsolunuza girebilir ya da web tabanlı içe aktarımı kullanabilirsiniz. Ayarlarınıza bağlı olarak göre konsol içe aktarımı size daha fazla geri bildirim verecektir.',
    'status_ready_config'                  => 'Yapılandırmayı indir',
    'status_ready_start'                   => 'İçe aktarmayı başlat',
    'status_ready_share'                   => 'Lütfen ayarlarınızı indirmeyi ve onu <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">içe aktarım ayarları merkezinde</a></strong> paylaşmayı düşünün. Bu diğer kullanıcılarının Firefly III\'ün dosyalarını daha kolay içe aktarmasına olanak tanır.',
    'status_job_new'                       => 'Yeni iş.',
    'status_job_configuring'               => 'İçe aktarım ayarlanıyor.',
    'status_job_configured'                => 'İçe aktarım ayarlandı.',
    'status_job_running'                   => 'Alma işlemi çalışıyor... Lütfen bekleyin..',
    'status_job_error'                     => 'İş bir hata üretti.',
    'status_job_finished'                  => 'Alma işlemi tamamlandı!',
    'status_running_title'                 => 'İçe aktarma işlemi sürüyor',
    'status_running_placeholder'           => 'Güncelleme için lütfen bekleyin...',
    'status_finished_title'                => 'İçe aktarma rutini tamamlandı',
    'status_finished_text'                 => 'İçe aktarma rutini verilerinizi içe aktardı.',
    'status_errors_title'                  => 'İçe aktarım sırasında hata',
    'status_errors_single'                 => 'İçe aktarım sırasında bir hata oluştu. Önemli gibi görünmüyor.',
    'status_errors_multi'                  => 'İçe aktarım sırasında hatalar oluştu. Önemli gibi görünmüyorlar.',
    'status_bread_crumb'                   => 'Aktarma durumu',
    'status_sub_title'                     => 'Aktarma durumu',
    'config_sub_title'                     => 'Hesabınızı oluşturunuz',
    'status_finished_job'                  => 'The :count transactions imported can be found in tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III has not collected any journals from your import file.',
    'import_with_key'                      => '\':key\' ile içe aktarın',

    // file, upload something
    'file_upload_title'                    => 'Ayarları aktar (1/4) - Dosyalarınızı yükelyin',
    'file_upload_text'                     => 'Bu yöntem dosyalarınızı bankanızdan Firefly III\'e aktarmanıza yardımcı olur. Lütfen sağ üst köşedeki yardımı kontrol edin.',
    'file_upload_fields'                   => 'Alanlar',
    'file_upload_help'                     => 'Dosyanızı seçin',
    'file_upload_config_help'              => 'Eğer Firefly III\'e daha önce veri aktardıysanız, ayarları sizin için önceden ayarlayacak bir ayar dosyasına sahip olabilirsiniz. Diğer kullanıcılar baı bankalar için kendi <a href="https://github.com/firefly-iii/import-configurations/wiki">ayar dosyalarını</a> sağlayabilirler',
    'file_upload_type_help'                => 'Yükleyeceğiniz dosya türünü seçin',
    'file_upload_submit'                   => 'Dosyaları yükle',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (virgülle ayrılmış değerler)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Ayarları aktar (2/4) - Temel CSV aktarım ayarları',
    'csv_initial_text'                     => 'Dosyanızı doğru bir şekilde içe aktarabilmek için lütfen aşağıdaki seçenekleri doğrulayın.',
    'csv_initial_box'                      => 'Temel CSV aktarım ayarları',
    'csv_initial_box_title'                => 'Temel CSV aktarım ayarları seçenekleri',
    'csv_initial_header_help'              => 'CSV dosyanızın ilk satırları sütun başlıklarıysa bu kutuyu işaretleyin.',
    'csv_initial_date_help'                => 'CSV dosyanızda ki zaman biçimi. <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">Bu sayfanın</a> gösterdiği biçimi kontrol takip edin. Varsayılan değer şu şekilde görülen tarihleri ayrıştırır: :dateExample.',
    'csv_initial_delimiter_help'           => 'Gir dosyanızda kullanılan alan sınırlayıcıyı seçin. Emin değilseniz, virgül en güvenli seçenektir.',
    'csv_initial_import_account_help'      => 'Eğer CSV dosyanız aktif hesabınızla ilgili bilgi içermiyorsa, CSV\'de bulunan işlemlerin hangi hesaba ait olduğunu bu açılan kutudan seçiniz.',
    'csv_initial_submit'                   => '3/4 adım ile devam et',

    // file, new options:
    'file_apply_rules_title'               => 'Kuralları uygula',
    'file_apply_rules_description'         => 'Kurallarınızı kabul ediniz. Bunun önemli ölçüde içe aktarmayı yavaşlattığını unutmayın.',
    'file_match_bills_title'               => 'Faturaları eşleştirin',
    'file_match_bills_description'         => 'Faturalarınızı yeni oluşturulan çekimlerle eşleştirin. Bunun önemli ölçüde içe aktarmayı yavaşlatacağını unutmayın.',

    // file, roles config
    'csv_roles_title'                      => 'Ayarları aktar (3/4) - Her sütunun görevini belirleyin',
    'csv_roles_text'                       => 'CSV dosyanızdaki her sütun belirli verileri içerir. Lütfen aktarıcının ne tür bir veri beklemesi gerektiğini belirtin. Verileri "planla" seçeneği sütunda bulunan her girdinin veri tabanınınızdaki bir değer ile bağlantılanması anlamına gelir. Genellikle planlanan sütun karşı hesabın IBAN numarasının olduğu sütundur. Bu veri tabanınızda bulunan IBAN\'larla kolayca eşleştirilebilir.',
    'csv_roles_table'                      => 'Tablo',
    'csv_roles_column_name'                => 'Sütun adı',
    'csv_roles_column_example'             => 'Sütun örneği verileri',
    'csv_roles_column_role'                => 'Sütun veri ortalaması',
    'csv_roles_do_map_value'               => 'Değerlerin haritası',
    'csv_roles_column'                     => 'Sütun',
    'csv_roles_no_example_data'            => 'Örnek veri yok',
    'csv_roles_submit'                     => '4/4 adım ile devam et',

    // not csv, but normal warning
    'roles_warning'                        => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'foreign_amount_warning'               => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    // file, map data
    'file_map_title'                       => 'Ayarları aktar (4/4) - İçe aktarım verilerini Firefly III verilerine bağlayın',
    'file_map_text'                        => 'Takip eden tabloda, sol değer yüklediğiniz dosyada bulunan bilgileri gösterir. Bu değeri eşlemek sizin göreviniz, eğer mümkünse veritabanınızda bulunan bir değerle. Firefly bu eşlemeye bağlı kalacak. Eğer eşleştirilecek değer yoksa ya da belirli bir değer ile eşleştirmek istemiyorsanız hiçbir şey seçmeyin.',
    'file_map_field_value'                 => 'Alan değeri',
    'file_map_field_mapped_to'             => 'Eşleşti',
    'map_do_not_map'                       => '(eşleştirme)',
    'file_map_submit'                      => 'İçe aktarmaya başla',
    'file_nothing_to_map'                  => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',

    // map things.
    'column__ignore'                       => '(bu sütünu yok say)',
    'column_account-iban'                  => 'Öğe hesabı (IBAN)',
    'column_account-id'                    => 'Asset account ID (matching FF3)',
    'column_account-name'                  => 'Varlık hesabı (isim)',
    'column_amount'                        => 'Tutar',
    'column_amount_foreign'                => 'Amount (in foreign currency)',
    'column_amount_debit'                  => 'Miktar (borç sütunu)',
    'column_amount_credit'                 => 'Miktar (kredi sütunu)',
    'column_amount-comma-separated'        => 'Miktar (virgül ondalık ayırıcı olarak)',
    'column_bill-id'                       => 'Bill ID (matching FF3)',
    'column_bill-name'                     => 'Fatura adı',
    'column_budget-id'                     => 'Budget ID (matching FF3)',
    'column_budget-name'                   => 'Bütçe adı',
    'column_category-id'                   => 'Category ID (matching FF3)',
    'column_category-name'                 => 'Kategori adı',
    'column_currency-code'                 => 'Para birimi kodu (ISO 4217)',
    'column_foreign-currency-code'         => 'Foreign currency code (ISO 4217)',
    'column_currency-id'                   => 'Currency ID (matching FF3)',
    'column_currency-name'                 => 'Currency name (matching FF3)',
    'column_currency-symbol'               => 'Currency symbol (matching FF3)',
    'column_date-interest'                 => 'Faiz hesaplama tarihi',
    'column_date-book'                     => 'İşlem rezervasyon tarihi',
    'column_date-process'                  => 'İşlem tarihi',
    'column_date-transaction'              => 'Tarih',
    'column_description'                   => 'Açıklama',
    'column_opposing-iban'                 => 'Karşı hesap (IBAN)',
    'column_opposing-id'                   => 'Opposing account ID (matching FF3)',
    'column_external-id'                   => 'Harici Kimlik',
    'column_opposing-name'                 => 'Karşı hesap (isim)',
    'column_rabo-debit-credit'             => 'Rabobank\'a özel borç / kredi göstergesi',
    'column_ing-debit-credit'              => 'ING\'ye özel borç/kredi göstergesi',
    'column_sepa-ct-id'                    => 'SEPA Kredi Transferinin uçtan uca kimliği',
    'column_sepa-ct-op'                    => 'SEPA Kredi Transferinin karşı hesabı',
    'column_sepa-db'                       => 'SEPA Direkt Borç',
    'column_tags-comma'                    => 'Etiketler (virgülle ayrılmış)',
    'column_tags-space'                    => 'Etiketler (boşlukla ayrılmış)',
    'column_account-number'                => 'Varlık hesabı (hesap numarası)',
    'column_opposing-number'               => 'Karşı hesap (hesap numarası)',
    'column_note'                          => 'Not(lar)',

    // prerequisites
    'prerequisites'                        => 'Prerequisites',

    // bunq
    'bunq_prerequisites_title'             => 'Bunq\'dan içeri aktarım için şartlar',
    'bunq_prerequisites_text'              => 'Bunq\'dan içe aktarabilmek için bir API anahtarı almalısınız. Bunu uygulamadan yapabilirsiniz.',

    // Spectre
    'spectre_title'                        => 'Spectre kullanarak içe aktar',
    'spectre_prerequisites_title'          => 'Spectre kullanarak içe aktarma için ön koşullar',
    'spectre_prerequisites_text'           => 'In order to import data using the Spectre API, you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'                => 'Alma işlemi sadece bu ortak anahtarı <a href="https://www.saltedge.com/clients/security/edit">güvenlik sayfası</a>na girdiğinizde çalışacaktır.',
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

