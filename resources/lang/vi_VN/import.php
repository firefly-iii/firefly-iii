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
    'index_breadcrumb'                    => 'Nhập dữ liệu vào Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Điều kiện tiên quyết cho người cung cấp nhập giả',
    'prerequisites_breadcrumb_spectre'    => 'Điều kiện tiên quyết cho Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Điều kiện tiên quyết cho bunq',
    'prerequisites_breadcrumb_ynab'       => 'Điều kiện tiên quyết cho YNAB',
    'job_configuration_breadcrumb'        => 'Cấu hình cho ":key"',
    'job_status_breadcrumb'               => 'Trạng thái nhập cho ":key"',
    'disabled_for_demo_user'              => 'bị vô hiệu hóa trong bản demo',

    // index page:
    'general_index_intro'                 => 'Chào mừng bạn đến với nhập dữ liệu cho Firefly III. Có một số cách nhập dữ liệu vào Firefly III, được hiển thị ở đây dưới dạng các nút.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Như đã nêu trong <a href="https://www.patreon.com/posts/future-updates-30012174">bài đăng Patreon này</a>, cách Firefly III quản lý nhập dữ liệu sẽ thay đổi. Điều đó có nghĩa là công cụ nhập CSV sẽ được chuyển sang một công cụ mới, riêng biệt. Bạn đã có thể sử dụng bản beta này nếu bạn truy cập <a href="https://github.com/firefly-iii/csv-importer">kho GitHub này</a>.',
    'final_csv_import'     => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that this is the last version of Firefly III that will feature a CSV importer. A separated tool is available that you should try for yourself: <a href="https://github.com/firefly-iii/csv-importer">the Firefly III CSV importer</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Giả mạo nhập',
    'button_file'                         => 'Nhập một tập tin',
    'button_bunq'                         => 'Nhập từ bunq',
    'button_spectre'                      => 'Nhập bằng Spectre',
    'button_plaid'                        => 'Nhập bằng Plaid',
    'button_yodlee'                       => 'Nhập bằng Yodlee',
    'button_quovo'                        => 'Nhập bằng Quovo',
    'button_ynab'                         => 'Nhập từ You Need A Budget',
    'button_fints'                        => 'Nhập bằng FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Điều kiện tiên quyết nhập',
    'need_prereq_intro'                   => 'Một số phương pháp nhập cần sự cho phép của bạn trước khi chúng có thể được sử dụng. Ví dụ: họ có thể yêu cầu khóa API đặc biệt hoặc mật khẩu ứng dụng. Bạn có thể cấu hình chúng ở đây. Biểu tượng cho biết nếu những điều kiện tiên quyết này đã được đáp ứng.',
    'do_prereq_fake'                      => 'Điều kiện tiên quyết cho nhà cung cấp giả',
    'do_prereq_file'                      => 'Điều kiện tiên quyết để nhập tệp',
    'do_prereq_bunq'                      => 'Điều kiện tiên quyết để nhập từ bunq',
    'do_prereq_spectre'                   => 'Điều kiện tiên quyết để nhập bằng cách sử dụng Spectre',
    'do_prereq_plaid'                     => 'Điều kiện tiên quyết để nhập bằng cách sử dụng Plaid',
    'do_prereq_yodlee'                    => 'PĐiều kiện tiên quyết để nhập bằng cách sử dụng Yodlee',
    'do_prereq_quovo'                     => 'Điều kiện tiên quyết để nhập bằng cách sử dụng Quovo',
    'do_prereq_ynab'                      => 'Điều kiện tiên quyết để nhập từ YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Điều kiện tiên quyết để nhập từ nhà cung cấp nhập giả',
    'prereq_fake_text'                    => 'Nhà cung cấp giả mạo này yêu cầu khóa API giả. Nó phải dài 32 ký tự. Bạn có thể sử dụng cái này: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Điều kiện tiên quyết để nhập bằng API Spectre',
    'prereq_spectre_text'                 => 'Để nhập dữ liệu bằng API Spectre (v4), bạn phải cung cấp cho Firefly III hai giá trị bí mật. Tìm nó trên trên <a href="https://www.saltedge.com/clients/profile/secrets">trang bí mật</a>.',
    'prereq_spectre_pub'                  => 'Tương tự, API Spectre cần biết khóa công khai mà bạn thấy bên dưới. Không có nó, nó sẽ không nhận ra bạn. Vui lòng nhập khóa công khai này vào <a href="https://www.saltedge.com/clients/profile/secrets">trang bí mật</a>.',
    'prereq_bunq_title'                   => 'Điều kiện tiên quyết để nhập từ bunq',
    'prereq_bunq_text'                    => 'Để nhập từ bunq, bạn cần lấy khóa API. Bạn có thể làm điều này thông qua các ứng dụng. Xin lưu ý rằng chức năng nhập cho bunq là trong BETA. Nó chỉ được thử nghiệm nội bộ.',
    'prereq_bunq_ip'                      => 'bunq yêu cầu địa chỉ IP của bạn. Firefly III đã cố gắng lấy bằng cách sử dụng <a href="https://www.ipify.org/">dịch vụ ipify</a>. Đảm bảo địa chỉ IP này là chính xác, nếu không việc nhập sẽ thất bại.',
    'prereq_ynab_title'                   => 'Điều kiện tiên quyết để nhập từ YNAB',
    'prereq_ynab_text'                    => 'Để có thể tải xuống các giao dịch từ YNAB, vui lòng tạo một ứng dụng mới trên <a href="https://app.youneedabudget.com/settings/developer">Trang cài đặt dành cho nhà phát triển</a> và nhập ID khách hàng và bí mật trên trang này.',
    'prereq_ynab_redirect'                => 'Để hoàn tất cấu hình, nhập URL sau tại <a href="https://app.youneedabudget.com/settings/developer">Trang cài đặt dành cho nhà phát triển</a> phía dưới cái "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III đã phát hiện URI gọi lại sau đây. Có vẻ như máy chủ của bạn không được thiết lập để chấp nhận kết nối TLS (https). YNAB sẽ không chấp nhận URI này. Bạn có thể tiếp tục nhập (vì Firefly III có thể sai) nhưng vui lòng ghi nhớ điều này.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Khóa API giả được lưu trữ thành công!',
    'prerequisites_saved_for_spectre'     => 'ID ứng dụng và bí mật được lưu trữ!',
    'prerequisites_saved_for_bunq'        => 'Khóa API và IP được lưu trữ!',
    'prerequisites_saved_for_ynab'        => 'ID khách hàng YNAB và bí mật được lưu trữ!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Cấu hình công việc - áp dụng quy tắc của bạn?',
    'job_config_apply_rules_text'         => 'Khi nhà cung cấp giả mạo đã chạy, quy tắc của bạn có thể được áp dụng cho các giao dịch. Điều này thêm thời gian để nhập.',
    'job_config_input'                    => 'Đầu vào của bạn',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Nhập tên album',
    'job_config_fake_artist_text'         => 'Nhiều thói quen nhập có một vài bước cấu hình bạn phải trải qua. Trong trường hợp nhà cung cấp nhập khẩu giả, bạn phải trả lời một số câu hỏi kỳ lạ. Trong trường hợp này, nhập "David Bowie" để tiếp tục.',
    'job_config_fake_song_title'          => 'Nhập tên bài hát',
    'job_config_fake_song_text'           => 'Nhắc đến bài hát "Golden years" để tiếp tục với bản nhập giả.',
    'job_config_fake_album_title'         => 'Nhập tên album',
    'job_config_fake_album_text'          => 'Một số thói quen nhập khẩu yêu cầu thêm dữ liệu giữa chừng khi nhập. Trong trường hợp nhà cung cấp nhập khẩu giả, bạn phải trả lời một số câu hỏi kỳ lạ. Nhập "Station to station" để tiếp tục.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Nhập thiết lập (1/4) - Tải lên tệp của bạn',
    'job_config_file_upload_text'         => 'Thủ tục này sẽ giúp bạn nhập tệp từ ngân hàng của bạn vào Firefly III. ',
    'job_config_file_upload_help'         => 'Chọn tập tin của bạn. Vui lòng đảm bảo tệp được mã hóa UTF-8.',
    'job_config_file_upload_config_help'  => 'Nếu trước đây bạn đã nhập dữ liệu vào Firefly III, bạn có thể có tệp cấu hình, tệp này sẽ đặt trước các giá trị cấu hình cho bạn. Đối với một số ngân hàng, những người dùng khác vui lòng cung cấp <a href="https://github.com/firefly-iii/import-configurations/wiki">tập tin cấu hình</a>',
    'job_config_file_upload_type_help'    => 'Chọn loại tệp bạn sẽ tải lên',
    'job_config_file_upload_submit'       => 'Tải lên tập tin',
    'import_file_type_csv'                => 'CSV (comma separated values)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Tệp bạn đã tải lên không được mã hóa dưới dạng UTF-8 hoặc ASCII. Firefly III không thể xử lý các tập tin như vậy. Vui lòng sử dụng Notepad ++ hoặc Sublime để chuyển đổi tệp của bạn sang UTF-8.',
    'job_config_uc_title'                 => 'Nhập thiết lập (2/4) - Thiết lập tệp cơ bản',
    'job_config_uc_text'                  => 'Để có thể nhập tệp của bạn một cách chính xác, vui lòng xác thực các tùy chọn bên dưới.',
    'job_config_uc_header_help'           => 'Chọn hộp này nếu hàng đầu tiên của tệp CSV của bạn là tiêu đề cột.',
    'job_config_uc_date_help'             => 'Định dạng thời gian ngày trong tập tin của bạn. Thực hiện theo định dạng như <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">trang này</a> chỉ ra. Giá trị mặc định sẽ phân tích các ngày giống như sau :: dateExample.',
    'job_config_uc_delimiter_help'        => 'Chọn dấu phân cách trường được sử dụng trong tệp đầu vào của bạn. Nếu không chắc chắn, dấu phẩy là lựa chọn an toàn nhất.',
    'job_config_uc_account_help'          => 'Nếu tệp của bạn KHÔNG chứa thông tin về (các) tài khoản của bạn, hãy sử dụng danh sách thả xuống này để chọn tài khoản mà các giao dịch trong tệp thuộc về tài khoản nào.',
    'job_config_uc_apply_rules_title'     => 'Áp dụng quy tắc',
    'job_config_uc_apply_rules_text'      => 'Áp dụng quy tắc của bạn cho mọi giao dịch nhập. Lưu ý rằng điều này làm chậm việc nhập đáng kể.',
    'job_config_uc_specifics_title'       => 'Tùy chọn ngân hàng cụ thể',
    'job_config_uc_specifics_txt'         => 'Một số ngân hàng cung cấp các tập tin định dạng xấu. Firefly III có thể tự động sửa chúng. Nếu ngân hàng của bạn cung cấp các tệp như vậy nhưng nó không được liệt kê ở đây, vui lòng mở một vấn đề trên GitHub.',
    'job_config_uc_submit'                => 'Tiếp tục',
    'invalid_import_account'              => 'Bạn đã chọn một tài khoản không hợp lệ để nhập vào.',
    'import_liability_select'             => 'Trách nhiệm',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Chọn đăng nhập của bạn',
    'job_config_spectre_login_text'       => 'Firefly III đã tìm thấy: thông tin đăng nhập hiện có trong tài khoản Spectre của bạn. Bạn muốn sử dụng cái nào để nhập?',
    'spectre_login_status_active'         => 'Hoạt động',
    'spectre_login_status_inactive'       => 'Không hoạt động',
    'spectre_login_status_disabled'       => 'Đã vô hiệu hóa',
    'spectre_login_new_login'             => 'Đăng nhập với một ngân hàng khác hoặc một trong những ngân hàng này với các thông tin khác nhau.',
    'job_config_spectre_accounts_title'   => 'Chọn tài khoản để nhập từ',
    'job_config_spectre_accounts_text'    => 'Bạn đã chọn ": name" (: quốc gia). Bạn có: số tài khoản có sẵn từ nhà cung cấp này. Vui lòng chọn (các) tài khoản Firefly III nơi các giao dịch từ các tài khoản này sẽ được lưu trữ. Hãy nhớ rằng, để nhập dữ liệu cả tài khoản Firefly III và tài khoản ": name" phải có cùng loại tiền.',
    'spectre_do_not_import'               => '(không nhập)',
    'spectre_no_mapping'                  => 'Có vẻ như bạn chưa chọn bất kỳ tài khoản nào để nhập.',
    'imported_from_account'               => 'Đã nhập từ ":account"',
    'spectre_account_with_number'         => 'Số tài khoản: ',
    'job_config_spectre_apply_rules'      => 'Áp dụng quy tắc',
    'job_config_spectre_apply_rules_text' => 'Theo mặc định, quy tắc của bạn sẽ được áp dụng cho các giao dịch được tạo trong quy trình nhập này. Nếu bạn không muốn điều này xảy ra, hãy bỏ chọn hộp kiểm này.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'Tài khoản bunq',
    'job_config_bunq_accounts_text'       => 'Đây là những tài khoản được liên kết với tài khoản bunq của bạn. Vui lòng chọn các tài khoản mà bạn muốn nhập.',
    'bunq_no_mapping'                     => 'Có vẻ như bạn chưa chọn bất kỳ tài khoản nào.',
    'should_download_config'              => 'Bạn nên tải về <a href=":route">the configuration file</a> Vì điều này sẽ làm cho cách nhập trong tương lai dễ dàng hơn.',
    'share_config_file'                   => 'Nếu bạn đã nhập dữ liệu từ một ngân hàng công cộng, bạn nên <a href="https://github.com/firefly-iii/import-configurations/wiki">chia sẻ tập tin cấu hình của bạn</a> do đó sẽ dễ dàng cho người dùng khác nhập dữ liệu của họ. Chia sẻ tệp cấu hình của bạn sẽ không tiết lộ chi tiết tài chính của bạn.',
    'job_config_bunq_apply_rules'         => 'Áp dụng quy tắc',
    'job_config_bunq_apply_rules_text'    => 'Theo mặc định, quy tắc của bạn sẽ được áp dụng cho các giao dịch được tạo trong quy trình nhập này. Nếu bạn không muốn điều này xảy ra, hãy bỏ chọn hộp kiểm này.',
    'bunq_savings_goal'                   => 'Mục tiêu tiết kiệm :: số tiền (: phần trăm%)',
    'bunq_account_status_CANCELLED'       => 'Tài khoản bunq đã đóng',

    'ynab_account_closed'                  => 'Tài khoản đã bị đóng!',
    'ynab_account_deleted'                 => 'Tài khoản đã bị xóa!',
    'ynab_account_type_savings'            => 'tài khoản tiết kiệm',
    'ynab_account_type_checking'           => 'kiểm tra tài khoản',
    'ynab_account_type_cash'               => 'cash account',
    'ynab_account_type_creditCard'         => 'tài khoản tiền mặt',
    'ynab_account_type_lineOfCredit'       => 'hạn mức tín dụng',
    'ynab_account_type_otherAsset'         => 'tài khoản khác',
    'ynab_account_type_otherLiability'     => 'những khoản nợ khác',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'tài khoản thương gia',
    'ynab_account_type_investmentAccount'  => 'tài khoản đầu tư',
    'ynab_account_type_mortgage'           => 'thế chấp',
    'ynab_do_not_import'                   => '(không nhập)',
    'job_config_ynab_apply_rules'          => 'Áp dụng quy tắc',
    'job_config_ynab_apply_rules_text'     => 'Theo mặc định, quy tắc của bạn sẽ được áp dụng cho các giao dịch được tạo trong quy trình nhập này. Nếu bạn không muốn điều này xảy ra, hãy bỏ chọn hộp kiểm này.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Chọn ngân sách của bạn',
    'job_config_ynab_select_budgets_text'  => 'Bạn có: ngân sách được lưu trữ tại YNAB. Vui lòng chọn một trong đó Firefly III sẽ nhập các giao dịch.',
    'job_config_ynab_no_budgets'           => 'Không có ngân sách có sẵn để được nhập từ.',
    'ynab_no_mapping'                      => 'Có vẻ như bạn chưa chọn bất kỳ tài khoản nào để nhập từ.',
    'job_config_ynab_bad_currency'         => 'Bạn không thể nhập từ (các) ngân sách sau, vì bạn không có tài khoản có cùng loại tiền với các ngân sách này.',
    'job_config_ynab_accounts_title'       => 'Chọn tài khoản',
    'job_config_ynab_accounts_text'        => 'Bạn có các tài khoản sau đây trong ngân sách này. Vui lòng chọn từ tài khoản bạn muốn nhập và nơi lưu trữ các giao dịch.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Trạng thái',
    'spectre_extra_key_card_type'          => 'Loại thẻ',
    'spectre_extra_key_account_name'       => 'Tên tài khoản',
    'spectre_extra_key_client_name'        => 'Tên khách hàng',
    'spectre_extra_key_account_number'     => 'Số tài khoản',
    'spectre_extra_key_blocked_amount'     => 'Số lượng bị chặn',
    'spectre_extra_key_available_amount'   => 'Số lượng có sẵn',
    'spectre_extra_key_credit_limit'       => 'Giới hạn tín dụng',
    'spectre_extra_key_interest_rate'      => 'Lãi suất',
    'spectre_extra_key_expiry_date'        => 'Ngày hết hạn',
    'spectre_extra_key_open_date'          => 'Ngày mở',
    'spectre_extra_key_current_time'       => 'Thời điểm hiện tại',
    'spectre_extra_key_current_date'       => 'Ngày hiện tại',
    'spectre_extra_key_cards'              => 'Thẻ',
    'spectre_extra_key_units'              => 'Các đơn vị',
    'spectre_extra_key_unit_price'         => 'Đơn giá',
    'spectre_extra_key_transactions_count' => 'Số lượng giao dịch',

    //job configuration for finTS
    'fints_connection_failed'              => 'Đã xảy ra lỗi trong khi cố gắng kết nối với ngân hàng của bạn. Vui lòng đảm bảo rằng tất cả dữ liệu bạn nhập là chính xác. Thông báo lỗi ban đầu:',

    'job_config_fints_url_help'       => 'E.g. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Đối với nhiều ngân hàng, đây là số tài khoản của bạn.',
    'job_config_fints_port_help'      => 'Cổng mặc định là 443.',
    'job_config_fints_account_help'   => 'Chọn tài khoản ngân hàng mà bạn muốn nhập giao dịch.',
    'job_config_local_account_help'   => 'Chọn tài khoản Firefly III tương ứng với tài khoản ngân hàng của bạn đã chọn ở trên.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Tạo mô tả tốt hơn trong xuất ING',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Cắt trích dẫn từ các tệp xuất SNS / Volksbank',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Khắc phục sự cố tiềm ẩn với các tệp ABN AMRO',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Khắc phục sự cố tiềm ẩn với các tệp Rabobank',
    'specific_pres_name'              => 'Lựa chọn tài chính của Chủ tịch CA',
    'specific_pres_descr'             => 'Khắc phục sự cố tiềm ẩn với tệp PC',
    'specific_belfius_name'           => 'Tháp chuông BE',
    'specific_belfius_descr'          => 'Khắc phục sự cố tiềm ẩn với các tệp của Belfius',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Khắc phục sự cố tiềm ẩn với các tệp ING Belgium',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Nhập thiết lập (3/4) - Xác định vai trò của từng cột',
    'job_config_roles_text'           => 'Mỗi cột trong tệp CSV của bạn chứa dữ liệu nhất định. Vui lòng cho biết loại dữ liệu mà nhà nhập khẩu nên mong đợi. Tùy chọn "ánh xạ" dữ liệu có nghĩa là bạn sẽ liên kết từng mục được tìm thấy trong cột với một giá trị trong cơ sở dữ liệu của bạn. Cột thường được ánh xạ là cột chứa IBAN của tài khoản đối diện. Điều đó có thể dễ dàng khớp với hiện tại của IBAN trong cơ sở dữ liệu của bạn.',
    'job_config_roles_submit'         => 'Tiếp tục',
    'job_config_roles_column_name'    => 'Tên cột',
    'job_config_roles_column_example' => 'Ví dụ cột dữ liệu ',
    'job_config_roles_column_role'    => 'Ý nghĩa cột dữ liệu',
    'job_config_roles_do_map_value'   => 'Ánh xạ các giá trị này',
    'job_config_roles_no_example'     => 'Không có dữ liệu mẫu',
    'job_config_roles_fa_warning'     => 'Nếu bạn đánh dấu một cột có chứa một số tiền bằng ngoại tệ, bạn cũng phải đặt cột chứa loại tiền đó.',
    'job_config_roles_rwarning'       => 'Ít nhất, đánh dấu một cột là cột số lượng. Bạn cũng nên chọn một cột cho mô tả, ngày và tài khoản đối lập.',
    'job_config_roles_colum_count'    => 'Cột',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Nhập thiết lập (4/4) - Kết nối dữ liệu nhập với dữ liệu Firefly III',
    'job_config_map_text'             => 'Trong các bảng sau, giá trị bên trái hiển thị cho bạn thông tin được tìm thấy trong tệp được tải lên của bạn. Nhiệm vụ của bạn là ánh xạ giá trị này, nếu có thể, đến một giá trị đã có trong cơ sở dữ liệu của bạn. Đom đóm sẽ dính vào bản đồ này. Nếu không có giá trị để ánh xạ tới hoặc bạn không muốn ánh xạ giá trị cụ thể, hãy chọn không có gì.',
    'job_config_map_nothing'          => 'Không có dữ liệu trong tệp của bạn mà bạn có thể ánh xạ tới các giá trị hiện có. Vui lòng nhấn "Bắt đầu nhập" để tiếp tục.',
    'job_config_field_value'          => 'Giá trị trường',
    'job_config_field_mapped'         => 'Ánh xạ tới',
    'map_do_not_map'                  => '(không ánh xạ)',
    'job_config_map_submit'           => 'Bắt đầu nhập',


    // import status page:
    'import_with_key'                 => 'Nhập bằng khóa \':key\'',
    'status_wait_title'               => 'Xin hãy đợi...',
    'status_wait_text'                => 'Hộp này sẽ biến mất trong giây lát.',
    'status_running_title'            => 'Quá trình nhập đang chạy',
    'status_job_running'              => 'Xin vui lòng chờ, đang nhập...',
    'status_job_storing'              => 'Xin vui lòng chờ, đang lưu trữ dữ liệu...',
    'status_job_rules'                => 'Xin vui lòng chờ, đang chạy quy tắc...',
    'status_fatal_title'              => 'Lỗi nghiêm trọng',
    'status_fatal_text'               => 'Việc nhập đã bị lỗi mà nó không thể phục hồi. Xin lỗi!',
    'status_fatal_more'               => 'Thông báo lỗi (có thể rất khó hiểu) này được bổ sung bởi các tệp nhật ký mà bạn có thể tìm thấy trên ổ cứng hoặc trong bộ chứa Docker nơi bạn chạy Firefly III.',
    'status_finished_title'           => 'Nhập xong',
    'status_finished_text'            => 'Quá trình nhập đã kết thúc.',
    'finished_with_errors'            => 'Có một số lỗi trong quá trình nhập. Vui lòng xem lại chúng cẩn thận.',
    'unknown_import_result'           => 'Kết quả nhập không xác định',
    'result_no_transactions'          => 'Không có giao dịch đã được nhập. Có lẽ tất cả chúng đều trùng lặp đơn giản là không có giao dịch nào được nhập. Có lẽ các tệp nhật ký có thể cho bạn biết những gì đã xảy ra. Nếu bạn nhập dữ liệu thường xuyên, điều này là bình thường.',
    'result_one_transaction'          => 'Chính xác một giao dịch đã được nhập. Nó được lưu trữ dưới thẻ <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> nơi bạn có thể kiểm tra.',
    'result_many_transactions'        => 'Firefly III đã nhập: giao dịch. Chúng được lưu trữ dưới thẻ <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> nơi bạn có thể kiểm tra chúng.',


    // general errors and warnings:
    'bad_job_status'                  => 'Để truy cập trang này, công việc nhập của bạn không thể có trạng thái ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(bỏ qua cột này)',
    'column_account-iban'             => 'tài khoản (IBAN)',
    'column_account-id'               => 'ID tài khoản (matching FF3)',
    'column_account-name'             => 'tài khoản (tên)',
    'column_account-bic'              => 'tài khoản (BIC)',
    'column_amount'                   => 'Số tiền',
    'column_amount_foreign'           => 'Số tiền (bằng ngoại tệ)',
    'column_amount_debit'             => 'Số tiền (cột ghi nợ)',
    'column_amount_credit'            => 'Số tiền (cột tín dụng)',
    'column_amount_negated'           => 'Số tiền (cột âm)',
    'column_amount-comma-separated'   => 'Số tiền (dấu phẩy là dấu phân cách thập phân)',
    'column_bill-id'                  => 'ID hóa đơn (matching FF3)',
    'column_bill-name'                => 'Tên hóa đơn',
    'column_budget-id'                => 'ID ngân sách (matching FF3)',
    'column_budget-name'              => 'Tên ngân sách',
    'column_category-id'              => 'ID danh mục (matching FF3)',
    'column_category-name'            => 'Tên danh mục',
    'column_currency-code'            => 'Mã tiền tệ (ISO 4217)',
    'column_foreign-currency-code'    => 'Mã ngoại tệ (ISO 4217)',
    'column_currency-id'              => 'ID tiền tệ (matching FF3)',
    'column_currency-name'            => 'Tên tiền tệ (matching FF3)',
    'column_currency-symbol'          => 'Ký hiệu tiền tệ (matching FF3)',
    'column_date-interest'            => 'Ngày tính lãi',
    'column_date-book'                => 'Ngày đặt giao dịch',
    'column_date-process'             => 'Ngày xử lý giao dịch',
    'column_date-transaction'         => 'Ngày',
    'column_date-due'                 => 'Ngày đáo hạn giao dịch',
    'column_date-payment'             => 'Ngày thanh toán giao dịch',
    'column_date-invoice'             => 'Ngày hóa đơn giao dịch',
    'column_description'              => 'Mô tả',
    'column_opposing-iban'            => 'Tài khoản đối lập (IBAN)',
    'column_opposing-bic'             => 'Tài khoản đối lập (BIC)',
    'column_opposing-id'              => 'ID tài khoản đối lập (matching FF3)',
    'column_external-id'              => 'ID bên ngoài',
    'column_opposing-name'            => 'Tài khoản đối lập (tên)',
    'column_rabo-debit-credit'        => 'Chỉ số ghi nợ / tín dụng cụ thể của Rabobank',
    'column_ing-debit-credit'         => 'Chỉ số ghi nợ / tín dụng cụ thể của ING',
    'column_generic-debit-credit'     => 'Chỉ tiêu ghi nợ / tín dụng ngân hàng chung',
    'column_sepa_ct_id'               => 'Mã định danh đầu cuối SEPA',
    'column_sepa_ct_op'               => 'Định danh tài khoản đối lập SEPA',
    'column_sepa_db'                  => 'Mã định danh ủy quyền SEPA',
    'column_sepa_cc'                  => 'Mã thanh toán bù trừ SEPA',
    'column_sepa_ci'                  => 'Định danh chủ nợ SEPA',
    'column_sepa_ep'                  => 'SEPA Định danh mục đích bên ngoài',
    'column_sepa_country'             => 'Mã quốc gia SEPA',
    'column_sepa_batch_id'            => 'ID SEPA',
    'column_tags-comma'               => 'Thẻ (được phân tách bằng dấu phẩy)',
    'column_tags-space'               => 'Thẻ (được phân tách bằng dấu space)',
    'column_account-number'           => 'tài khoản (số tài khoản)',
    'column_opposing-number'          => 'Tài khoản đối diện (số tài khoản)',
    'column_note'                     => 'Lưu ý',
    'column_internal-reference'       => 'Tài liệu tham khảo nội bộ',

    // error message
    'duplicate_row'                   => 'Không thể nhập hàng #: row (": description"). Nó đã tồn tại.',

];
