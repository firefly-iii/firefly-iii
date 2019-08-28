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
    'need_prereq_intro'                   => 'Alguns métodos de importação precisam da sua atenção antes de serem utilizados. Por exemplo, esses métodos podem necessitar uma chave de API especial ou chaves de aplicação. Você pode configurá-los aqui. O ícone indica se os pré-requisitos foram atendidos.',
    'do_prereq_fake'                      => 'Pre-requisitos para o fornecedor falso',
    'do_prereq_file'                      => 'Pré-requisitos para a importação por arquivo',
    'do_prereq_bunq'                      => 'Pré-requisitos para a importação usando bunq',
    'do_prereq_spectre'                   => 'Pré-requisitos para a importação usando Spectre',
    'do_prereq_plaid'                     => 'Pré-requisitos para a importação usando Plaid',
    'do_prereq_yodlee'                    => 'Pré-requisitos para a importação usando Yodlee',
    'do_prereq_quovo'                     => 'Pré-requisitos para a importação usando Quovo',
    'do_prereq_ynab'                      => 'Pré-requisitos para a importação usando YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Pré-requisitos para a importação usando um fornecedor falso',
    'prereq_fake_text'                    => 'Este fornecedor falso necessita de uma falsa API key de 32 caracteres. Você pode usar este: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Pre-requesitos para uma importacao pela API do Spectre',
    'prereq_spectre_text'                 => 'Para importar dados usando a API do Spectre (v4), você deve fornecer ao Firefly III dois códigos secretos. Eles podem ser encontrados na <a href="https://www.saltedge.com/clients/profile/secrets">pagina de segredos</a>.',
    'prereq_spectre_pub'                  => 'Da mesma forma, a API do Spectre precisa saber a chave pública abaixo. Sem ela, a API não vai reconhecê-lo. Por favor introduza esta chave pública na seguinte <a href="https://www.saltedge.com/clients/profile/secrets">pagina</a>.',
    'prereq_bunq_title'                   => 'Pré-requisitos para uma importação do bunq',
    'prereq_bunq_text'                    => 'Para importar do bunq, você precisa obter uma chave de API. Você pode fazer isso através do aplicativo. Por favor, note que a função de importação para bunq está em BETA. Foi testado apenas na API do sandbox.',
    'prereq_bunq_ip'                      => 'O bunq requer seu endereço IP externo. O Firefly III tentou preencher isso usando <a href="https://www.ipify.org/">o serviço ipify</a>. Certifique-se de que esse endereço IP esteja correto ou a importação falhará.',
    'prereq_ynab_title'                   => 'Pré-requisitos para uma importação de YNAB',
    'prereq_ynab_text'                    => 'Para poder fazer o download das transações do YNAB, crie um novo aplicativo em sua <a href="https://app.youneedabudget.com/settings/developer">página de configurações do desenvolvedor</a> e insira o ID do cliente e senha nesta página.',
    'prereq_ynab_redirect'                => 'Para concluir a configuração, digite o seguinte URL na <a href="https://app.youneedabudget.com/settings/developer">página de configurações do desenvolvedor</a> sob "URI(s) de redirecionamento".',
    'callback_not_tls'                    => 'O Firefly III detectou o seguinte URI de retorno de chamada. Parece que seu servidor não está configurado para aceitar conexões TLS (https). O YNAB não aceitará este URI. Você pode continuar com a importação (porque o Firefly III pode estar errado), mas lembre-se disso.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'API Key falsa armazenada com sucesso!',
    'prerequisites_saved_for_spectre'     => 'App ID e segredo armazenados!',
    'prerequisites_saved_for_bunq'        => 'Chave de API e IP armazenados!',
    'prerequisites_saved_for_ynab'        => 'ID do cliente YNAB e segredo armazenados!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Configuração de tarefa - aplicar suas regras?',
    'job_config_apply_rules_text'         => 'Uma vez que o provedor falso executar, suas regras podem ser aplicadas às transações. Isso aumenta o tempo da importação.',
    'job_config_input'                    => 'Sua entrada',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Digite o nome do álbum',
    'job_config_fake_artist_text'         => 'Muitas rotinas de importação têm algumas etapas de configuração pelas quais você deve passar. No caso do provedor de importação falso, você deve responder a algumas perguntas estranhas. Neste caso, digite "David Bowie" para continuar.',
    'job_config_fake_song_title'          => 'Digite o nome da música',
    'job_config_fake_song_text'           => 'Mencione a música "Golden years" para continuar com a importação falsa.',
    'job_config_fake_album_title'         => 'Digite o nome do álbum',
    'job_config_fake_album_text'          => 'Algumas rotinas de importação exigem dados extras na metade da importação. No caso do provedor de importação falso, você deve responder a algumas perguntas estranhas. Digite "Station to station" para continuar.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Importar configuração (1/4) - Carregar seu arquivo',
    'job_config_file_upload_text'         => 'Essa rotina ajudará você a importar arquivos do seu banco para o Firefly III. ',
    'job_config_file_upload_help'         => 'Selecione seu arquivo. Por favor, verifique se o arquivo é codificado em UTF-8.',
    'job_config_file_upload_config_help'  => 'Se você já importou dados para o Firefly III, você pode ter um arquivo de configuração, que irá pré-configurar valores para você. Para alguns bancos, outros usuários gentilmente forneceram seu <a href="https://github.com/firefly-iii/import-configurations/wiki">arquivo de configuração</a>',
    'job_config_file_upload_type_help'    => 'Selecione o tipo de arquivo que você fará o upload',
    'job_config_file_upload_submit'       => 'Anexar arquivos',
    'import_file_type_csv'                => 'CSV (valores separados por vírgula)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'O arquivo que você enviou não está codificado como UTF-8 ou ASCII. O Firefly III não pode lidar com esses arquivos. Por favor, use o Notepad++ ou Sublime para converter seu arquivo em UTF-8.',
    'job_config_uc_title'                 => 'Importar configuração (2/4) - configuração básica do arquivo',
    'job_config_uc_text'                  => 'Para ser capaz de importar o arquivo corretamente, por favor valide as opções abaixo.',
    'job_config_uc_header_help'           => 'Marque esta caixa se a primeira linha do seu arquivo CSV for os títulos das colunas.',
    'job_config_uc_date_help'             => 'Formato de data e hora no seu arquivo. Siga o formato como <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta página</a> indica. O valor padrão analisará datas semelhantes a esta: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Escolha o delimitador de campo que é usado em seu arquivo de entrada. Se não tiver certeza, a vírgula é a opção mais segura.',
    'job_config_uc_account_help'          => 'Se o seu arquivo NÃO contiver informações sobre sua(s) conta(s) de ativos, use esta lista suspensa para selecionar a qual conta as transações no arquivo pertencem.',
    'job_config_uc_apply_rules_title'     => 'Aplicar regras',
    'job_config_uc_apply_rules_text'      => 'Aplica suas regras a todas as transações importadas. Note que isto diminui significativamente a importação.',
    'job_config_uc_specifics_title'       => 'Opções específicas do banco',
    'job_config_uc_specifics_txt'         => 'Alguns bancos entregam arquivos mal formatados. O Firefly III pode consertar esses arquivos automaticamente. Se seu banco entregar esses arquivos, mas não está listado aqui, por favor, abra uma issue no GitHub.',
    'job_config_uc_submit'                => 'Continuar',
    'invalid_import_account'              => 'Você selecionou uma conta inválida para importar.',
    'import_liability_select'             => 'Passivo',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Escolha seu login',
    'job_config_spectre_login_text'       => 'Firefly III encontrou :count login(s) existente(s) na sua conta Spectre. De qual você gostaria de usar para importar?',
    'spectre_login_status_active'         => 'Ativo',
    'spectre_login_status_inactive'       => 'Inativo',
    'spectre_login_status_disabled'       => 'Desabilitado',
    'spectre_login_new_login'             => 'Faça o login com outro banco, ou com um desses bancos com credenciais diferentes.',
    'job_config_spectre_accounts_title'   => 'Selecione as contas a serem importadas',
    'job_config_spectre_accounts_text'    => 'Você selecionou ":name" (:country). Você tem :count conta(s) disponível deste provedor. Por favor, selecione a conta de ativo(s) Firefly III onde as transações destas contas devem ser armazenadas. Lembre-se, para importar dados tanto da conta Firefly III como da ":name" devem ter a mesma moeda.',
    'spectre_do_not_import'               => '(não importar)',
    'spectre_no_mapping'                  => 'Parece que você não selecionou nenhuma conta para importar.',
    'imported_from_account'               => 'Importado de ":account"',
    'spectre_account_with_number'         => 'Conta :number',
    'job_config_spectre_apply_rules'      => 'Aplicar regras',
    'job_config_spectre_apply_rules_text' => 'Por padrão, suas regras serão aplicadas às transações criadas durante esta rotina de importação. Se você não quer que isso aconteça, desmarque esta caixa de seleção.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'contas bunq',
    'job_config_bunq_accounts_text'       => 'Estas são as contas associadas à sua conta bunq. Por favor, selecione as contas das quais você deseja importar e em qual conta as transações devem ser importadas.',
    'bunq_no_mapping'                     => 'Parece que você não selecionou nenhuma conta.',
    'should_download_config'              => 'Você deve baixar <a href=":route">o arquivo de configuração</a> para esta tarefa. Isso facilitará as futuras importações.',
    'share_config_file'                   => 'Se você importou dados de um banco público, você deve <a href="https://github.com/firefly-iii/import-configurations/wiki">compartilhar seu arquivo de configuração</a> para que seja fácil outros usuários importem seus dados. Compartilhar seu arquivo de configuração não exporá seus detalhes financeiros.',
    'job_config_bunq_apply_rules'         => 'Aplicar regras',
    'job_config_bunq_apply_rules_text'    => 'Por padrão, suas regras serão aplicadas às transações criadas durante esta rotina de importação. Se você não quer que isso aconteça, desmarque esta caixa de seleção.',
    'bunq_savings_goal'                   => 'Meta de poupança: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Contas bunq fechadas',

    'ynab_account_closed'                  => 'Conta fechada!',
    'ynab_account_deleted'                 => 'Conta excluída!',
    'ynab_account_type_savings'            => 'conta poupança',
    'ynab_account_type_checking'           => 'conta corrente',
    'ynab_account_type_cash'               => 'conta de dinheiro',
    'ynab_account_type_creditCard'         => 'cartão de crédito',
    'ynab_account_type_lineOfCredit'       => 'linha de crédito',
    'ynab_account_type_otherAsset'         => 'outra conta de ativos',
    'ynab_account_type_otherLiability'     => 'outros passivos',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'conta de comerciante',
    'ynab_account_type_investmentAccount'  => 'conta de investimento',
    'ynab_account_type_mortgage'           => 'hipoteca',
    'ynab_do_not_import'                   => '(não importar)',
    'job_config_ynab_apply_rules'          => 'Aplicar regras',
    'job_config_ynab_apply_rules_text'     => 'Por padrão, suas regras serão aplicadas às transações criadas durante esta rotina de importação. Se você não quer que isso aconteça, desmarque esta caixa de seleção.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Selecione seu orçamento',
    'job_config_ynab_select_budgets_text'  => 'Você tem :count orçamentos armazenados no YNAB. Por favor, selecione de qual o Firefly III irá importar as transações.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'You cannot import from the following budget(s), because you do not have accounts with the same currency as these budgets.',
    'job_config_ynab_accounts_title'       => 'Seleccionar contas',
    'job_config_ynab_accounts_text'        => 'You have the following accounts available in this budget. Please select from which accounts you want to import, and where the transactions should be stored.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Tipo de cartão',
    'spectre_extra_key_account_name'       => 'Nome da conta',
    'spectre_extra_key_client_name'        => 'Nome do cliente',
    'spectre_extra_key_account_number'     => 'Número da conta',
    'spectre_extra_key_blocked_amount'     => 'Valor bloqueado',
    'spectre_extra_key_available_amount'   => 'Valor disponível',
    'spectre_extra_key_credit_limit'       => 'Limite de crédito',
    'spectre_extra_key_interest_rate'      => 'Taxa de juros',
    'spectre_extra_key_expiry_date'        => 'Data de vencimento',
    'spectre_extra_key_open_date'          => 'Data de abertura',
    'spectre_extra_key_current_time'       => 'Hora atual',
    'spectre_extra_key_current_date'       => 'Data actual',
    'spectre_extra_key_cards'              => 'Cartões',
    'spectre_extra_key_units'              => 'Unidades',
    'spectre_extra_key_unit_price'         => 'Preço unitário',
    'spectre_extra_key_transactions_count' => 'Contagem de transações',

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
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Fixes potential problems with Belfius files',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
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
    'unknown_import_result'           => 'Resultados da importação desconhecidos',
    'result_no_transactions'          => 'Nenhuma transação foi importada. Talvez fossem todos duplicados, simplesmente não há transações a serem importadas. Talvez os arquivos de log possam dizer o que aconteceu. Se você importar dados regularmente, isso é normal.',
    'result_one_transaction'          => 'Exatamente uma transação foi importada. Ela é armazenada sob a tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> onde você pode inspecioná-la ainda mais.',
    'result_many_transactions'        => 'Firefly III importou :count transações. Elas são armazenados na tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>, onde você pode inspecioná-los ainda mais.',


    // general errors and warnings:
    'bad_job_status'                  => 'Para acessar esta página, seu job de importação não pode ter status ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(ignorar esta coluna)',
    'column_account-iban'             => 'Conta de Ativo (IBAN)',
    'column_account-id'               => 'ID da Conta de Ativo (correspondente FF3)',
    'column_account-name'             => 'Conta de Ativo (nome)',
    'column_account-bic'              => 'Conta de Ativo (IBAN)',
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
    'column_generic-debit-credit'     => 'Indicador de débito/crédito bancário genérico',
    'column_sepa_ct_id'               => 'Identificador final do SEPA',
    'column_sepa_ct_op'               => 'Identificador de conta de oposição SEPA',
    'column_sepa_db'                  => 'Identificador obrigatório SEPA',
    'column_sepa_cc'                  => 'Codigo de Compensação SEPA',
    'column_sepa_ci'                  => 'Identificador de Crédito SEPA',
    'column_sepa_ep'                  => 'Finalidade externa SEPA',
    'column_sepa_country'             => 'Código do País SEPA',
    'column_sepa_batch_id'            => 'ID do Lote SEPA',
    'column_tags-comma'               => 'Tags (separadas por vírgula)',
    'column_tags-space'               => 'Tags (separadas por espaço)',
    'column_account-number'           => 'Conta de ativo (número da conta)',
    'column_opposing-number'          => 'Conta Contrária (número da conta)',
    'column_note'                     => 'Nota(s)',
    'column_internal-reference'       => 'Referência interna',

    // error message
    'duplicate_row'                   => 'Linha #:row (":description") não pôde ser importada. Ela já existe.',

];
