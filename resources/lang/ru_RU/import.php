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
    'status_wait_title'                    => 'Пожалуйста, подождите...',
    'status_wait_text'                     => 'Это сообщение исчезнет через мгновение.',
    'status_fatal_title'                   => 'Произошла критическая ошибка',
    'status_fatal_text'                    => 'Произошла фатальная ошибка, из-за которой невозможно восстановить процедуру импорта. Пожалуйста, ознакомьтесь с пояснением в красном блоке ниже.',
    'status_fatal_more'                    => 'Если ошибка вызывает тайм-аут, импорт остановится на полпути. Для некоторых конфигураций серверов это означает, что сервер остановился, хотя импорт продолжает работать в фоновом режиме. Чтобы проверить, так ли это, проверьте лог-файл. Если проблема не устранена, попробуйте запустить импорт из командной строки.',
    'status_ready_title'                   => 'Импорт готов к запуску',
    'status_ready_text'                    => 'Импорт готов к запуску. Все необходимые настройки были сделаны. Пожалуйста, загрузите файл конфигурации. Это поможет повторно запустить импорт, если что-то пойдет не так, как планировалось. Чтобы непосредственно запустить импорт, вы можете либо выполнить следующую команду в консоли, либо запустить веб-импорт. В зависимости от вашей конфигурации импорт с помощью консоли может быть более информативен.',
    'status_ready_noconfig_text'           => 'Импорт готов к запуску. Все необходимые настройки были сделаны. Чтобы непосредственно запустить импорт, вы можете либо выполнить следующую команду в консоли, либо запустить веб-импорт. В зависимости от вашей конфигурации импорт с помощью консоли может быть более информативен.',
    'status_ready_config'                  => 'Загрузить конфигурацию',
    'status_ready_start'                   => 'Начать импорт',
    'status_ready_share'                   => 'Пожалуйста, рассмотрите возможность загрузки вашей конфигурации в <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">центр импорта конфигураций</a></strong>. Это позволит другим пользователям Firefly III проще импортировать свои файлы.',
    'status_job_new'                       => 'Новая задача.',
    'status_job_configuring'               => 'Импорт настроен.',
    'status_job_configured'                => 'Импорт настроен.',
    'status_job_running'                   => 'Импорт запущен. Пожалуйста, подождите...',
    'status_job_error'                     => 'Это задание вызвало ошибку.',
    'status_job_finished'                  => 'Импорт завершен!',
    'status_running_title'                 => 'Выполняется импорт',
    'status_running_placeholder'           => 'Пожалуйста, дождитесь, пока страница обновится...',
    'status_finished_title'                => 'Процедура импорта завершена',
    'status_finished_text'                 => 'Ваши данные были импортированы.',
    'status_errors_title'                  => 'Ошибки во время импорта',
    'status_errors_single'                 => 'Во время импорта произошла ошибка. Однако, она не привела к фатальным последствиям.',
    'status_errors_multi'                  => 'Во время импорта произошли ошибки. Однако, они не привели к фатальным последствиям.',
    'status_bread_crumb'                   => 'Статус импорта',
    'status_sub_title'                     => 'Статус импорта',
    'config_sub_title'                     => 'Настройте свой импорт',
    'status_finished_job'                  => 'Всего :count транзакций было импортировано. Они могу быть найдены по метке <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III не собрал никаких журналов из вашего файла импорта.',
    'import_with_key'                      => 'Импорт с ключем \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Настройка импорта (1/4) - Загрузите ваш файл',
    'file_upload_text'                     => 'Эта процедура поможет вам импортировать файлы из вашего банка в Firefly III. Пожалуйста, прочитайте справку, доступную в правом верхнем углу этой страницы.',
    'file_upload_fields'                   => 'Поля',
    'file_upload_help'                     => 'Выберите файл',
    'file_upload_config_help'              => 'Если вы ранее импортировали данные в Firefly III, у вас может быть файл конфигурации, который позволит вам загрузить готовые настойки. Для некоторых банков другие пользователи любезно предоставили свои <a href="https://github.com/firefly-iii/import-configurations/wiki">файлы конфигурации</a>',
    'file_upload_type_help'                => 'Выберите тип загружаемого файла',
    'file_upload_submit'                   => 'Загрузить файлы',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (значения, разделенные запятыми)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Настройка импорта (2/4) - Основные настройки CSV-импорта',
    'csv_initial_text'                     => 'Чтобы импорт данных прошёл успешно, пожалуйста проверьте несколько параметров.',
    'csv_initial_box'                      => 'Основные параметры импорта CSV',
    'csv_initial_box_title'                => 'Основные параметры импорта CSV',
    'csv_initial_header_help'              => 'Установите этот флажок, если первая строка CSV-файла содержит заголовки столбцов.',
    'csv_initial_date_help'                => 'Формат даты и времени в вашем CSV-файле. Придерживайтесь формата, описанного <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">на этой</a> странице. По умолчанию дату будут анализироваться на соответствие такому формату: :dateExample.',
    'csv_initial_delimiter_help'           => 'Выберите разделитель полей, который используется в вашем файле. Если вы не уверены, помните, что запятая - это самый безопасный вариант.',
    'csv_initial_import_account_help'      => 'Если ваш CSV-файл НЕ СОДЕРЖИТ информацию о ваших счетах, используйте этот выпадающий список, чтобы выбрать, к какому счёту относятся транзакции в CVS-файле.',
    'csv_initial_submit'                   => 'Перейти к шагу 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Применить правила',
    'file_apply_rules_description'         => 'Применить ваши правила. Обратите внимание, что это значительно замедляет импорт.',
    'file_match_bills_title'               => 'Соответствующие счета к оплате',
    'file_match_bills_description'         => 'Сопоставление свои счета к оплате с вновь созданными расходами. Помните, что это может существенно замедлить импорт.',

    // file, roles config
    'csv_roles_title'                      => 'Настройка импорта (3/4). Определите роль каждого столбца',
    'csv_roles_text'                       => 'Каждый столбец в файле CSV содержит определённые данные. Укажите, какие данные должен ожидать импортер. Опция «сопоставить» данные привяжет каждую запись, найденную в столбце, к значению в вашей базе данных. Часто отображаемый столбец - это столбец, содержащий IBAN спонсорского счёта. Его можно легко сопоставить с существующим в вашей базе данных IBAN.',
    'csv_roles_table'                      => 'Таблица',
    'csv_roles_column_name'                => 'Название столбца',
    'csv_roles_column_example'             => 'Пример данных в столбце',
    'csv_roles_column_role'                => 'Значение в столбце',
    'csv_roles_do_map_value'               => 'Сопоставьте эти значения',
    'csv_roles_column'                     => 'Столбец',
    'csv_roles_no_example_data'            => 'Нет доступных данных для примера',
    'csv_roles_submit'                     => 'Перейти к шагу 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'Пожалуйста, отметьте хотя бы один столбец как столбец с суммой. Также целесообразно выбрать столбец для описания, даты и спонсорского счёта.',
    'foreign_amount_warning'               => 'Если вы пометите этот столбец, как содержащий сумму в иностранной валюте, вы также должны указать столбец, который указывает, какая именно это валюта.',
    // file, map data
    'file_map_title'                       => 'Настройки импорта (4/4) - Сопоставление данных импорта с данными Firefly III',
    'file_map_text'                        => 'В следующих таблицах значение слева отображает информацию, найденную в загруженном файле. Ваша задача - сопоставить это значение (если это возможно) со значением, уже имеющимся в вашей базе данных. Firefly будет придерживаться этого сопоставления. Если для сопоставления нет значения или вы не хотите отображать определённое значение, ничего не выбирайте.',
    'file_map_field_value'                 => 'Значение поля',
    'file_map_field_mapped_to'             => 'Сопоставлено с',
    'map_do_not_map'                       => '(не сопоставлено)',
    'file_map_submit'                      => 'Начать импорт',
    'file_nothing_to_map'                  => 'В вашем файле нет данных, которые можно сопоставить с существующими значениями. Нажмите «Начать импорт», чтобы продолжить.',

    // map things.
    'column__ignore'                       => '(игнорировать этот столбец)',
    'column_account-iban'                  => 'Счет актива (IBAN)',
    'column_account-id'                    => 'ID основного счёта (соответствующий FF3)',
    'column_account-name'                  => 'Основной счёт (название)',
    'column_amount'                        => 'Сумма',
    'column_amount_foreign'                => 'Сумма (в иностранной валюте)',
    'column_amount_debit'                  => 'Сумма (столбец с дебетом)',
    'column_amount_credit'                 => 'Сумма (столбец с кредитом)',
    'column_amount-comma-separated'        => 'Сумма (запятая как десятичный разделитель)',
    'column_bill-id'                       => 'ID счёта на оплату (соответствующий FF3)',
    'column_bill-name'                     => 'Название счета',
    'column_budget-id'                     => 'ID бюджета (соответствующий FF3)',
    'column_budget-name'                   => 'Название бюджета',
    'column_category-id'                   => 'ID категории (соответствующий FF3)',
    'column_category-name'                 => 'Название категории',
    'column_currency-code'                 => 'Код валюты (ISO 4217)',
    'column_foreign-currency-code'         => 'Код иностранной валюты (ISO 4217)',
    'column_currency-id'                   => 'ID валюты (соответствующий FF3)',
    'column_currency-name'                 => 'Название валюты (соответствующее FF3)',
    'column_currency-symbol'               => 'Символ валюты (соответствующий FF3)',
    'column_date-interest'                 => 'Дата расчета процентов',
    'column_date-book'                     => 'Дата записи транзакции',
    'column_date-process'                  => 'Дата обработки транзакции',
    'column_date-transaction'              => 'Дата',
    'column_description'                   => 'Описание',
    'column_opposing-iban'                 => 'Спонсорский счёт (IBAN)',
    'column_opposing-id'                   => 'ID спонсорского счёта (соответствующий FF3)',
    'column_external-id'                   => 'Внешний ID',
    'column_opposing-name'                 => 'Спонсорский счёт (название)',
    'column_rabo-debit-credit'             => 'Индикатор дебита/кредита, специфичный для Rabobank',
    'column_ing-debit-credit'              => 'Индикатор дебита/кредита, специфичный для ING',
    'column_sepa-ct-id'                    => 'Идентификационный номер SEPA Credit Transfer',
    'column_sepa-ct-op'                    => 'Спонсорский счет SEPA Credit Transfer',
    'column_sepa-db'                       => 'Прямой дебет SEPA',
    'column_tags-comma'                    => 'Метки (разделены запятыми)',
    'column_tags-space'                    => 'Метки (разделены пробелами)',
    'column_account-number'                => 'Основной счёт (номер счёта)',
    'column_opposing-number'               => 'Спонсорский счёт (номер счёта)',
    'column_note'                          => 'Примечания',

    // prerequisites
    'prerequisites'                        => 'Требования',

    // bunq
    'bunq_prerequisites_title'             => 'Требования для импорта из bunq',
    'bunq_prerequisites_text'              => 'Чтобы импортировать из bunq, вам нужно получить ключ API. Вы можете сделать это через приложение.',

    // Spectre
    'spectre_title'                        => 'Импорт с использованием Spectre',
    'spectre_prerequisites_title'          => 'Требования для импорта с использованием Spectre',
    'spectre_prerequisites_text'           => 'Чтобы импортировать данные с помощью API-интерфейса Spectre, вы должны предоставить Firefly III два секретных значения. Их можно найти на странице <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'                => 'Импорт будет работать только если вы введёте этот ключ безопасности на своей <a href="https://www.saltedge.com/clients/security/edit">странице</a>.',
    'spectre_accounts_title'               => 'Выберите счёта, с которых будет производиться импорт',
    'spectre_accounts_text'                => 'Каждый счёт в списке слева был найден в в Spectre и может быть импортирован в Firefly III. Выберите основной счёт, на котором нужно сохранить импортируемые транзакции. Если вы не хотите импортировать данные с какого-либо конкретного счёта, снимите соответствующий флажок.',
    'spectre_do_import'                    => 'Да, импортировать с этого счёта',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Статус',
    'spectre_extra_key_card_type'          => 'Тип карты',
    'spectre_extra_key_account_name'       => 'Название счёта',
    'spectre_extra_key_client_name'        => 'Имя клиента',
    'spectre_extra_key_account_number'     => 'Номер счёта',
    'spectre_extra_key_blocked_amount'     => 'Заблокированная сумма',
    'spectre_extra_key_available_amount'   => 'Доступная сумма',
    'spectre_extra_key_credit_limit'       => 'Кредитный лимит',
    'spectre_extra_key_interest_rate'      => 'Процентная ставка',
    'spectre_extra_key_expiry_date'        => 'Дата окончания',
    'spectre_extra_key_open_date'          => 'Дата открытия',
    'spectre_extra_key_current_time'       => 'Текущее время',
    'spectre_extra_key_current_date'       => 'Текущая дата',
    'spectre_extra_key_cards'              => 'Карты',
    'spectre_extra_key_units'              => 'Единицы',
    'spectre_extra_key_unit_price'         => 'Цена за единицу',
    'spectre_extra_key_transactions_count' => 'Количество транзакций',

    // various other strings:
    'imported_from_account'                => 'Импортировано со счёта ":account"',
];

