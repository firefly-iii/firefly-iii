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
    'index_breadcrumb'                    => 'Импорт данных в Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prerequisites for the fake import provider',
    'prerequisites_breadcrumb_spectre'    => 'Требования для Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Конфигурация для bunq',
    'prerequisites_breadcrumb_ynab'       => 'Требования для YNAB',
    'job_configuration_breadcrumb'        => 'Конфигурация для ":key"',
    'job_status_breadcrumb'               => 'Статус импорта для ":key"',
    'disabled_for_demo_user'              => 'отключено в демо-версии',

    // index page:
    'general_index_intro'                 => 'Добро пожаловать в инструмент импорта Firefly III. Существует несколько способов импорта данных в Firefly III, отображаемых здесь в виде кнопок.',

    // import provider strings (index):
    'button_fake'                         => 'Поддельный (демо) импорт',
    'button_file'                         => 'Импортировать файл',
    'button_bunq'                         => 'Импорт из bunq',
    'button_spectre'                      => 'Импорт с использованием Spectre',
    'button_plaid'                        => 'Импорт с использованием Plaid',
    'button_yodlee'                       => 'Импорт с использованием Yodlee',
    'button_quovo'                        => 'Импорт с использованием Quovo',
    'button_ynab'                         => 'Импорт из \'You Need A Budget\'',
    'button_fints'                        => 'Import using FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Import prerequisites',
    'need_prereq_intro'                   => 'Some import methods need your attention before they can be used. For example, they might require special API keys or application secrets. You can configure them here. The icon indicates if these prerequisites have been met.',
    'do_prereq_fake'                      => 'Prerequisites for the fake provider',
    'do_prereq_file'                      => 'Prerequisites for file imports',
    'do_prereq_bunq'                      => 'Prerequisites for imports from bunq',
    'do_prereq_spectre'                   => 'Prerequisites for imports using Spectre',
    'do_prereq_plaid'                     => 'Prerequisites for imports using Plaid',
    'do_prereq_yodlee'                    => 'Prerequisites for imports using Yodlee',
    'do_prereq_quovo'                     => 'Prerequisites for imports using Quovo',
    'do_prereq_ynab'                      => 'Prerequisites for imports from YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Prerequisites for an import from the fake import provider',
    'prereq_fake_text'                    => 'This fake provider requires a fake API key. It must be 32 characters long. You can use this one: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prerequisites for an import using the Spectre API',
    'prereq_spectre_text'                 => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Likewise, the Spectre API needs to know the public key you see below. Without it, it will not recognize you. Please enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_bunq_title'                   => 'Prerequisites for an import from bunq',
    'prereq_bunq_text'                    => 'In order to import from bunq, you need to obtain an API key. You can do this through the app. Please note that the import function for bunq is in BETA. It has only been tested against the sandbox API.',
    'prereq_bunq_ip'                      => 'bunq requires your externally facing IP address. Firefly III has tried to fill this in using <a href="https://www.ipify.org/">the ipify service</a>. Make sure this IP address is correct, or the import will fail.',
    'prereq_ynab_title'                   => 'Требования для импорта из YNAB',
    'prereq_ynab_text'                    => 'Для успешной загрузки транзакций с YNAB, пожалуйста, создайте новое приложение на вашей <a href="https://app.youneedabudget.com/settings/developer">странице Настроек разработчика</a> и введите ID клиента и секретный ключ на этой странице.',
    'prereq_ynab_redirect'                => 'To complete the configuration, enter the following URL at the <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> under the "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III has detected the following callback URI. It seems your server is not set up to accept TLS-connections (https). YNAB will not accept this URI. You may continue with the import (because Firefly III could be wrong) but please keep this in mind.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Ключ Fake API успешно сохранен!',
    'prerequisites_saved_for_spectre'     => 'App ID и секретный ключ сохранены!',
    'prerequisites_saved_for_bunq'        => 'API-ключ и IP сохранены!',
    'prerequisites_saved_for_ynab'        => 'ID клиента YNAB и секрет сохранены!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Job configuration - apply your rules?',
    'job_config_apply_rules_text'         => 'Once the fake provider has run, your rules can be applied to the transactions. This adds time to the import.',
    'job_config_input'                    => 'Ваш ввод',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Введите имя альбома',
    'job_config_fake_artist_text'         => 'Many import routines have a few configuration steps you must go through. In the case of the fake import provider, you must answer some weird questions. In this case, enter "David Bowie" to continue.',
    'job_config_fake_song_title'          => 'Введите название песни',
    'job_config_fake_song_text'           => 'Mention the song "Golden years" to continue with the fake import.',
    'job_config_fake_album_title'         => 'Введите название альбома',
    'job_config_fake_album_text'          => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Настройка импорта (1/4) - Загрузите ваш файл',
    'job_config_file_upload_text'         => 'This routine will help you import files from your bank into Firefly III. ',
    'job_config_file_upload_help'         => 'Выберите ваш файл. Убедитесь, что он в кодировке UTF-8.',
    'job_config_file_upload_config_help'  => 'Если вы ранее импортировали данные в Firefly III, у вас может быть файл конфигурации, который позволит вам загрузить готовые настойки. Для некоторых банков другие пользователи любезно предоставили свои <a href="https://github.com/firefly-iii/import-configurations/wiki">файлы конфигурации</a>',
    'job_config_file_upload_type_help'    => 'Выберите тип загружаемого файла',
    'job_config_file_upload_submit'       => 'Загрузить файлы',
    'import_file_type_csv'                => 'CSV (значения, разделенные запятыми)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Загруженный вами файл использует кодировку, отличную от UTF-8 или ASCII. Firefly III не может обработать такой файл. Пожалуйста используйте Notepad++ или Sublime что бы сконвертировать ваш файл в UTF-8.',
    'job_config_uc_title'                 => 'Настройка импорта (2/4) - Основные настройки CSV-импорта',
    'job_config_uc_text'                  => 'Чтобы импорт данных прошёл успешно, пожалуйста проверьте несколько параметров.',
    'job_config_uc_header_help'           => 'Установите этот флажок, если первая строка вашего CSV-файла содержит заголовки столбцов.',
    'job_config_uc_date_help'             => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'          => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'     => 'Применить правила',
    'job_config_uc_apply_rules_text'      => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'       => 'Параметры, специфичные для платформы',
    'job_config_uc_specifics_txt'         => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                => 'Продолжить',
    'invalid_import_account'              => 'You have selected an invalid account to import into.',
    'import_liability_select'             => 'Liability',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Выберите свой логин',
    'job_config_spectre_login_text'       => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'         => 'Активный',
    'spectre_login_status_inactive'       => 'Неактивный',
    'spectre_login_status_disabled'       => 'Отключён',
    'spectre_login_new_login'             => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'   => 'Выберите счета, с которых будет производиться импорт',
    'job_config_spectre_accounts_text'    => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_do_not_import'               => '(не импортировать)',
    'spectre_no_mapping'                  => 'Похоже, вы не выбрали ни одного счёта для импорта.',
    'imported_from_account'               => 'Импортировано со счёта ":account"',
    'spectre_account_with_number'         => 'Cчёт :number',
    'job_config_spectre_apply_rules'      => 'Применить правила',
    'job_config_spectre_apply_rules_text' => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'счета bunq',
    'job_config_bunq_accounts_text'       => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    'bunq_no_mapping'                     => 'It seems you have not selected any accounts.',
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',
    'job_config_bunq_apply_rules'         => 'Применить правила',
    'job_config_bunq_apply_rules_text'    => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',
    'bunq_savings_goal'                   => 'Savings goal: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Closed bunq account',

    'ynab_account_closed'                  => 'Счёт закрыт!',
    'ynab_account_deleted'                 => 'Счёт удалён!',
    'ynab_account_type_savings'            => 'сберегательный счёт',
    'ynab_account_type_checking'           => 'checking account',
    'ynab_account_type_cash'               => 'наличный счёт',
    'ynab_account_type_creditCard'         => 'кредитная карта',
    'ynab_account_type_lineOfCredit'       => 'кредитная линия',
    'ynab_account_type_otherAsset'         => 'другой счёт активов',
    'ynab_account_type_otherLiability'     => 'other liabilities',
    'ynab_account_type_payPal'             => 'PayPal',
    'ynab_account_type_merchantAccount'    => 'merchant account',
    'ynab_account_type_investmentAccount'  => 'investment account',
    'ynab_account_type_mortgage'           => 'ипотека',
    'ynab_do_not_import'                   => '(не импортировать)',
    'job_config_ynab_apply_rules'          => 'Применить правила',
    'job_config_ynab_apply_rules_text'     => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Выберите бюджет',
    'job_config_ynab_select_budgets_text'  => 'You have :count budgets stored at YNAB. Please select the one from which Firefly III will import the transactions.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'You cannot import from the following budget(s), because you do not have accounts with the same currency as these budgets.',
    'job_config_ynab_accounts_title'       => 'Выберите аккаунты',
    'job_config_ynab_accounts_text'        => 'You have the following accounts available in this budget. Please select from which accounts you want to import, and where the transactions should be stored.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Статус',
    'spectre_extra_key_card_type'          => 'Тип карты',
    'spectre_extra_key_account_name'       => 'Название счёта',
    'spectre_extra_key_client_name'        => 'Имя клиента',
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

    //job configuration for finTS
    'fints_connection_failed'              => 'An error occurred while trying to connecting to your bank. Please make sure that all the data you entered is correct. Original error message: :originalError',

    'job_config_fints_url_help'       => 'E.g. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'For many banks this is your account number.',
    'job_config_fints_port_help'      => 'The default port is 443.',
    'job_config_fints_account_help'   => 'Choose the bank account for which you want to import transactions.',
    'job_config_local_account_help'   => 'Choose the Firefly III account corresponding to your bank account chosen above.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Create better descriptions in ING exports',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Trim quotes from SNS / Volksbank export files',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Fixes potential problems with ABN AMRO files',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Fixes potential problems with Rabobank files',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Fixes potential problems with PC files',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Fixes potential problems with Belfius files',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Настройка импорта (3/4). Определите роль каждого столбца',
    'job_config_roles_text'           => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'job_config_roles_submit'         => 'Продолжить',
    'job_config_roles_column_name'    => 'Название столбца',
    'job_config_roles_column_example' => 'Пример данных в столбце',
    'job_config_roles_column_role'    => 'Значение в столбце',
    'job_config_roles_do_map_value'   => 'Сопоставьте эти значения',
    'job_config_roles_no_example'     => 'Нет доступных данных для примера',
    'job_config_roles_fa_warning'     => 'Если вы пометите этот столбец, как содержащий сумму в иностранной валюте, вы также должны указать столбец, который указывает, какая именно это валюта.',
    'job_config_roles_rwarning'       => 'Пожалуйста, отметьте хотя бы один столбец как столбец с суммой. Также целесообразно выбрать столбец для описания, даты и спонсорского счёта.',
    'job_config_roles_colum_count'    => 'Столбец',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Настройки импорта (4/4) - Сопоставление данных импорта с данными Firefly III',
    'job_config_map_text'             => 'В следующих таблицах значение слева отображает информацию, найденную в загруженном файле. Ваша задача - сопоставить это значение (если это возможно) со значением, уже имеющимся в вашей базе данных. Firefly будет придерживаться этого сопоставления. Если для сопоставления нет значения или вы не хотите отображать определённое значение, ничего не выбирайте.',
    'job_config_map_nothing'          => 'В вашем файле нет данных, которые можно сопоставить с существующими значениями. Нажмите «Начать импорт», чтобы продолжить.',
    'job_config_field_value'          => 'Значение поля',
    'job_config_field_mapped'         => 'Сопоставлено с',
    'map_do_not_map'                  => '(не сопоставлено)',
    'job_config_map_submit'           => 'Начать импорт',


    // import status page:
    'import_with_key'                 => 'Импорт с ключем \':key\'',
    'status_wait_title'               => 'Пожалуйста, подождите...',
    'status_wait_text'                => 'Это сообщение исчезнет через мгновение.',
    'status_running_title'            => 'Выполняется импорт',
    'status_job_running'              => 'Пожалуйста, подождите, идёт импорт...',
    'status_job_storing'              => 'Пожалуйста, подождите, идёт сохранение данных...',
    'status_job_rules'                => 'Пожалуйста, подождите, выполняются правила...',
    'status_fatal_title'              => 'Фатальная ошибка',
    'status_fatal_text'               => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'               => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'           => 'Импорт завершён',
    'status_finished_text'            => 'Импорт завершен!',
    'finished_with_errors'            => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'           => 'Неизвестный результат импорта',
    'result_no_transactions'          => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'          => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'        => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',


    // general errors and warnings:
    'bad_job_status'                  => 'To access this page, your import job cannot have status ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(игнорировать этот столбец)',
    'column_account-iban'             => 'Счет актива (IBAN)',
    'column_account-id'               => 'ID основного счёта (соответствующий FF3)',
    'column_account-name'             => 'Основной счёт (название)',
    'column_account-bic'              => 'Asset account (BIC)',
    'column_amount'                   => 'Сумма',
    'column_amount_foreign'           => 'Сумма (в иностранной валюте)',
    'column_amount_debit'             => 'Сумма (столбец с дебетом)',
    'column_amount_credit'            => 'Сумма (столбец с кредитом)',
    'column_amount_negated'           => 'Amount (negated column)',
    'column_amount-comma-separated'   => 'Сумма (запятая как десятичный разделитель)',
    'column_bill-id'                  => 'ID счёта на оплату (соответствующий FF3)',
    'column_bill-name'                => 'Название счета',
    'column_budget-id'                => 'ID бюджета (соответствующий FF3)',
    'column_budget-name'              => 'Название бюджета',
    'column_category-id'              => 'ID категории (соответствующий FF3)',
    'column_category-name'            => 'Название категории',
    'column_currency-code'            => 'Код валюты (ISO 4217)',
    'column_foreign-currency-code'    => 'Код иностранной валюты (ISO 4217)',
    'column_currency-id'              => 'ID валюты (соответствующий FF3)',
    'column_currency-name'            => 'Название валюты (соответствующее FF3)',
    'column_currency-symbol'          => 'Символ валюты (соответствующий FF3)',
    'column_date-interest'            => 'Дата расчета процентов',
    'column_date-book'                => 'Дата записи транзакции',
    'column_date-process'             => 'Дата обработки транзакции',
    'column_date-transaction'         => 'Дата',
    'column_date-due'                 => 'Дата транзакции',
    'column_date-payment'             => 'Дата оплаты',
    'column_date-invoice'             => 'Дата выставления счёта',
    'column_description'              => 'Описание',
    'column_opposing-iban'            => 'Спонсорский счёт (IBAN)',
    'column_opposing-bic'             => 'Спонсорский счёт (BIC)',
    'column_opposing-id'              => 'ID спонсорского счёта (соответствующий FF3)',
    'column_external-id'              => 'Внешний ID',
    'column_opposing-name'            => 'Спонсорский счёт (название)',
    'column_rabo-debit-credit'        => 'Индикатор дебита/кредита, специфичный для Rabobank',
    'column_ing-debit-credit'         => 'Индикатор дебита/кредита, специфичный для ING',
    'column_generic-debit-credit'     => 'Generic bank debit/credit indicator',
    'column_sepa_ct_id'               => 'SEPA end-to-end Identifier',
    'column_sepa_ct_op'               => 'SEPA Opposing Account Identifier',
    'column_sepa_db'                  => 'SEPA Mandate Identifier',
    'column_sepa_cc'                  => 'SEPA Clearing Code',
    'column_sepa_ci'                  => 'SEPA Creditor Identifier',
    'column_sepa_ep'                  => 'SEPA External Purpose',
    'column_sepa_country'             => 'SEPA Country Code',
    'column_sepa_batch_id'            => 'SEPA Batch ID',
    'column_tags-comma'               => 'Метки (разделены запятыми)',
    'column_tags-space'               => 'Метки (разделены пробелами)',
    'column_account-number'           => 'Основной счёт (номер счёта)',
    'column_opposing-number'          => 'Спонсорский счёт (номер счёта)',
    'column_note'                     => 'Примечания',
    'column_internal-reference'       => 'Внутренняя ссылка',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];
