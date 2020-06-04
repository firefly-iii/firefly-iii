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
    'index_breadcrumb'                    => '导入资料到 Firefly III',
    'prerequisites_breadcrumb_fake'       => '假导入供应商的先决条件',
    'prerequisites_breadcrumb_spectre'    => 'Spectre 的先决条件',
    'job_configuration_breadcrumb'        => '":key" 设定',
    'job_status_breadcrumb'               => '":key" 导入状态',
    'disabled_for_demo_user'              => '在展示中不启用',

    // index page:
    'general_index_intro'                 => '欢迎来到 Firefly III 的导入例行。有几种方法可以将资料导入 Firefly III 中，在此以按钮表示。',

    // notices about the CSV importer:
    'deprecate_csv_import' => '正如 <a href="https://www.patreon.com/posts/future-updates-30012174">在这个Patreon帖子</a>中所概述的那样，Firefly III管理导入数据的方式将会改变。 这意味着CSV进口商将被转移到一个新的、单独的工具。 如果您访问 <a href="https://github.com/firefly-iii/csv-importer">此 GitHub 仓库</a>，您可以测试此工具。 如果你测试了新的导入并且让我知道，我将不胜感激。',
    'final_csv_import'     => '正如 <a href="https://www.patreon.com/posts/future-updates-30012174">在这个Patreon帖子</a>中所概述的那样，Firefly III管理导入数据的方式将会改变。 这意味着CSV进口商将被转移到一个新的、单独的工具。 如果您访问 <a href="https://github.com/firefly-iii/csv-importer">此 GitHub 仓库</a>，您可以测试此工具。 如果你测试了新的导入并且让我知道，我将不胜感激。',

    // import provider strings (index):
    'button_fake'                         => '假造导入',
    'button_file'                         => '导入档案',
    'button_spectre'                      => '自 Spectre 导入',

    // prerequisites box (index)
    'need_prereq_title'                   => '导入先决条件',
    'need_prereq_intro'                   => '部分导入方式您得先在使用前注意一下。比方说，他们可能需要特别的串接秘钥或应用程式金钥，您可在此设定。此图示表示所属的先决条件已经媒合。',
    'do_prereq_fake'                      => '假导入供应商的先决条件',
    'do_prereq_file'                      => '档案导入的先决条件',
    'do_prereq_spectre'                   => '使用 Spectre 导入的先决条件',

    // prerequisites:
    'prereq_fake_title'                   => '自假的导入供应商导入的先决条件',
    'prereq_fake_text'                    => '这个假的供应商需要一个假的 API 金钥，必须是32个字元长。您可以使用此：12446809901236890123690124444466990aa',
    'prereq_spectre_title'                => '使用 Spectre API 导入的先决条件',
    'prereq_spectre_text'                 => '为使用 Spectre API (v4) 导入资料，您必须提供 Firefly III 两个秘密数值，可于 <a href="https://www.saltedge.com/clients/profile/secrets">密钥页面</a> 找到。',
    'prereq_spectre_pub'                  => '同理，Spectre API 也会需要您下方看见的公钥。若无此公钥，服务供应商无法辨认您，请于您的 <a href="https://www.saltedge.com/clients/profile/secrets">密钥页面</a> 键入您的公钥。',
    'callback_not_tls'                    => 'Firefly III 侦测到以下回呼 URI。您的伺服器似乎没有设定成 TLS-连接 (HTTP)。YNAB 不会接受此 URI，你可以继续导入 (因为 Firefly III 可能是错的)，但请记住这一点。',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => '假 API 金钥存储成功！',
    'prerequisites_saved_for_spectre'     => '应用程式 ID 与密钥已储存！',

    // job configuration:
    'job_config_apply_rules_title'        => '工作设定 - 套用您的规则？',
    'job_config_apply_rules_text'         => '一旦假供应商执行，您的规则可用于交易。这将为导入增加时间。',
    'job_config_input'                    => '您的输入',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => '输入专辑名称',
    'job_config_fake_artist_text'         => '许多导入惯常程序都有几个必须经过的配置步骤。在假导入供应商的情况下，你必须回答一些奇怪的问题。在这种情况下，请输入 "David Bowie" 继续。',
    'job_config_fake_song_title'          => '输入歌曲名称',
    'job_config_fake_song_text'           => '请键入 "Golden years" 以继续假导入。',
    'job_config_fake_album_title'         => '输入专辑名称',
    'job_config_fake_album_text'          => '某些导入惯常程序在导入过程中需要额外的资料。在假导入供应商的情况下，你必须回答一些奇怪的问题。请输入 "Station to station" 继续。',
    // job configuration form the file provider
    'job_config_file_upload_title'        => '导入设定 (1/4) - 上传您的档案',
    'job_config_file_upload_text'         => '此惯常程序将协助您从您银行将档案导入 Firefly III。',
    'job_config_file_upload_help'         => '选择您的档案，请确定档案是 UTF-8 编码。',
    'job_config_file_upload_config_help'  => '如果您之前已导入过档案至 Firefly III，您可能已有可提供预设值的设定档案。就部分银行，其他使用者业已慷慨地提供了他们的 <a href="https://github.com/firefly-iii/import-configurations/wiki">设定档</a>。',
    'job_config_file_upload_type_help'    => '选择要上传的档案类型',
    'job_config_file_upload_submit'       => '上传档案',
    'import_file_type_csv'                => 'CSV (以逗号分隔值)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => '您上传的档案并非以 UTF-8 或 ASCII 编码，Firefly III 无法处理此类档案，请使用 Notepad++ 或 Sublime 转换您的档案成 UTF-8 格式。',
    'job_config_uc_title'                 => '导入设定 (2/4) - 基本档案设定',
    'job_config_uc_text'                  => '若要正确导入您的档案，请验证以下选项。',
    'job_config_uc_header_help'           => '若您的 CSV 档案第一列均为栏位标题，请核选此选项。',
    'job_config_uc_date_help'             => '您档案内的日期格式。请依循 <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">本页</a> 所示的格式，预设值将以 :dateExample 形式呈现日期。',
    'job_config_uc_delimiter_help'        => '选择您档案所使用的栏位分隔符号，若不确定，逗号系最为安全的选项。',
    'job_config_uc_account_help'          => '若您的档案不包含资产帐户的资讯，使用此下拉式选单选择此档案内交易所属的帐户。',
    'job_config_uc_apply_rules_title'     => '套用规则',
    'job_config_uc_apply_rules_text'      => '套用规则至每一个导入的交易，请注意此功能会显著地降低导入速度。',
    'job_config_uc_specifics_title'       => '特定银行选项',
    'job_config_uc_specifics_txt'         => '部分银行提供格式残不佳的档案，Firefly III 可以自动修复这个问题。如果银行提供了不佳的档案，又没有列在这边，请至 GitHub 开启新的讨论。',
    'job_config_uc_submit'                => '继续',
    'invalid_import_account'              => '您选择了一个无效帐号来导入。',
    'import_liability_select'             => '债务',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => '选择您的登入',
    'job_config_spectre_login_text'       => 'Firefly III 已在您的 Spectre 帐户找到 :count 笔既存登入，哪一个是您想要导入的呢？',
    'spectre_login_status_active'         => '启用',
    'spectre_login_status_inactive'       => '未启用',
    'spectre_login_status_disabled'       => '停用',
    'spectre_login_new_login'             => '使用其他银行登入，或其中一间具有不同凭证的银行。',
    'job_config_spectre_accounts_title'   => '选择欲导入的帐户',
    'job_config_spectre_accounts_text'    => '您以选择 ":name" (:country)。您在这个供应商有 :count 个可用帐户，请在 Firefly III 的资产帐户中选择这些交易应被储存的帐户。请记得，若要导入资料，Firefly III 与 ":name"-帐户两者均需使用相同货币。',
    'spectre_do_not_import'               => '(不导入)',
    'spectre_no_mapping'                  => '您似乎没有选择任何欲导入的帐户。',
    'imported_from_account'               => '已自 ":account" 导入',
    'spectre_account_with_number'         => '帐户 :number',
    'job_config_spectre_apply_rules'      => '套用规则',
    'job_config_spectre_apply_rules_text' => '预设下，您的规则会被套用至此次导入惯常程序中所建立的交易。若您不希望如此，请取消选取此核选方块。',

    // job configuration for bunq:
    'should_download_config'              => '您应该为此工作下载 <a href=":route">设定档</a>，可更俾利为来导入。',
    'share_config_file'                   => '如果您已自公有银行导入资料，您应该 <a href="https://github.com/firefly-iii/import-configurations/wiki">分享您的设定档</a> 俾利其他使用者导入他们的资料。分享您的设定档并不会暴露您的财务细节。',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => '国际银行帐户号码 (IBAN)',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => '状态',
    'spectre_extra_key_card_type'          => '卡片种类',
    'spectre_extra_key_account_name'       => '帐户名称',
    'spectre_extra_key_client_name'        => '客户名称',
    'spectre_extra_key_account_number'     => '帐户号码',
    'spectre_extra_key_blocked_amount'     => '封锁的金额',
    'spectre_extra_key_available_amount'   => '可用金额',
    'spectre_extra_key_credit_limit'       => '信用额度',
    'spectre_extra_key_interest_rate'      => '利率',
    'spectre_extra_key_expiry_date'        => '到期日',
    'spectre_extra_key_open_date'          => '开始日期',
    'spectre_extra_key_current_time'       => '目前时间',
    'spectre_extra_key_current_date'       => '目前日期',
    'spectre_extra_key_cards'              => '卡片',
    'spectre_extra_key_units'              => '单位',
    'spectre_extra_key_unit_price'         => '单价',
    'spectre_extra_key_transactions_count' => '交易数',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => '导入设定 (4/) - 连接导入资料与 Firefly III 资料',
    'job_config_map_text'             => '在下方的表格中，左边值显示在您上传的档案中所找到的资讯，而映射这些值是您当前的任务。如果可能，请映射至呈现在您资料库既有的值，Firefly III 会依此映射。若无可供映射的值，或您不希望映射特定值，请别选取。',
    'job_config_map_nothing'          => '您档案中并无可映射至既有数值的资料，请点选 "开始导入" 以继续。',
    'job_config_field_value'          => '栏位数值',
    'job_config_field_mapped'         => '映射至',
    'map_do_not_map'                  => '(不映射)',
    'job_config_map_submit'           => '开始导入',


    // import status page:
    'import_with_key'                 => '以键 \':key\' 导入',
    'status_wait_title'               => '请稍待……',
    'status_wait_text'                => '此方块过一会儿会消失。',
    'status_running_title'            => '导入正在执行',
    'status_job_running'              => '请稍候，执行导入…',
    'status_job_storing'              => '请稍候，储存资料…',
    'status_job_rules'                => '请稍候，执行规则…',
    'status_fatal_title'              => '重大错误',
    'status_fatal_text'               => '此次导入遇到了无法回复的错误，抱歉！',
    'status_fatal_more'               => '此一讯息 (可能非常加密) 是由日志档所补完，您可在硬碟内或您运行的 Firefly III 空间中找到日志档。',
    'status_finished_title'           => '导入完成',
    'status_finished_text'            => '导入已完成',
    'finished_with_errors'            => '执行导入时有些错误，请仔细複审。',
    'unknown_import_result'           => '未知的导入结果',
    'result_no_transactions'          => '没有导入任何交易纪录，或许是因为均为重複记录或无纪录可供导入。日志档也许能告诉你来龙去脉，若您定期导入资料，这是正常的。',
    'result_one_transaction'          => '仅有1笔交易被导入，并储存在 <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>，您之后可进一步检视。',
    'result_many_transactions'        => 'Firefly III 已导入 :count 笔交易，并储存 <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> 标签下，您之后可进一步检视。',

    // general errors and warnings:
    'bad_job_status'                  => '欲通行此页，您的导入工作不能有 ":status" 状态。',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];
