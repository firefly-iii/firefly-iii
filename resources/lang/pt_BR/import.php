<?php

/**
 * import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Importar dados para o Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Pré-requisitos para o provedor falso de importação',
    'prerequisites_breadcrumb_spectre'    => 'Pré-requisitos para Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Pré-requisitos para bunq',
    'prerequisites_breadcrumb_ynab'       => 'Pré-requisitos para YNAB',
    'job_configuration_breadcrumb'        => 'Configuração para ":key"',
    'job_status_breadcrumb'               => 'Status de importação para ":key"',
    'disabled_for_demo_user'              => 'desativado no modo demonstração',

    // index page:
    'general_index_intro'                 => 'Bem-vindo à rotina de importação do Firefly III. Existem algumas maneiras de importar dados no Firefly III; elas estão mostradas aqui como botões.',

    // import provider strings (index):
    'button_fake'                         => 'Fingir uma importação',
    'button_file'                         => 'Importar um arquivo',
    'button_bunq'                         => 'Importar de bunq',
    'button_spectre'                      => 'Importar usando Spectre',
    'button_plaid'                        => 'Importar usando Plaid',
    'button_yodlee'                       => 'Importar usando Yodlee',
    'button_quovo'                        => 'Importar usando Quovo',
    'button_ynab'                         => 'Importar de You Need A Budget',
    'button_fints'                        => 'Importar usando FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Pré-requisitos de importação',
    'need_prereq_intro'                   => 'Alguns metodos de importacao necessitam da tua atencao antes que possam ser usados. Por exemplo, eles podem necessitar de uma chave especial da API. Podes configurar tudo aqui. O icon indica se esses pre-requesitos foram cumpridos.',
    'do_prereq_fake'                      => 'Pre-requesitos para o provedor ficticio',
    'do_prereq_file'                      => 'Pre-requesitos para a importacao de ficheiros',
    'do_prereq_bunq'                      => 'Pre-requesitos para a importacao do bunq',
    'do_prereq_spectre'                   => 'Pre-requesitos para a importacao do Spectre',
    'do_prereq_plaid'                     => 'Pre-requesitos para a importacao do Plaid',
    'do_prereq_yodlee'                    => 'Pre-requesitos para a importacao do Yodlee',
    'do_prereq_quovo'                     => 'Pre-requesitos para a importacao do Quovo',
    'do_prereq_ynab'                      => 'Pre-requesitos para a importacao do YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Configuracao para a importacao pelo provedor de importacao ficticio',
    'prereq_fake_text'                    => 'Este provedor ficticio necessita de uma chave API ficticia. Ela tem de conter 32 caracteres de comprimento. Podes usar esta: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Pre-requesitos para uma importacao pela API do Spectre',
    'prereq_spectre_text'                 => 'Para importar dados usando a API do Spectre (v4), deves fornecer ao Firefly III 2 codigos secretos. Eles podem ser encontrados na seguinte <a href="https://www.saltedge.com/clients/profile/secrets">pagina</a>.',
    'prereq_spectre_pub'                  => 'Da mesma forma, a API do Spectre necessita de saber a chave publica que ves em baixo. Sem ela, nao te vai reconhecer. Por favor introduz esta chave publica na seguinte <a href="https://www.saltedge.com/clients/profile/secrets">pagina</a>.',
    'prereq_bunq_title'                   => 'Pre-requesitos para uma importaca pelo bunq',
    'prereq_bunq_text'                    => 'Para importar do bunq, precisas de obter uma chave da API. Podes fazer isso atraves da app. De notar que a importacao do bunq este em fase BETA. Ela so tem vindo a ser testada na API sandbox.',
    'prereq_bunq_ip'                      => 'o bunq necessita do teu endereco IP externo. O Firefly III tentou preencher esse valor usando <a href="https://www.ipify.org/">o servico ipify</a>. Verifica que este endereco de IP e correcto, ou a importacao vai falhar.',
    'prereq_ynab_title'                   => 'Pre-requesitos para uma importacao de YNAB',
    'prereq_ynab_text'                    => 'Para poderes descarregar as transaccoes do YNAB, por favor cria uma nova aplicacao na tua <a href="https://app.youneedabudget.com/settings/developer">Pagina de Configuracoes de Desenvolvedor</a> e introduz o ID e a senha de cliente nesta pagina.',
    'prereq_ynab_redirect'                => 'Para completar a configuracao, introduz o seguinte URL na <a href="https://app.youneedabudget.com/settings/developer">Pagina de Configuracoes de Desenvolvedor</a>, sobre a area de "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III has detected the following callback URI. It seems your server is not set up to accept TLS-connections (https). YNAB will not accept this URI. You may continue with the import (because Firefly III could be wrong) but please keep this in mind.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Fake API key stored successfully!',
    'prerequisites_saved_for_spectre'     => 'App ID and secret stored!',
    'prerequisites_saved_for_bunq'        => 'API key and IP stored!',
    'prerequisites_saved_for_ynab'        => 'YNAB client ID and secret stored!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Job configuration - apply your rules?',
    'job_config_apply_rules_text'         => 'Once the fake provider has run, your rules can be applied to the transactions. This adds time to the import.',
    'job_config_input'                    => 'O teu input',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Introduz o nome do album',
    'job_config_fake_artist_text'         => 'Many import routines have a few configuration steps you must go through. In the case of the fake import provider, you must answer some weird questions. In this case, enter "David Bowie" to continue.',
    'job_config_fake_song_title'          => 'Introduz o nome da musica',
    'job_config_fake_song_text'           => 'Mention the song "Golden years" to continue with the fake import.',
    'job_config_fake_album_title'         => 'Introduz o nome do album',
    'job_config_fake_album_text'          => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Import setup (1/4) - Upload your file',
    'job_config_file_upload_text'         => 'This routine will help you import files from your bank into Firefly III. ',
    'job_config_file_upload_help'         => 'Select your file. Please make sure the file is UTF-8 encoded.',
    'job_config_file_upload_config_help'  => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'job_config_file_upload_type_help'    => 'Select the type of file you will upload',
    'job_config_file_upload_submit'       => 'Carregar ficheiros',
    'import_file_type_csv'                => 'CSV (valores separados por vírgula)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                 => 'Import setup (2/4) - Basic file setup',
    'job_config_uc_text'                  => 'To be able to import your file correctly, please validate the options below.',
    'job_config_uc_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'job_config_uc_date_help'             => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'          => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'     => 'Aplicar regras',
    'job_config_uc_apply_rules_text'      => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'       => 'Bank-specific options',
    'job_config_uc_specifics_txt'         => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                => 'Continuar',
    'invalid_import_account'              => 'You have selected an invalid account to import into.',
    'import_liability_select'             => 'Responsabilidade',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Escolhe o teu login',
    'job_config_spectre_login_text'       => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'         => 'Activo',
    'spectre_login_status_inactive'       => 'Inactivo',
    'spectre_login_status_disabled'       => 'Desactivado',
    'spectre_login_new_login'             => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'   => 'Seleccionar as contas de onde vai importar',
    'job_config_spectre_accounts_text'    => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_do_not_import'               => '(nao importar)',
    'spectre_no_mapping'                  => 'It seems you have not selected any accounts to import from.',
    'imported_from_account'               => 'Importado de ":account"',
    'spectre_account_with_number'         => 'Conta :number',
    'job_config_spectre_apply_rules'      => 'Aplicar regras',
    'job_config_spectre_apply_rules_text' => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'contas bunq',
    'job_config_bunq_accounts_text'       => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    'bunq_no_mapping'                     => 'It seems you have not selected any accounts.',
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',
    'job_config_bunq_apply_rules'         => 'Aplicar regras',
    'job_config_bunq_apply_rules_text'    => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',
    'bunq_savings_goal'                   => 'Savings goal: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Closed bunq account',

    'ynab_account_closed'                  => 'A conta esta fechada!',
    'ynab_account_deleted'                 => 'A conta esta apagada!',
    'ynab_account_type_savings'            => 'contas poupanca',
    'ynab_account_type_checking'           => 'checking account',
    'ynab_account_type_cash'               => 'conta de dinheiro',
    'ynab_account_type_creditCard'         => 'cartao de credito',
    'ynab_account_type_lineOfCredit'       => 'linha de credito',
    'ynab_account_type_otherAsset'         => 'outra conta de activos',
    'ynab_account_type_otherLiability'     => 'outras responsabilidades',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'conta de comerciante',
    'ynab_account_type_investmentAccount'  => 'conta de investimentos',
    'ynab_account_type_mortgage'           => 'hipoteca',
    'ynab_do_not_import'                   => '(nao importar)',
    'job_config_ynab_apply_rules'          => 'Aplicar regras',
    'job_config_ynab_apply_rules_text'     => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Seleccionar o teu orcamento',
    'job_config_ynab_select_budgets_text'  => 'You have :count budgets stored at YNAB. Please select the one from which Firefly III will import the transactions.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'You cannot import from the following budget(s), because you do not have accounts with the same currency as these budgets.',
    'job_config_ynab_accounts_title'       => 'Seleccionar contas',
    'job_config_ynab_accounts_text'        => 'You have the following accounts available in this budget. Please select from which accounts you want to import, and where the transactions should be stored.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Estado',
    'spectre_extra_key_card_type'          => 'Tipo de cartao',
    'spectre_extra_key_account_name'       => 'Nome da conta',
    'spectre_extra_key_client_name'        => 'Nome do cliente',
    'spectre_extra_key_account_number'     => 'Numero da conta',
    'spectre_extra_key_blocked_amount'     => 'Montante bloqueado',
    'spectre_extra_key_available_amount'   => 'Montante disponivel',
    'spectre_extra_key_credit_limit'       => 'Limite de credito',
    'spectre_extra_key_interest_rate'      => 'Taxa de juros',
    'spectre_extra_key_expiry_date'        => 'Data de validade',
    'spectre_extra_key_open_date'          => 'Data de abertura',
    'spectre_extra_key_current_time'       => 'Hora actual',
    'spectre_extra_key_current_date'       => 'Data actual',
    'spectre_extra_key_cards'              => 'Cartoes',
    'spectre_extra_key_units'              => 'Unidades',
    'spectre_extra_key_unit_price'         => 'Preco unitario',
    'spectre_extra_key_transactions_count' => 'Contagem de transaccoes',

    //job configuration for finTS
    'fints_connection_failed'              => 'An error occurred while trying to connecting to your bank. Please make sure that all the data you entered is correct. Original error message: :originalError',

    'job_config_fints_url_help'       => 'E.g. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'For many banks this is your account number.',
    'job_config_fints_port_help'      => 'A porta de defeito e 443.',
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
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Import setup (3/4) - Define each column\'s role',
    'job_config_roles_text'           => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'job_config_roles_submit'         => 'Continuar',
    'job_config_roles_column_name'    => 'Nome da coluna',
    'job_config_roles_column_example' => 'Dados de exemplo da coluna',
    'job_config_roles_column_role'    => 'Significado dos dados da coluna',
    'job_config_roles_do_map_value'   => 'Map these values',
    'job_config_roles_no_example'     => 'No example data available',
    'job_config_roles_fa_warning'     => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    'job_config_roles_rwarning'       => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'job_config_roles_colum_count'    => 'Coluna',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'             => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'          => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'          => 'Valor do campo',
    'job_config_field_mapped'         => 'Mapped to',
    'map_do_not_map'                  => '(não mapear)',
    'job_config_map_submit'           => 'Comecar a importacao',


    // import status page:
    'import_with_key'                 => 'Importar com a chave \':key\'',
    'status_wait_title'               => 'Por favor espere...',
    'status_wait_text'                => 'Esta caixa desaparecerá em um instante.',
    'status_running_title'            => 'A importação está em execução',
    'status_job_running'              => 'Aguarda por favor, a importacao esta a correr...',
    'status_job_storing'              => 'Aguarda por favor, a gravar dados...',
    'status_job_rules'                => 'Aguarda por favor, as regras estao a correr...',
    'status_fatal_title'              => 'Erro fatal',
    'status_fatal_text'               => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'               => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'           => 'Importacao terminada',
    'status_finished_text'            => 'A importacao foi terminada.',
    'finished_with_errors'            => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'           => 'Resultados da importacao desconhecidos',
    'result_no_transactions'          => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'          => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'        => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',


    // general errors and warnings:
    'bad_job_status'                  => 'To access this page, your import job cannot have status ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(ignorar esta coluna)',
    'column_account-iban'             => 'Conta de Ativo (IBAN)',
    'column_account-id'               => 'ID da Conta de Ativo (correspondente FF3)',
    'column_account-name'             => 'Conta de Ativo (nome)',
    'column_account-bic'              => 'Asset account (BIC)',
    'column_amount'                   => 'Montante',
    'column_amount_foreign'           => 'Montante (em moeda estrangeira)',
    'column_amount_debit'             => 'Montante (coluna de débito)',
    'column_amount_credit'            => 'Montante (coluna de crédito)',
    'column_amount_negated'           => 'Montante (coluna negada)',
    'column_amount-comma-separated'   => 'Montante (vírgula como separador decimal)',
    'column_bill-id'                  => 'ID da fatura (correspondente FF3)',
    'column_bill-name'                => 'Nome da Fatura',
    'column_budget-id'                => 'ID do Orçamento (correspondente FF3)',
    'column_budget-name'              => 'Nome do Orçamento',
    'column_category-id'              => 'ID da Categoria (correspondente FF3)',
    'column_category-name'            => 'Nome da Categoria',
    'column_currency-code'            => 'Código da Moeda (ISO 4217)',
    'column_foreign-currency-code'    => 'Código de moeda estrangeira (ISO 4217)',
    'column_currency-id'              => 'ID da Moeda (correspondente FF3)',
    'column_currency-name'            => 'Nome da Moeda (correspondente FF3)',
    'column_currency-symbol'          => 'Símbolo da Moeda (correspondente FF3)',
    'column_date-interest'            => 'Data de cálculo de juros',
    'column_date-book'                => 'Data da reserva de transação',
    'column_date-process'             => 'Data do processo de transação',
    'column_date-transaction'         => 'Data',
    'column_date-due'                 => 'Data de vencimento da transação',
    'column_date-payment'             => 'Data de pagamento da transação',
    'column_date-invoice'             => 'Data da fatura da transação',
    'column_description'              => 'Descrição',
    'column_opposing-iban'            => 'Conta contrária (IBAN)',
    'column_opposing-bic'             => 'Conta contrária (BIC)',
    'column_opposing-id'              => 'ID da Conta Cotrária (correspondente FF3)',
    'column_external-id'              => 'ID Externo',
    'column_opposing-name'            => 'Conta contrária (nome)',
    'column_rabo-debit-credit'        => 'Indicador de débito/crédito específico do Rabobank',
    'column_ing-debit-credit'         => 'Indicador de débito/crédito específico do ING',
    'column_generic-debit-credit'     => 'Generic bank debit/credit indicator',
    'column_sepa-ct-id'               => 'SEPA identificador end-to-end',
    'column_sepa-ct-op'               => 'SEPA Identificador de conta de contrária',
    'column_sepa-db'                  => 'SEPA Identificador de Mandato',
    'column_sepa-cc'                  => 'SEPA Código de Compensação',
    'column_sepa-ci'                  => 'SEPA Identificador Credor',
    'column_sepa-ep'                  => 'SEPA Finalidade Externa',
    'column_sepa-country'             => 'SEPA Código do País',
    'column_sepa-batch-id'            => 'SEPA Batch ID',
    'column_tags-comma'               => 'Tags (separadas por vírgula)',
    'column_tags-space'               => 'Tags (separadas por espaço)',
    'column_account-number'           => 'Conta de ativo (número da conta)',
    'column_opposing-number'          => 'Conta Contrária (número da conta)',
    'column_note'                     => 'Nota(s)',
    'column_internal-reference'       => 'Referência interna',

];
