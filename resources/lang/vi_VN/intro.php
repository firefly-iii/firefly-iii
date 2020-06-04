<?php

/**
 * intro.php
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
    // index
    'index_intro'                                     => 'Chào mừng bạn đến trang chỉ mục của Firefly III. Hãy dành thời gian để đi qua phần giới thiệu này để hiểu cách Firefly III hoạt động.',
    'index_accounts-chart'                            => 'Biểu đồ này cho thấy số dư hiện tại của tài khoản của bạn. Bạn có thể chọn các tài khoản hiển thị ở đây trong tùy chọn của bạn.',
    'index_box_out_holder'                            => 'Chiếc hộp nhỏ này và những chiếc hộp bên cạnh sẽ cho bạn cái nhìn tổng quan nhanh về tình hình tài chính của bạn.',
    'index_help'                                      => 'Nếu bạn cần trợ giúp với một trang hoặc một form, nhấn nút này.',
    'index_outro'                                     => 'Hầu hết các trang của Firefly III sẽ bắt đầu với một chuyến tham quan nhỏ như thế này. Hãy liên hệ với tôi khi bạn có thắc mắc hoặc ý kiến.',
    'index_sidebar-toggle'                            => 'Để tạo giao dịch, tài khoản mới hoặc những thứ khác, hãy sử dụng menu dưới biểu tượng này.',
    'index_cash_account'                              => 'Đây là những tài khoản được tạo ra cho đến nay. Bạn có thể sử dụng tài khoản tiền mặt để theo dõi chi phí tiền mặt nhưng tất nhiên đó không phải là bắt buộc.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Chọn tài khoản hoặc tài sản yêu thích của bạn từ danh sách thả xuống này.',
    'transactions_create_withdrawal_destination'      => 'Chọn một tài khoản chi phí ở đây. Để trống nếu bạn muốn kiếm tiền.',
    'transactions_create_withdrawal_foreign_currency' => 'Sử dụng trường này để đặt ngoại tệ và số tiền.',
    'transactions_create_withdrawal_more_meta'        => 'Rất nhiều dữ liệu meta khác bạn đặt trong các trường này.',
    'transactions_create_withdrawal_split_add'        => 'Nếu bạn muốn phân tách một giao dịch, hãy thêm nhiều phân tách bằng nút này',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Chọn hoặc nhập người nhận thanh toán trong hộp thả xuống / hộp văn bản tự động hoàn thành này. Để trống nếu bạn muốn gửi tiền mặt.',
    'transactions_create_deposit_destination'         => 'Chọn một tài sản hoặc tài khoản nợ ở đây.',
    'transactions_create_deposit_foreign_currency'    => 'Sử dụng trường này để đặt ngoại tệ và số tiền.',
    'transactions_create_deposit_more_meta'           => 'Rất nhiều dữ liệu meta khác bạn đặt trong các trường này.',
    'transactions_create_deposit_split_add'           => 'Nếu bạn muốn phân tách một giao dịch, hãy thêm nhiều lần chia tách bằng nút này',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Chọn tài khoản nguồn tại đây.',
    'transactions_create_transfer_destination'        => 'Chọn tài khoản đích ở đây.',
    'transactions_create_transfer_foreign_currency'   => 'Sử dụng trường này để đặt ngoại tệ và số tiền.',
    'transactions_create_transfer_more_meta'          => 'Rất nhiều dữ liệu meta khác bạn đặt trong các trường này.',
    'transactions_create_transfer_split_add'          => 'Nếu bạn muốn phân tách một giao dịch, hãy thêm nhiều lần chia tách bằng nút này',

    // create account:
    'accounts_create_iban'                            => 'Cung cấp cho tài khoản của bạn một IBAN hợp lệ. Điều này có thể làm cho việc nhập dữ liệu rất dễ dàng trong tương lai.',
    'accounts_create_asset_opening_balance'           => 'tài khoản có thể có "số dư mở", cho biết bắt đầu lịch sử của tài khoản này trong Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III hỗ trợ nhiều loại tiền tệ. tài khoản có một loại tiền tệ chính mà bạn phải đặt ở đây.',
    'accounts_create_asset_virtual'                   => 'Đôi khi có thể giúp cung cấp cho tài khoản của bạn một số dư ảo: một số tiền bổ sung luôn được thêm vào hoặc xóa khỏi số dư thực tế.',

    // budgets index
    'budgets_index_intro'                             => 'Ngân sách được sử dụng để quản lý tài chính của bạn và tạo thành một trong những chức năng cốt lõi của Firefly III.',
    'budgets_index_set_budget'                        => 'Đặt tổng ngân sách của bạn cho mọi thời kỳ để Firefly III có thể cho bạn biết nếu bạn đã lập ngân sách tất cả số tiền có sẵn.',
    'budgets_index_see_expenses_bar'                  => 'Khi tiêu tiền thanh này sẽ được lấp đầy từ từ.',
    'budgets_index_navigate_periods'                  => 'Điều hướng qua các thời kỳ để dễ dàng đặt ngân sách trước thời hạn.',
    'budgets_index_new_budget'                        => 'Tạo ngân sách mới khi bạn thấy phù hợp.',
    'budgets_index_list_of_budgets'                   => 'Sử dụng bảng này để đặt số tiền cho từng ngân sách và xem bạn đang làm như thế nào.',
    'budgets_index_outro'                             => 'Để tìm hiểu thêm về lập ngân sách, hãy kiểm tra biểu tượng trợ giúp ở góc trên bên phải.',

    // reports (index)
    'reports_index_intro'                             => 'Sử dụng các báo cáo này để có được thông tin chi tiết về tài chính của bạn.',
    'reports_index_inputReportType'                   => 'Chọn một loại báo cáo. Kiểm tra các trang trợ giúp để xem mỗi báo cáo hiển thị cho bạn.',
    'reports_index_inputAccountsSelect'               => 'Bạn có thể loại trừ hoặc bao gồm các tài khoản khi bạn thấy phù hợp.',
    'reports_index_inputDateRange'                    => 'Phạm vi ngày đã chọn hoàn toàn tùy thuộc vào bạn: từ một ngày đến 10 năm.',
    'reports_index_extra-options-box'                 => 'Tùy thuộc vào báo cáo bạn đã chọn, bạn có thể chọn các bộ lọc và tùy chọn bổ sung tại đây. Xem hộp này khi bạn thay đổi loại báo cáo.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Báo cáo này sẽ cung cấp cho bạn một cái nhìn tổng quan nhanh chóng và toàn diện về tài chính của bạn. Nếu bạn muốn thấy bất cứ điều gì khác, xin vui lòng không liên lạc với tôi!',
    'reports_report_audit_intro'                      => 'Báo cáo này sẽ cung cấp cho bạn thông tin chi tiết về tài khoản của bạn.',
    'reports_report_audit_optionsBox'                 => 'Sử dụng các hộp kiểm này để hiển thị hoặc ẩn các cột bạn quan tâm.',

    'reports_report_category_intro'                  => 'Báo cáo này sẽ cung cấp cho bạn cái nhìn sâu sắc trong một hoặc nhiều danh mục.',
    'reports_report_category_pieCharts'              => 'Những biểu đồ này sẽ cung cấp cho bạn cái nhìn sâu sắc về chi phí và thu nhập cho mỗi danh mục hoặc mỗi tài khoản.',
    'reports_report_category_incomeAndExpensesChart' => 'Biểu đồ này cho thấy chi phí và thu nhập của bạn trên mỗi danh mục.',

    'reports_report_tag_intro'                  => 'Báo cáo này sẽ cung cấp cho bạn cái nhìn sâu sắc trong một hoặc nhiều thẻ.',
    'reports_report_tag_pieCharts'              => 'Những biểu đồ này sẽ cung cấp cho bạn cái nhìn sâu sắc về chi phí và thu nhập trên mỗi nhãn, tài khoản, danh mục hoặc ngân sách.',
    'reports_report_tag_incomeAndExpensesChart' => 'Biểu đồ này cho thấy chi phí và thu nhập của bạn trên mỗi nhãn.',

    'reports_report_budget_intro'                             => 'Báo cáo này sẽ cung cấp cho bạn cái nhìn sâu sắc về một hoặc nhiều ngân sách.',
    'reports_report_budget_pieCharts'                         => 'Những biểu đồ này sẽ cung cấp cho bạn cái nhìn sâu sắc về chi phí cho mỗi ngân sách hoặc mỗi tài khoản.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Biểu đồ này cho thấy chi phí của bạn trên mỗi ngân sách.',

    // create transaction
    'transactions_create_switch_box'                          => 'Sử dụng các nút này để nhanh chóng chuyển đổi loại giao dịch bạn muốn lưu.',
    'transactions_create_ffInput_category'                    => 'Bạn có thể tự do gõ vào lĩnh vực này. Các danh mục được tạo trước đây sẽ được đề xuất.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Liên kết rút tiền của bạn với ngân sách để kiểm soát tài chính tốt hơn.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Sử dụng danh sách thả xuống này khi rút tiền của bạn bằng loại tiền khác.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Sử dụng danh sách thả xuống này khi tiền gửi của bạn bằng loại tiền khác.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Chọn một con heo đất và liên kết chuyển khoản này với tiền tiết kiệm của bạn.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Trường này cho bạn biết bạn đã tiết kiệm được bao nhiêu trong mỗi con heo đất.',
    'piggy-banks_index_button'                                => 'Bên cạnh thanh tiến trình này là hai nút (+ và -) để thêm hoặc xóa tiền từ mỗi ngân hàng heo.',
    'piggy-banks_index_accountStatus'                         => 'Đối với mỗi tài khoản có ít nhất một ngân hàng heo, trạng thái được liệt kê trong bảng này.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Mục tiêu của bạn là gì? Một chiếc ghế dài mới, một máy ảnh, tiền cho các trường hợp khẩn cấp?',
    'piggy-banks_create_date'                                 => 'Bạn có thể đặt ngày mục tiêu hoặc thời hạn cho ngân hàng heo của bạn.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Biểu đồ này sẽ cho thấy lịch sử của con heo đất này.',
    'piggy-banks_show_piggyDetails'                           => 'Một số chi tiết về con heo đất của bạn',
    'piggy-banks_show_piggyEvents'                            => 'Bất kỳ bổ sung hoặc loại bỏ cũng được liệt kê ở đây.',

    // bill index
    'bills_index_rules'                                       => 'Tại đây bạn thấy quy tắc nào sẽ kiểm tra xem hóa đơn này có được nhấn hay không',
    'bills_index_paid_in_period'                              => 'Trường này cho biết khi hóa đơn được thanh toán lần cuối.',
    'bills_index_expected_in_period'                          => 'Trường này cho biết mỗi hóa đơn nếu và khi hóa đơn tiếp theo dự kiến đạt.',

    // show bill
    'bills_show_billInfo'                                     => 'Bảng này cho thấy một số thông tin chung về dự luật này.',
    'bills_show_billButtons'                                  => 'Sử dụng nút này để quét lại các giao dịch cũ để chúng được khớp với hóa đơn này.',
    'bills_show_billChart'                                    => 'Biểu đồ này cho thấy các giao dịch được liên kết với hóa đơn này.',

    // create bill
    'bills_create_intro'                                      => 'Sử dụng các hóa đơn để theo dõi số tiền bạn đáo hạn mỗi kỳ. Hãy suy nghĩ về các chi phí như tiền thuê nhà, bảo hiểm hoặc thế chấp.',
    'bills_create_name'                                       => 'Sử dụng tên mô tả, chẳng hạn như "Thuê" hoặc "Bảo hiểm y tế".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Chọn số tiền tối thiểu và tối đa cho hóa đơn này.',
    'bills_create_repeat_freq_holder'                         => 'Hầu hết các hóa đơn lặp lại hàng tháng, nhưng bạn có thể đặt tần suất khác tại đây.',
    'bills_create_skip_holder'                                => 'Nếu hóa đơn lặp lại sau mỗi 2 tuần, trường "bỏ qua" phải được đặt thành "1" để bỏ qua mỗi tuần.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III cho phép bạn quản lý các quy tắc, sẽ tự động được áp dụng cho mọi giao dịch bạn tạo hoặc chỉnh sửa.',
    'rules_index_new_rule_group'                              => 'Bạn có thể kết hợp các quy tắc trong các nhóm để quản lý dễ dàng hơn.',
    'rules_index_new_rule'                                    => 'Tạo nhiều quy tắc như bạn muốn.',
    'rules_index_prio_buttons'                                => 'Đặt hàng cho họ bất cứ cách nào bạn thấy phù hợp.',
    'rules_index_test_buttons'                                => 'Bạn có thể kiểm tra quy tắc của mình hoặc áp dụng chúng cho các giao dịch hiện tại.',
    'rules_index_rule-triggers'                               => 'Bạn có thể kiểm tra quy tắc của mình hoặc áp dụng chúng cho các giao dịch hiện tại....',
    'rules_index_outro'                                       => 'Hãy chắc chắn kiểm tra các trang trợ giúp bằng biểu tượng (?) Ở trên cùng bên phải!',

    // create rule:
    'rules_create_mandatory'                                  => 'Chọn một tiêu đề mô tả và đặt khi quy tắc sẽ được kích hoạt.',
    'rules_create_ruletriggerholder'                          => 'Thêm bao nhiêu kích hoạt tùy thích, nhưng hãy nhớ rằng TẤT CẢ các kích hoạt phải khớp trước khi bất kỳ hành động nào được kích hoạt.',
    'rules_create_test_rule_triggers'                         => 'Sử dụng nút này để xem giao dịch nào sẽ phù hợp với quy tắc của bạn.',
    'rules_create_actions'                                    => 'Đặt bao nhiêu hành động tùy thích.',

    // preferences
    'preferences_index_tabs'                                  => 'Nhiều tùy chọn có sẵn đằng sau các tab này.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III hỗ trợ nhiều loại tiền tệ mà bạn có thể thay đổi trên trang này.',
    'currencies_index_default'                                => 'Firefly III có một loại tiền tệ mặc định.',
    'currencies_index_buttons'                                => 'Sử dụng các nút này để thay đổi loại tiền tệ mặc định hoặc kích hoạt các loại tiền tệ khác.',

    // create currency
    'currencies_create_code'                                  => 'Mã này phải tuân thủ ISO (Google mã cho loại tiền mới của bạn).',
];
