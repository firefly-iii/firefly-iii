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
    'prerequisites_breadcrumb_fake'       => 'Настройки для импорта через демо-провайдера',
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
    'button_fints'                        => 'Импорт с использованием FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Импорт настроек',
    'need_prereq_intro'                   => 'Некоторые методы импорта требуют вашего внимания, прежде чем они могут быть использованы. Например, они могут потребовать специальных ключей API или секретов приложения. Вы можете настроить их здесь. Иконка указывает, что эти предварительные условия были выполнены.',
    'do_prereq_fake'                      => 'Настройки для демо-провайдера',
    'do_prereq_file'                      => 'Настройки для импорта файлов',
    'do_prereq_bunq'                      => 'Настройки для импорта из bunq',
    'do_prereq_spectre'                   => 'Настройки для импорта из Spectre',
    'do_prereq_plaid'                     => 'Настройки для импорта из Plaid',
    'do_prereq_yodlee'                    => 'Настройки для импорта из Yodlee',
    'do_prereq_quovo'                     => 'Настройки для импорта из Quovo',
    'do_prereq_ynab'                      => 'Настройки для импорта из YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Настройки для импорта из демо-провайдера',
    'prereq_fake_text'                    => 'Этот демо-провайдер требует фиктивный API-ключ. Его длина должна быть не менее 32 символов. Вы можете использовать этот ключ: 1234567890123456789090AA',
    'prereq_spectre_title'                => 'Настройки для импорта через Spectre API',
    'prereq_spectre_text'                 => 'Чтобы импортировать данные с помощью Spectre API (v4), вы должны предоставить Firefly III два секретных значения. Их можно найти на странице <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Точно так же, Spectre API должен знать открытый ключ, который вы видите ниже. Без него он не распознает вас. Пожалуйста, введите этот публичный ключ на вашей странице <a href="https://www.saltedge.com/clients/profile/secrets">секретов</a>.',
    'prereq_bunq_title'                   => 'Настройки для импорта из bunq',
    'prereq_bunq_text'                    => 'Чтобы импортировать из bunq, вам нужно получить ключ API. Вы можете сделать это через приложение. Обратите внимание, что функция импорта для bunq находится в бета-тестирования. Было протестировано только API песочницы (sandbox).',
    'prereq_bunq_ip'                      => 'для работы с bunq необходимо указать ваш внешний IP-адрес. Firefly III попытался узнать ваш адрес с помощью <a href="https://www.ipify.org/">службы ipify</a>. Убедитесь, что этот IP-адрес верен, иначе импорт не будет выполнен.',
    'prereq_ynab_title'                   => 'Требования для импорта из YNAB',
    'prereq_ynab_text'                    => 'Для успешной загрузки транзакций с YNAB, пожалуйста, создайте новое приложение на вашей <a href="https://app.youneedabudget.com/settings/developer">странице Настроек разработчика</a> и введите ID клиента и секретный ключ на этой странице.',
    'prereq_ynab_redirect'                => 'Для завершения настройки введите этот URL на странице <a href="https://app.youneedabudget.com/settings/developer">Developer Settings</a> в поле "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III обнаружил следующий URL-адрес обратной связи. Похоже, ваш сервер не настроен для принятия TLS-соединений (https). YNAB не примет этот URI. Вы можете продолжить импорт (потому что Firefly III может быть неправильным), но помните об этом.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Ключ Fake API успешно сохранен!',
    'prerequisites_saved_for_spectre'     => 'App ID и секретный ключ сохранены!',
    'prerequisites_saved_for_bunq'        => 'API-ключ и IP сохранены!',
    'prerequisites_saved_for_ynab'        => 'ID клиента YNAB и секрет сохранены!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Параметры работы - применить ваши правила?',
    'job_config_apply_rules_text'         => 'После запуска демо-провайдера, ваши правила могут применяться к транзакциям. Это увеличивает время импорта.',
    'job_config_input'                    => 'Ваш ввод',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Введите имя альбома',
    'job_config_fake_artist_text'         => 'Многие процедуры импорта имеют несколько шагов конфигурации, которые необходимо пройти. В случае демо-импорта, вы должны ответить на некоторые странные вопросы. В этом случае введите "David Bowie", чтобы продолжить.',
    'job_config_fake_song_title'          => 'Введите название песни',
    'job_config_fake_song_text'           => 'Упомяните песню "Золотые годы", чтобы продолжить демо-импорт.',
    'job_config_fake_album_title'         => 'Введите название альбома',
    'job_config_fake_album_text'          => 'Некоторые процедуры импорта требуют дополнительных данных в середине импорта. В случае демо-импорта вы должны ответить на некоторые странные вопросы. Введите "Station to station", чтобы продолжить.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Настройка импорта (1/4) - Загрузите ваш файл',
    'job_config_file_upload_text'         => 'Эта процедура поможет вам импортировать файлы из вашего банка в Firefly III. ',
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
    'job_config_uc_date_help'             => 'Формат даты и времени в вашем файле. Придерживайтесь формата, описанного <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">на этой</a> странице. По умолчанию даты будут анализироваться на соответствие такому формату: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Выберите разделитель полей, который используется в вашем файле. Если вы не уверены, помните, что запятая - это самый безопасный вариант.',
    'job_config_uc_account_help'          => 'Если ваш файл НЕ СОДЕРЖИТ информацию о ваших счётах, укажите счета для всех транзакций, выбрав подходящие из выпадающего списка.',
    'job_config_uc_apply_rules_title'     => 'Применить правила',
    'job_config_uc_apply_rules_text'      => 'Применять ваши правила к каждой импортированной транзакции. Обратите внимание, что это значительно замедляет импорт.',
    'job_config_uc_specifics_title'       => 'Параметры, специфичные для платформы',
    'job_config_uc_specifics_txt'         => 'Некоторые банки предоставляют плохо отформатированные файлы. Firefly III может исправить их автоматически. Если ваш банк поставляет такие файлы, но он не указан здесь, пожалуйста, откройте issue на GitHub.',
    'job_config_uc_submit'                => 'Продолжить',
    'invalid_import_account'              => 'Вы выбрали неверный счёт для импорта.',
    'import_liability_select'             => 'Обязательство',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Выберите свой логин',
    'job_config_spectre_login_text'       => 'Firefly III нашёл :count существующего логинов в вашем аккаунте Spectre. Какой из них вы хотите использовать для импорта?',
    'spectre_login_status_active'         => 'Активный',
    'spectre_login_status_inactive'       => 'Неактивный',
    'spectre_login_status_disabled'       => 'Отключён',
    'spectre_login_new_login'             => 'Зайдите с учётной записью другого банка, или одного из перечисленных банков, но с другой учётной записью.',
    'job_config_spectre_accounts_title'   => 'Выберите счета, с которых будет производиться импорт',
    'job_config_spectre_accounts_text'    => 'Вы выбрали ":name" (:country). У вас есть :count счетов у этого провайдера. Пожалуйста, выберите основной счёт Firefly III, на котором транзакции с этих счетов должны быть сохранены. Помните, чтобы импорт прошёл успешно, счёт Firefly III и счёт в банке ":name" должны быть в одной валюте.',
    'spectre_do_not_import'               => '(не импортировать)',
    'spectre_no_mapping'                  => 'Похоже, вы не выбрали ни одного счёта для импорта.',
    'imported_from_account'               => 'Импортировано со счёта ":account"',
    'spectre_account_with_number'         => 'Cчёт :number',
    'job_config_spectre_apply_rules'      => 'Применить правила',
    'job_config_spectre_apply_rules_text' => 'По умолчанию, ваши правила будут применены к транзакциям, созданным во время этой процедуры импорта. Если вы не хотите, чтобы это произошло, снимите этот флажок.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'счета bunq',
    'job_config_bunq_accounts_text'       => 'Эти счета связаны с вашей учётной записью bunq. Выберите счета, данные о о которых вы хотите импортировать, и счёт, на который будут импортированы транзакции.',
    'bunq_no_mapping'                     => 'Похоже, вы не выбрали ни одного счёта для импорта.',
    'should_download_config'              => 'Вы должны загрузить <a href=":route">файл конфигурации</a> для этого задания. Это облегчит будущий импорт.',
    'share_config_file'                   => 'Если вы импортировали данные из публичного банка, вам следует <a href="https://github.com/firefly-iii/import-configurations/wiki">поделиться своим конфигурационным файлом</a>, так что другие пользователи будут легко импортировать свои данные. Обмен конфигурационным файлом не приведёт к разглашению ваших финансовых данных.',
    'job_config_bunq_apply_rules'         => 'Применить правила',
    'job_config_bunq_apply_rules_text'    => 'По умолчанию, ваши правила будут применены к транзакциям, созданным во время этой процедуры импорта. Если вы не хотите, чтобы это произошло, снимите этот флажок.',
    'bunq_savings_goal'                   => 'Цель сбережений: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Закрытый аккаунт bunq',

    'ynab_account_closed'                  => 'Счёт закрыт!',
    'ynab_account_deleted'                 => 'Счёт удалён!',
    'ynab_account_type_savings'            => 'сберегательный счёт',
    'ynab_account_type_checking'           => 'проверка счёта',
    'ynab_account_type_cash'               => 'наличный счёт',
    'ynab_account_type_creditCard'         => 'кредитная карта',
    'ynab_account_type_lineOfCredit'       => 'кредитная линия',
    'ynab_account_type_otherAsset'         => 'другой счёт активов',
    'ynab_account_type_otherLiability'     => 'другие обязательства',
    'ynab_account_type_payPal'             => 'PayPal',
    'ynab_account_type_merchantAccount'    => 'торговый аккаунт',
    'ynab_account_type_investmentAccount'  => 'инвестиционный счёт',
    'ynab_account_type_mortgage'           => 'ипотека',
    'ynab_do_not_import'                   => '(не импортировать)',
    'job_config_ynab_apply_rules'          => 'Применить правила',
    'job_config_ynab_apply_rules_text'     => 'По умолчанию, ваши правила будут применены к транзакциям, созданным во время этой процедуры импорта. Если вы не хотите, чтобы это произошло, снимите этот флажок.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Выберите бюджет',
    'job_config_ynab_select_budgets_text'  => 'У вас есть :count бюджетов, хранящихся в YNAB. Пожалуйста, выберите из которых Firefly III импортирует транзакции.',
    'job_config_ynab_no_budgets'           => 'Нет доступных бюджетов для импорта.',
    'ynab_no_mapping'                      => 'Похоже, вы не выбрали ни одного счёта для импорта.',
    'job_config_ynab_bad_currency'         => 'Вы не можете импортировать из следующего бюджета(ов), потому что у вас нет счетов с той же валютой, что и эти бюджеты.',
    'job_config_ynab_accounts_title'       => 'Выберите аккаунты',
    'job_config_ynab_accounts_text'        => 'У вас есть следующие счета в этом бюджете. Пожалуйста, выберите из которых вы хотите импортировать счета и где транзакции должны быть сохранены.',


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
    'spectre_extra_key_units'              => 'Количество',
    'spectre_extra_key_unit_price'         => 'Цена за единицу',
    'spectre_extra_key_transactions_count' => 'Количество транзакций',

    //job configuration for finTS
    'fints_connection_failed'              => 'Произошла ошибка при попытке подключения к вашему банку. Убедитесь, что все введённые данные верны. Исходное сообщение об ошибке: :originalError',

    'job_config_fints_url_help'       => 'Например, https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Для многих банков это ваш номер счёта.',
    'job_config_fints_port_help'      => 'Порт по умолчанию 443.',
    'job_config_fints_account_help'   => 'Выберите банковский счёт, для которого вы хотите импортировать транзакции.',
    'job_config_local_account_help'   => 'Выберите счёт Firefly III, соответствующий вашему банковскому счёту, выбранному выше.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Создать более чёткое описание в экспорте ING',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Убрать кавычки из файлов экспорта SNS / Volksbank',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Исправлены потенциальные проблемы с файлами AMRO ABN',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Исправлены потенциальные проблемы с файлами Rabobank',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Исправлены потенциальные проблемы с файлами PC',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Исправлены потенциальные проблемы с файлами Belfius',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Исправлены потенциальные проблемы с ING-файлами Belfius',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Настройка импорта (3/4). Определите роль каждого столбца',
    'job_config_roles_text'           => 'Каждый столбец в вашем файле CSV содержит определённые данные. Укажите, какие данные должен ожидать импортёр. Опция «сопоставить» данные привяжет каждую запись, найденную в столбце, к значению в вашей базе данных. Часто отображаемый столбец - это столбец, содержащий IBAN спонсорского счёта. Его можно легко сопоставить с существующим в вашей базе данных IBAN.',
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
    'status_fatal_text'               => 'Процесс импорта столкнулся с ошибкой, которую мы не смогли устранить. Увы нам!',
    'status_fatal_more'               => 'Это (возможно очень загадочное) сообщение об ошибке дополняется лог-файлами, которые вы можете найти на жёстком диске, или в контейнере Docker, где вы запускаете Firefly III.',
    'status_finished_title'           => 'Импорт завершён',
    'status_finished_text'            => 'Импорт завершен!',
    'finished_with_errors'            => 'Во время импорта произошли ошибки. Пожалуйста, внимательно проверьте их.',
    'unknown_import_result'           => 'Неизвестный результат импорта',
    'result_no_transactions'          => 'Не было импортировано ни одной транзакции. Возможно, все они были дубликатами и импортировать было нечего. Возможно, файлы журнала смогут рассказать вам, что произошло. Если вы регулярно импортируете данные, это нормально.',
    'result_one_transaction'          => 'Всего одна транзакция была импортирована. Она сохранена с меткой <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>, и вы можете проверить её в будущем.',
    'result_many_transactions'        => 'Firefly III импортировал :count транзакции. Они хранятся с меткой <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> , и вы можете дополнительно их проверить.',


    // general errors and warnings:
    'bad_job_status'                  => 'Для доступа к этой странице, ваш процесс импорта не должен иметь ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(игнорировать этот столбец)',
    'column_account-iban'             => 'Счет актива (IBAN)',
    'column_account-id'               => 'ID основного счёта (соответствующий FF3)',
    'column_account-name'             => 'Основной счёт (название)',
    'column_account-bic'              => 'Счет актива (BIC)',
    'column_amount'                   => 'Сумма',
    'column_amount_foreign'           => 'Сумма (в иностранной валюте)',
    'column_amount_debit'             => 'Сумма (столбец с дебетом)',
    'column_amount_credit'            => 'Сумма (столбец с кредитом)',
    'column_amount_negated'           => 'Сумма (столбец с отрицательной суммой)',
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
    'column_generic-debit-credit'     => 'Общий показатель дебет/кредит банка',
    'column_sepa_ct_id'               => 'Идентификатор SEPA end-to-end',
    'column_sepa_ct_op'               => 'Идентификатор учетной записи SEPA',
    'column_sepa_db'                  => 'Идентификатор SEPA Mandate',
    'column_sepa_cc'                  => 'Код очистки SEPA',
    'column_sepa_ci'                  => 'Идентификатор кредитора SEPA',
    'column_sepa_ep'                  => 'Внешняя цель SEPA',
    'column_sepa_country'             => 'Код страны SEPA',
    'column_sepa_batch_id'            => 'SEPA Batch ID',
    'column_tags-comma'               => 'Метки (разделены запятыми)',
    'column_tags-space'               => 'Метки (разделены пробелами)',
    'column_account-number'           => 'Основной счёт (номер счёта)',
    'column_opposing-number'          => 'Спонсорский счёт (номер счёта)',
    'column_note'                     => 'Примечания',
    'column_internal-reference'       => 'Внутренняя ссылка',

    // error message
    'duplicate_row'                   => 'Строка #:row (":description") не может быть импортирована. Она уже существует.',

];
