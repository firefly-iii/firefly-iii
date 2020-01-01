<?php

/**
 * import.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    'prerequisites_breadcrumb_bunq'       => 'bunq 的先决条件',
    'prerequisites_breadcrumb_ynab'       => 'YNAB 的先决条件',
    'job_configuration_breadcrumb'        => '":key" 设定',
    'job_status_breadcrumb'               => '":key" 导入状态',
    'disabled_for_demo_user'              => '在展示中不启用',

    // index page:
    'general_index_intro'                 => '欢迎来到 Firefly III 的导入例行。有几种方法可以将资料导入 Firefly III 中，在此以按钮表示。',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => '假造导入',
    'button_file'                         => '导入档案',
    'button_bunq'                         => '自 bunq 导入',
    'button_spectre'                      => '自 Spectre 导入',
    'button_plaid'                        => '使用 Plait 导入',
    'button_yodlee'                       => '使用 Yodlee 导入',
    'button_quovo'                        => '使用 Quovo 导入',
    'button_ynab'                         => '自 You Need A Budget 导入',
    'button_fints'                        => '使用 FinTS 导入',


    // prerequisites box (index)
    'need_prereq_title'                   => '导入先决条件',
    'need_prereq_intro'                   => '部分导入方式您得先在使用前注意一下。比方说，他们可能需要特别的串接秘钥或应用程式金钥，您可在此设定。此图示表示所属的先决条件已经媒合。',
    'do_prereq_fake'                      => '假导入供应商的先决条件',
    'do_prereq_file'                      => '档案导入的先决条件',
    'do_prereq_bunq'                      => '从 bunq 导入的先决条件',
    'do_prereq_spectre'                   => '使用 Spectre 导入的先决条件',
    'do_prereq_plaid'                     => '使用 Plaid 导入的先决条件',
    'do_prereq_yodlee'                    => '使用 Yodlee 导入的先决条件',
    'do_prereq_quovo'                     => '使用 Quovo 导入的先决条件',
    'do_prereq_ynab'                      => '从 YNAB 导入的先决条件',

    // prerequisites:
    'prereq_fake_title'                   => '自假的导入供应商导入的先决条件',
    'prereq_fake_text'                    => '这个假的供应商需要一个假的 API 金钥，必须是32个字元长。您可以使用此：12446809901236890123690124444466990aa',
    'prereq_spectre_title'                => '使用 Spectre API 导入的先决条件',
    'prereq_spectre_text'                 => '为使用 Spectre API (v4) 导入资料，您必须提供 Firefly III 两个秘密数值，可于 <a href="https://www.saltedge.com/clients/profile/secrets">密钥页面</a> 找到。',
    'prereq_spectre_pub'                  => '同理，Spectre API 也会需要您下方看见的公钥。若无此公钥，服务供应商无法辨认您，请于您的 <a href="https://www.saltedge.com/clients/profile/secrets">密钥页面</a> 键入您的公钥。',
    'prereq_bunq_title'                   => '从 bunq 导入的先决条件',
    'prereq_bunq_text'                    => '为自 bunq 导入，您需要获得一组 API 金钥，您可以从应用程式著手。请注意自 bunq 导入的功能仍是测试版本，仅在沙盒 API 内完成测试而已。',
    'prereq_bunq_ip'                      => 'bunq 需要您的对外 IP 位址。Firefly III 已尝试使用 <a href="https://www.ipify.org/">ipify 服务</a> 自动填入，请确认此 IP 系正确的，否则导入将失败。',
    'prereq_ynab_title'                   => '从 YNAB 导入的先决条件',
    'prereq_ynab_text'                    => '为了能够从 YNAB 下载交易，请在您的 <a href="https://app.youneedabudget.com/settings/developer"> 开发人员设置页 </a> 上建立一个新的应用程式，并在此页面上输入客户端 ID 和密码。',
    'prereq_ynab_redirect'                => '若要完成设定，前往以下位于 <a href="https://app.youneedabudget.com/settings/developer">开发者设定页面</a> 中 "Redirect URI(s)" 的网址。',
    'callback_not_tls'                    => 'Firefly III 侦测到以下回呼 URI。您的伺服器似乎没有设定成 TLS-连接 (HTTP)。YNAB 不会接受此 URI，你可以继续导入 (因为 Firefly III 可能是错的)，但请记住这一点。',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => '假 API 金钥存储成功！',
    'prerequisites_saved_for_spectre'     => '应用程式 ID 与密钥已储存！',
    'prerequisites_saved_for_bunq'        => 'API 金钥与 IP 已储存！',
    'prerequisites_saved_for_ynab'        => 'YNAB 客户 ID 与密钥已储存！',

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
    'job_config_bunq_accounts_title'      => 'bunq 帐户',
    'job_config_bunq_accounts_text'       => '这些是与您 bunq 帐户关联的帐户，请选择您所欲导入的帐户以及其必须导入的交易。',
    'bunq_no_mapping'                     => '您似乎没有选择任何帐户。',
    'should_download_config'              => '您应该为此工作下载 <a href=":route">设定档</a>，可更俾利为来导入。',
    'share_config_file'                   => '如果您已自公有银行导入资料，您应该 <a href="https://github.com/firefly-iii/import-configurations/wiki">分享您的设定档</a> 俾利其他使用者导入他们的资料。分享您的设定档并不会暴露您的财务细节。',
    'job_config_bunq_apply_rules'         => '套用规则',
    'job_config_bunq_apply_rules_text'    => '预设下，您的规则会被套用至此次导入惯常程序中所建立的交易。若您不希望如此，请取消选取此核选方块。',
    'bunq_savings_goal'                   => '储蓄目标：:amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => '已关闭 bunq 帐号',

    'ynab_account_closed'                  => '帐户已关闭！',
    'ynab_account_deleted'                 => '帐户已删除！',
    'ynab_account_type_savings'            => '储蓄帐户',
    'ynab_account_type_checking'           => '支票帐户',
    'ynab_account_type_cash'               => '现金帐户',
    'ynab_account_type_creditCard'         => '信用卡',
    'ynab_account_type_lineOfCredit'       => '信用额度',
    'ynab_account_type_otherAsset'         => '其他资产帐户',
    'ynab_account_type_otherLiability'     => '其他债务',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => '商业帐户',
    'ynab_account_type_investmentAccount'  => '投资帐户',
    'ynab_account_type_mortgage'           => '抵押',
    'ynab_do_not_import'                   => '(不导入)',
    'job_config_ynab_apply_rules'          => '套用规则',
    'job_config_ynab_apply_rules_text'     => '预设下，您的规则会被套用至此次导入惯常程序中所建立的交易。若您不希望如此，请取消选取此核选方块。',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => '选择您的预算',
    'job_config_ynab_select_budgets_text'  => '您有 :count 笔储存于 YNAB 的预算，请选择以供 Firefly III 导入其中交易纪录。',
    'job_config_ynab_no_budgets'           => '没有可被导入的预算。',
    'ynab_no_mapping'                      => '您似乎没有选择任何欲导入的帐户。',
    'job_config_ynab_bad_currency'         => '您无法自以下预算导入，因为您没有与这些预算使用相同货币的帐户。',
    'job_config_ynab_accounts_title'       => '选择帐户',
    'job_config_ynab_accounts_text'        => '以下有您可用于此预算的帐户，请选择您欲导入的帐户以及交易资料储存的地方。',


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

    //job configuration for finTS
    'fints_connection_failed'              => '尝试连接至您的银行时发生1个错误，请确定您所有键入的资料均正确。原始错误讯息：:originalError',

    'job_config_fints_url_help'       => '例如 https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => '对多数银行而言，这是你的帐号。',
    'job_config_fints_port_help'      => '预设埠号为 443。',
    'job_config_fints_account_help'   => '选择您欲导入交易的银行帐户。',
    'job_config_local_account_help'   => '选择对应您上方所选银行帐户的 Firefly III 帐户。',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => '在 ING 汇出中建立更好的描述',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => '删除 SNS / Volksbank 汇出档案中的英文引号',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => '修正 ABN AMRO 档案中的潜在问题',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => '修正 Rabobank 档案中的潜在问题',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => '修正 PC 档案中的潜在问题',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Fixes potential problems with Belfius files',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => '导入设定 (3/4) - 定义每个栏的角色',
    'job_config_roles_text'           => '在您 CSV 档案中的每个栏均含某些资料，请说明系核种资料供导入器参照。用以「映射」资料的选项，即您将连结每个栏中的资料至您资料库的一个值。一个常见的「已映射」的栏，是包含 IBAN 相对帐户的栏，这便可轻易地媒合至您资料库既存的 IBAN 帐户。',
    'job_config_roles_submit'         => '继续',
    'job_config_roles_column_name'    => '栏名',
    'job_config_roles_column_example' => '栏的范例资料',
    'job_config_roles_column_role'    => '栏资料涵义',
    'job_config_roles_do_map_value'   => '映射这些数值',
    'job_config_roles_no_example'     => '无范例资料可用',
    'job_config_roles_fa_warning'     => '如果您将一个栏标记为外币金额、您亦须设定该栏外币为何。',
    'job_config_roles_rwarning'       => '请至少将一个栏标示为金额-栏，亦建议为描述、日期与对应帐户选择栏。',
    'job_config_roles_colum_count'    => '栏',
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

    // column roles for CSV import:
    'column__ignore'                  => '(忽略此栏)',
    'column_account-iban'             => '资产帐户 (IBAN)',
    'column_account-id'               => '资产帐户 ID (与 FF3 相符)',
    'column_account-name'             => '资产帐户 (名称)',
    'column_account-bic'              => '资产帐户 (BIC)',
    'column_amount'                   => '金额',
    'column_amount_foreign'           => '金额 (以外币计)',
    'column_amount_debit'             => '金额 (债务栏)',
    'column_amount_credit'            => '金额 (信用栏)',
    'column_amount_negated'           => '金额 (正负交换栏)',
    'column_amount-comma-separated'   => '金额 (以逗号作为进位分隔符号)',
    'column_bill-id'                  => '帐单 ID (与 FF3 相符)',
    'column_bill-name'                => '帐单名称',
    'column_budget-id'                => '预算 ID (与 FF3 相符)',
    'column_budget-name'              => '预算名称',
    'column_category-id'              => '分类 ID (与 FF3 相符)',
    'column_category-name'            => '分类名称',
    'column_currency-code'            => '货币代码 (ISO 4217)',
    'column_foreign-currency-code'    => '外币代码 (ISO 4217)',
    'column_currency-id'              => '货币 ID (与 FF3 相符)',
    'column_currency-name'            => '货币名称 (与 FF3 相符)',
    'column_currency-symbol'          => '货币符号 (与 FF3 相符)',
    'column_date-interest'            => '利率计算日期',
    'column_date-book'                => '交易预订日期',
    'column_date-process'             => '交易处理日期',
    'column_date-transaction'         => '日期',
    'column_date-due'                 => '交易到期日期',
    'column_date-payment'             => '交易付款日期',
    'column_date-invoice'             => '交易收据日期',
    'column_description'              => '描述',
    'column_opposing-iban'            => '对应帐户 (IBAN)',
    'column_opposing-bic'             => '对应帐户 (BIC)',
    'column_opposing-id'              => '对应帐户 ID (与 FF3 相符)',
    'column_external-id'              => '外部 ID',
    'column_opposing-name'            => '对应帐户 (名称)',
    'column_rabo-debit-credit'        => 'Rabobank 独有现金/信用卡指标',
    'column_ing-debit-credit'         => 'ING 独有 现金/信用卡 指标',
    'column_generic-debit-credit'     => 'Generic bank debit/credit indicator',
    'column_sepa_ct_id'               => 'SEPA end-to-end Identifier',
    'column_sepa_ct_op'               => 'SEPA Opposing Account Identifier',
    'column_sepa_db'                  => 'SEPA Mandate Identifier',
    'column_sepa_cc'                  => 'SEPA Clearing Code',
    'column_sepa_ci'                  => 'SEPA Creditor Identifier',
    'column_sepa_ep'                  => 'SEPA External Purpose',
    'column_sepa_country'             => 'SEPA Country Code',
    'column_sepa_batch_id'            => 'SEPA Batch ID',
    'column_tags-comma'               => '标签 (以逗号分隔)',
    'column_tags-space'               => '标签 (以空白键分隔)',
    'column_account-number'           => '资产帐户 (帐户号码)',
    'column_opposing-number'          => '相对帐户 (帐户号码)',
    'column_note'                     => '备注',
    'column_internal-reference'       => '内部参考',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];
