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
    'greeting'                         => 'Chào bạn,',
    'closing'                          => 'Haha',
    'signature'                        => 'Thư gửi tự động',
    'footer_ps'                        => 'Thông báo này đã được gửi vì một yêu cầu từ IP :ipAddress đã kích hoạt nó.',

    // admin test
    'admin_test_subject'               => 'Một thông báo kiểm tra từ bản cài đặt Firefly III của bạn',
    'admin_test_body'                  => 'Đây là một thông báo thử nghiệm từ Firefly III của bạn. Nó đã được gửi đến :email.',

    // access token created
    'access_token_created_subject'     => 'Mã truy cập mới đã được tạo',
    'access_token_created_body'        => 'Ai đó (hy vọng bạn) vừa tạo Mã thông báo truy cập API Firefly III mới cho tài khoản người dùng của bạn.',
    'access_token_created_explanation' => 'Với mã thông báo này, họ có thể truy cập <strong> tất cả </ strong> hồ sơ tài chính của bạn thông qua API Firefly III.',
    'access_token_created_revoke'      => 'Nếu đây không phải là bạn, vui lòng thu hồi mã thông báo này càng sớm càng tốt tại :url.',

    // registered
    'registered_subject'               => 'Chào mừng đến với Firefly III!',
    'registered_welcome'               => 'Chào mừng đến <a style="color:#337ab7" href=":address">Firefly III</a>. Đăng ký của bạn đã được thực hiện và email này để xác nhận nó!',
    'registered_pw'                    => 'Nếu bạn đã quên mật khẩu của mình, vui lòng đặt lại bằng cách sử dụng <a style="color:#337ab7" href=":address/password/reset"> công cụ đặt lại mật khẩu </a>.',
    'registered_help'                  => 'Có một biểu tượng trợ giúp ở góc trên bên phải của mỗi trang. Nếu bạn cần giúp đỡ, bấm vào nó!',
    'registered_doc_html'              => 'Nếu bạn chưa có, vui lòng đọc lý thuyết <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory"> </ a>.',
    'registered_doc_text'              => 'Nếu bạn chưa có, xin vui lòng đọc hướng dẫn sử dụng đầu tiên và mô tả.',
    'registered_closing'               => 'Hãy tận hưởng!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Thiết lập lại mật khẩu đăng nhập:',
    'registered_doc_link'              => 'Tài Liệu:',

    // email change
    'email_change_subject'             => 'Địa chỉ email Firefly III của bạn đã thay đổi',
    'email_change_body_to_new'         => 'Bạn hoặc ai đó có quyền truy cập vào tài khoản Firefly III đã thay đổi địa chỉ email của bạn.',
    'email_change_body_to_old'         => 'Bạn hoặc ai đó có quyền truy cập vào tài khoản Firefly III đã thay đổi địa chỉ email của bạn. Nếu không phải bạn, bạn <strong> phải </ strong> theo liên kết "hoàn tác" bên dưới để bảo vệ tài khoản của bạn!',
    'email_change_ignore'              => 'Nếu bạn đã bắt đầu thay đổi, bạn có thể bỏ qua thông báo này một cách an toàn.',
    'email_change_old'                 => 'Địa chỉ email cũ là: :email',
    'email_change_old_strong'          => 'Địa chỉ email cũ là: <strong>:email</strong>',
    'email_change_new'                 => 'Địa chỉ email mới là: :email',
    'email_change_new_strong'          => 'Địa chỉ email mới là: <strong>:email</strong>',
    'email_change_instructions'        => 'Bạn không thể sử dụng Firefly III cho đến khi bạn xác nhận thay đổi này. Vui lòng theo liên kết dưới đây để làm như vậy.',
    'email_change_undo_link'           => 'Để hoàn tác thay đổi, hãy theo liên kết sau:',

    // OAuth token created
    'oauth_created_subject'            => 'Một khóa mới đã được tạo',
    'oauth_created_body'               => 'Ai đó (hy vọng là bạn) vừa tạo API OAuth Client Firefly III mới cho tài khoản người dùng của bạn. Nó được gắn nhãn ":name" và có URL <span style = "font-family: monospace;">:url </span>.',
    'oauth_created_explanation'        => 'Với client này, họ có thể truy cập <strong> tất cả </strong> hồ sơ tài chính của bạn thông qua API Firefly III.',
    'oauth_created_undo'               => 'Nếu đây không phải là bạn, vui lòng thu hồi client này càng sớm càng tốt tại: :url.',

    // reset password
    'reset_pw_subject'                 => 'Yêu cầu tạo lại mật khẩu',
    'reset_pw_instructions'            => 'Ai đó đã cố gắng thiết lập lại mật khẩu của bạn. Nếu đó là bạn, vui lòng theo liên kết dưới đây để làm như vậy.',
    'reset_pw_warning'                 => '<strong> XIN VUI LÒNG </strong> xác minh rằng liên kết thực sự đi đến Firefly III!',

    // error
    'error_subject'                    => 'Bắt lỗi trong Firefly III',
    'error_intro'                      => 'Firefly III v::version gặp lỗi: <span style = "font-family: monospace;">:errorMessage </span>.',
    'error_type'                       => 'Lỗi thuộc loại: ":class".',
    'error_timestamp'                  => 'Lỗi xảy ra vào / tại: :time.',
    'error_location'                   => 'Lỗi này xảy ra trong tệp "<span style =" font-family: monospace; ">:file </span>" trên dòng :line với code :code.',
    'error_user'                       => 'Người dùng đã gặp phải lỗi #:id, <a href="mailto::email">:email </a>.',
    'error_no_user'                    => 'Không có người dùng đăng nhập cho lỗi này hoặc không có người dùng nào được phát hiện.',
    'error_ip'                         => 'Địa chỉ IP liên quan đến lỗi này là: :ip',
    'error_url'                        => 'URL là: :url',
    'error_user_agent'                 => 'Đại lý người dùng: :userAgent',
    'error_stacktrace'                 => 'Các stacktrace đầy đủ là dưới đây. Nếu bạn nghĩ rằng đây là một lỗi trong Firefly III, bạn có thể chuyển tiếp tin nhắn này tới <a href="mailto:james@firefly-iii.org?subject=BUG!"> james@firefly-iii.org </a>. Điều này có thể giúp khắc phục lỗi bạn vừa gặp phải.',
    'error_github_html'                => 'Nếu bạn thích, bạn cũng có thể mở một vấn đề mới trên <a href="https://github.com/firefly-iii/firefly-iii/issues"> GitHub </a>.',
    'error_github_text'                => 'Nếu bạn thích, bạn cũng có thể mở một vấn đề mới trên https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Các stacktrace đầy đủ bên dưới:',

    // report new journals
    'new_journals_subject'             => 'Firefly III đã tạo một giao dịch mới | Firefly III đã tạo: :count các giao dịch mới',
    'new_journals_header'              => 'Firefly III đã tạo ra một giao dịch cho bạn. Bạn có thể tìm thấy nó trong bản cài đặt Firefly III: | Firefly III đã tạo :count giao dịch cho bạn. Bạn có thể tìm thấy chúng trong bản cài đặt Firefly III:',
];
