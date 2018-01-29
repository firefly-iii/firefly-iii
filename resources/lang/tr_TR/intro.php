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
    'index_intro'                           => 'Firefly III indeks sayfasına hoşgeldiniz. Firefly III\'nin nasıl çalıştığını öğrenmek için lütfen bu tanıtımı izleyin.',
    'index_accounts-chart'                  => 'Bu grafik, varlık hesaplarınızın geçerli bakiyesini gösterir. Burada görünen hesapları tercihlerinizde seçebilirsiniz.',
    'index_box_out_holder'                  => 'Bu küçük kutu ve bunun yanındaki kutular size finansal durumunuza hızlı bir bakış sunar.',
    'index_help'                            => 'Bir sayfa veya formla ilgili yardıma ihtiyacınız varsa, bu düğmeye basın.',
    'index_outro'                           => 'Firefly III\'ün çoğu sayfası bunun gibi küçük bir turla başlayacak. Sorularınız ve yorumlarınız olursa lütfen benimle iletişime geçin. Keyfini çıkarın!',
    'index_sidebar-toggle'                  => 'Yeni işlemler, hesaplar veya başka şeyler oluşturmak için bu simgenin altındaki menüyü kullanın.',

    // create account:
    'accounts_create_iban'                  => 'Hesaplarınıza geçerli IBAN girin. Bu, ileride veri aktarma işlemini kolaylaştırabilir.',
    'accounts_create_asset_opening_balance' => 'Assets accounts may have an "opening balance", indicating the start of this account\'s history in Firefly III.',
    'accounts_create_asset_currency'        => 'Firefly III, birden fazla para birimini destekliyor. Varlık hesaplarının bir ana para birimi var, burada ayarlamanız gerekir.',
    'accounts_create_asset_virtual'         => 'Bazen hesabınıza sanal bir bakiye sağlamanıza yardımcı olabilir: ek bir miktar her zaman gerçek bakiyeye eklenir veya gerçek bakiyeden çıkarılır.',

    // budgets index
    'budgets_index_intro'                   => 'Bütçeler, finansmanınızı yönetmek ve Firefly III\'nin temel işlevlerinden birini oluşturmak için kullanılır.',
    'budgets_index_set_budget'              => 'Set your total budget for every period so Firefly III can tell you if you have budgeted all available money.',
    'budgets_index_see_expenses_bar'        => 'Para harcamak yavaşça bu çubuğu dolduracaktır.',
    'budgets_index_navigate_periods'        => 'Bütçeleri önceden kolayca ayarlamak için dönemleri gezinin.',
    'budgets_index_new_budget'              => 'Uygun gördüğünüz yeni bütçeler oluşturun.',
    'budgets_index_list_of_budgets'         => 'Her bütçe için tutarları ayarlamak ve ne durumda olduğunuzu görmek için bu tabloyu kullanın.',
    'budgets_index_outro'                   => 'Bütçeleme hakkında daha fazla bilgi almak için sağ üst köşedeki yardım simgesini kontrol edin.',

    // reports (index)
    'reports_index_intro'                   => 'Maliyetlerinizde ayrıntılı bilgi edinmek için bu raporları kullanın.',
    'reports_index_inputReportType'         => 'Bir rapor türü seçin. Her bir raporun neyi gösterdiğini görmek için yardım sayfalarına göz atın.',
    'reports_index_inputAccountsSelect'     => 'Varlık hesaplarını uygun gördüğünüz gibi hariç tutabilir veya ekleyebilirsiniz.',
    'reports_index_inputDateRange'          => 'Seçilen tarih aralığı tamamen size kalmış: 1 günden 10 yıla kadar.',
    'reports_index_extra-options-box'       => 'Seçtiğiniz rapora bağlı olarak, burada ekstra filtre ve seçenekleri belirleyebilirsiniz. Rapor türlerini değiştirirken bu kutuya dikkat edin.',

    // reports (reports)
    'reports_report_default_intro'          => 'Bu rapor size mali durumunuz hakkında hızlı ve kapsamlı bir bilgi verecektir. Başka bir şey görmek isterseniz, lütfen benimle iletişime geçmekten çekinmeyin!',
    'reports_report_audit_intro'            => 'Bu rapor size aktif hesaplarınızla ilgili ayrıntılı bilgiler verecektir.',
    'reports_report_audit_optionsBox'       => 'İlgilendiğiniz sütunları göstermek veya gizlemek için bu onay kutularını kullanın.',

    'reports_report_category_intro'                  => 'Bu rapor size bir veya birden fazla kategoride fikir verecektir.',
    'reports_report_category_pieCharts'              => 'Bu grafikler, size her bir kategori veya hesaptaki gelir ve giderler konusunda fikir verecektir.',
    'reports_report_category_incomeAndExpensesChart' => 'Bu grafik her bir kategori için gelir ve giderlerinizi gösterir.',

    'reports_report_tag_intro'                  => 'Bu rapor size bir veya birden fazla etikette fikir verecektir.',
    'reports_report_tag_pieCharts'              => 'Bu grafikler, her bir etiket, hesap, kategori veya bütçe için gelir ve giderler konusunda size fikir verecektir.',
    'reports_report_tag_incomeAndExpensesChart' => 'Bu grafik her bir etiket için gelir ve giderlerinizi gösterir.',

    'reports_report_budget_intro'                             => 'Bu rapor size bir veya birden fazla bütçede fikir verecektir.',
    'reports_report_budget_pieCharts'                         => 'Bu grafikler her bir bütçe veya hesaptaki giderler konusunda size fikir verecektir.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Bu grafik her bir bütçe için giderlerinizi gösterir.',

    // create transaction
    'transactions_create_switch_box'                          => 'Kaydetmek istediğiniz işlem tipini hızlıca değiştirmek için bu düğmeleri kullanın.',
    'transactions_create_ffInput_category'                    => 'Bu alana istediğiniz şekilde yazabilirsiniz. Daha önceden oluşturulan kategoriler önerilir.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Daha iyi mali kontrol için para çekme işleminizi bir bütçeyle ilişkilendirin.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Para çekme işleminiz başka bir para biriminde olduğunda bu açılan listeyi kullanın.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Para yatırma işleminiz başka bir bir para biriminde olduğunda bu açılır listeyi kullanın.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Bir kumbara seçin ve bu transferi birikimlerinize ilişkilendirin.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Bu alan size her bir kumbarada ne kadar biriktirdiğinizi gösterir.',
    'piggy-banks_index_button'                                => 'Her bir kumbaraya para eklemek veya çıkarmak için bu ilerleme çubuğunun yanında iki düğme (+ ve -) bulunur.',
    'piggy-banks_index_accountStatus'                         => 'En az bir kumbarası olan her bir aktif hesap için durum bu tabloda listelenir.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Hedefin nedir? Yeni bir kanepe, bir kamera ya da acil durumlar için para mı?',
    'piggy-banks_create_date'                                 => 'Kumbaranız için bir hedef tarih ya da bitiş tarihi belirleyebilirsiniz.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Bu tablo, bu kumbaranın geçmişini gösterecektir.',
    'piggy-banks_show_piggyDetails'                           => 'Kumbaranız hakkında bazı bilgiler',
    'piggy-banks_show_piggyEvents'                            => 'Herhangi bir ekleme veya çıkarma işlemi de burada listelenmektedir.',

    // bill index
    'bills_index_paid_in_period'                              => 'Bu alan faturanın en son ne zaman ödendiğini gösterir.',
    'bills_index_expected_in_period'                          => 'Bu alan, her fatura için, eğer ödenmesi gerekiyorsa bir sonraki faturanın ne zaman ödeneceğini gösterir.',

    // show bill
    'bills_show_billInfo'                                     => 'Bu tablo, bu fatura hakkında bazı genel bilgiler gösterir.',
    'bills_show_billButtons'                                  => 'Eski işlemleri tekrar taramak için bu düğmeyi kullanın böylelikle bu fatura ile eşleşeceklerdir.',
    'bills_show_billChart'                                    => 'Bu tablo, bu faturaya ilişkilendirilmiş işlemleri gösterir.',

    // create bill
    'bills_create_name'                                       => '"Kira" veya "Sağlık sigortası" gibi açıklayıcı bir isim kullanın.',
    'bills_create_match'                                      => 'İşlemleri eşleştirmek için, bu işlemlerden veya ilgili gider hesabından gelen terimleri kullanın. Tüm kelimeler eşleşmelidir.',
    'bills_create_amount_min_holder'                          => 'Bu fatura için minimum ve maksimum bir tutar seçin.',
    'bills_create_repeat_freq_holder'                         => 'Birçok fatura aylık yinelenir, fakat burada başka bir sıklık ayarlayabilirsiniz.',
    'bills_create_skip_holder'                                => 'Örneğin bir fatura her 2 haftada yineleniyorsa, "atla" alanı iki haftada bir atlaması için "1" olarak ayarlanmalıdır.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III, oluşturduğunuz veya düzenlediğiniz herhangi bir işleme otomatik olarak uygulanacak olan kuralları yönetmenize olanak verir.',
    'rules_index_new_rule_group'                              => 'Daha kolay yönetim için kuralları gruplar halinde bir araya getirebilirsiniz.',
    'rules_index_new_rule'                                    => 'İstediğiniz kadar kural oluşturun.',
    'rules_index_prio_buttons'                                => 'Onları uygun gördüğün herhangi bir şekilde sipariş edin.',
    'rules_index_test_buttons'                                => 'Kurallarınızı test edebilir veya onları mevcut işlemlere uygulayabilirsiniz.',
    'rules_index_rule-triggers'                               => 'Kuralların, sürükleyip bırakarak sıralayabileceğiniz "tetikleyicileri" ve "eylemleri" vardır.',
    'rules_index_outro'                                       => 'Sağ üstteki (?) Simgesini kullanarak yardım sayfalarını kontrol ettiğinizden emin olun!',

    // create rule:
    'rules_create_mandatory'                                  => 'Açıklayıcı bir başlık seçin ve kuralın ne zaman harekete geçeceğini ayarlayın.',
    'rules_create_ruletriggerholder'                          => 'İstediğiniz kadar çok tetikleyici ekleyin, fakat herhangi bir eylem harekete geçmeden önce TÜM tetikleyicilerin eşleşmesi gerektiğini unutmayın.',
    'rules_create_test_rule_triggers'                         => 'Hangi işlemlerin kurallarınıza uyacağını görmek için bu tuşu kullanın.',
    'rules_create_actions'                                    => 'İstediğiniz kadar eylem belirleyin.',

    // preferences
    'preferences_index_tabs'                                  => 'Bu sekmelerin arkasında daha fazla seçenek bulunmaktadır.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III, bu sayfada değiştirebileceğiniz birden fazla para birimini destekliyor.',
    'currencies_index_default'                                => 'Firefly III bir varsayılan para birimine sahiptir. Tabi ki istediğiniz zaman bu düğmeleri kullanarak değiştirebilirsiniz.',

    // create currency
    'currencies_create_code'                                  => 'Bu kod ISO uyumlu olmalıdır (Yeni para biriminiz için Google\'da arayın).',
];
