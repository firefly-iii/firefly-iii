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
    'job_configuration_breadcrumb'        => 'Cấu hình cho ":key"',
    'job_status_breadcrumb'               => 'Trạng thái nhập cho ":key"',
    'disabled_for_demo_user'              => 'bị vô hiệu hóa trong bản demo',

    // index page:
    'general_index_intro'                 => 'Chào mừng bạn đến với nhập dữ liệu cho Firefly III. Có một số cách nhập dữ liệu vào Firefly III, được hiển thị ở đây dưới dạng các nút.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Như đã nêu trong <a href="https://www.patreon.com/posts/future-updates-30012174">bài đăng Patreon này</a>, cách Firefly III quản lý nhập dữ liệu sẽ thay đổi. Điều đó có nghĩa là công cụ nhập CSV sẽ được chuyển sang một công cụ mới, riêng biệt. Bạn đã có thể sử dụng bản beta này nếu bạn truy cập <a href="https://github.com/firefly-iii/csv-importer">kho GitHub này</a>.',
    'final_csv_import'     => 'Như đã nêu trong <a href="https://www.patreon.com/posts/future-updates-30012174"> bài đăng Patreon này </a>, cách Firefly III quản lý nhập dữ liệu sẽ thay đổi. Điều đó có nghĩa là công cụ nhập CSV sẽ được chuyển sang một công cụ mới, riêng biệt. Bạn đã có thể sử dụng bản beta này nếu bạn truy cập <a href="https://github.com/firefly-iii/csv-importer">kho GitHub này</a>.',

    // import provider strings (index):
    'button_fake'                         => 'Giả mạo nhập',
    'button_file'                         => 'Nhập một tập tin',
    'button_spectre'                      => 'Nhập bằng Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Điều kiện tiên quyết nhập',
    'need_prereq_intro'                   => 'Một số phương pháp nhập cần sự cho phép của bạn trước khi chúng có thể được sử dụng. Ví dụ: họ có thể yêu cầu khóa API đặc biệt hoặc mật khẩu ứng dụng. Bạn có thể cấu hình chúng ở đây. Biểu tượng cho biết nếu những điều kiện tiên quyết này đã được đáp ứng.',
    'do_prereq_fake'                      => 'Điều kiện tiên quyết cho nhà cung cấp giả',
    'do_prereq_file'                      => 'Điều kiện tiên quyết để nhập tệp',
    'do_prereq_spectre'                   => 'Điều kiện tiên quyết để nhập bằng cách sử dụng Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Điều kiện tiên quyết để nhập từ nhà cung cấp nhập giả',
    'prereq_fake_text'                    => 'Nhà cung cấp giả mạo này yêu cầu khóa API giả. Nó phải dài 32 ký tự. Bạn có thể sử dụng cái này: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Điều kiện tiên quyết để nhập bằng API Spectre',
    'prereq_spectre_text'                 => 'Để nhập dữ liệu bằng API Spectre (v4), bạn phải cung cấp cho Firefly III hai giá trị bí mật. Tìm nó trên trên <a href="https://www.saltedge.com/clients/profile/secrets">trang bí mật</a>.',
    'prereq_spectre_pub'                  => 'Tương tự, API Spectre cần biết khóa công khai mà bạn thấy bên dưới. Không có nó, nó sẽ không nhận ra bạn. Vui lòng nhập khóa công khai này vào <a href="https://www.saltedge.com/clients/profile/secrets">trang bí mật</a>.',
    'callback_not_tls'                    => 'Firefly III đã phát hiện URI gọi lại sau đây. Có vẻ như máy chủ của bạn không được thiết lập để chấp nhận kết nối TLS (https). YNAB sẽ không chấp nhận URI này. Bạn có thể tiếp tục nhập (vì Firefly III có thể sai) nhưng vui lòng ghi nhớ điều này.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Khóa API giả được lưu trữ thành công!',
    'prerequisites_saved_for_spectre'     => 'ID ứng dụng và bí mật được lưu trữ!',

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
    'import_file_type_csv'                => 'CSV (dấu phân cách)',
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
    'should_download_config'              => 'Bạn nên tải về <a href=":route">the configuration file</a> Vì điều này sẽ làm cho cách nhập trong tương lai dễ dàng hơn.',
    'share_config_file'                   => 'Nếu bạn đã nhập dữ liệu từ một ngân hàng công cộng, bạn nên <a href="https://github.com/firefly-iii/import-configurations/wiki">chia sẻ tập tin cấu hình của bạn</a> do đó sẽ dễ dàng cho người dùng khác nhập dữ liệu của họ. Chia sẻ tệp cấu hình của bạn sẽ không tiết lộ chi tiết tài chính của bạn.',

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
    'result_many_transactions'        => 'Firefly III đã nhập: giao dịch. Chúng được lưu trữ dưới nhãn<a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> nơi bạn có thể kiểm tra chúng.',

    // general errors and warnings:
    'bad_job_status'                  => 'Để truy cập trang này, công việc nhập của bạn không thể có trạng thái ":status".',

    // error message
    'duplicate_row'                   => 'Không thể nhập hàng #:row (":description"). Nó đã tồn tại.',

];
