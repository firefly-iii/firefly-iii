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
    'index_breadcrumb'                    => 'Importar dados para o Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Pré-requisitos para o provedor falso de importação',
    'prerequisites_breadcrumb_spectre'    => 'Pré-requisitos para Spectre',
    'job_configuration_breadcrumb'        => 'Configuração para ":key"',
    'job_status_breadcrumb'               => 'Status de importação para ":key"',
    'disabled_for_demo_user'              => 'desativado no modo demonstração',

    // index page:
    'general_index_intro'                 => 'Bem-vindo à rotina de importação do Firefly III. Existem algumas maneiras de importar dados no Firefly III; elas estão mostradas aqui como botões.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',
    'final_csv_import'     => 'Como descrito neste <a href="https://www.patreon.com/posts/future-updates-30012174">post no Patreon</a>, a forma como o Firefly III gerencia a importação de dados vai mudar. Isto significa que esta é a última versão do Firefly III que incluirá um importador CSV. Uma ferramenta separada está disponível e você deveria testar: <a href="https://github.com/firefly-iii/csv-importer">o importador CSV Firefly III.</a>. Agradeço se você puder testar o novo importador e me dizer o que acha.',

    // import provider strings (index):
    'button_fake'                         => 'Fingir uma importação',
    'button_file'                         => 'Importar um arquivo',
    'button_spectre'                      => 'Importar usando Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Pré-requisitos de importação',
    'need_prereq_intro'                   => 'Alguns métodos de importação precisam da sua atenção antes de serem utilizados. Por exemplo, esses métodos podem necessitar uma chave de API especial ou chaves de aplicação. Você pode configurá-los aqui. O ícone indica se os pré-requisitos foram atendidos.',
    'do_prereq_fake'                      => 'Pre-requisitos para o fornecedor falso',
    'do_prereq_file'                      => 'Pré-requisitos para a importação por arquivo',
    'do_prereq_spectre'                   => 'Pré-requisitos para a importação usando Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Pré-requisitos para a importação usando um fornecedor falso',
    'prereq_fake_text'                    => 'Este fornecedor falso necessita de uma falsa API key de 32 caracteres. Você pode usar este: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Pre-requesitos para uma importacao pela API do Spectre',
    'prereq_spectre_text'                 => 'Para importar dados usando a API do Spectre (v4), você deve fornecer ao Firefly III dois códigos secretos. Eles podem ser encontrados na <a href="https://www.saltedge.com/clients/profile/secrets">pagina de segredos</a>.',
    'prereq_spectre_pub'                  => 'Da mesma forma, a API do Spectre precisa saber a chave pública abaixo. Sem ela, a API não vai reconhecê-lo. Por favor introduza esta chave pública na seguinte <a href="https://www.saltedge.com/clients/profile/secrets">pagina</a>.',
    'callback_not_tls'                    => 'O Firefly III detectou o seguinte URI de retorno de chamada. Parece que seu servidor não está configurado para aceitar conexões TLS (https). O YNAB não aceitará este URI. Você pode continuar com a importação (porque o Firefly III pode estar errado), mas lembre-se disso.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'API Key falsa armazenada com sucesso!',
    'prerequisites_saved_for_spectre'     => 'App ID e segredo armazenados!',

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
    'should_download_config'              => 'Você deve baixar <a href=":route">o arquivo de configuração</a> para esta tarefa. Isso facilitará as futuras importações.',
    'share_config_file'                   => 'Se você importou dados de um banco público, você deve <a href="https://github.com/firefly-iii/import-configurations/wiki">compartilhar seu arquivo de configuração</a> para que seja fácil outros usuários importem seus dados. Compartilhar seu arquivo de configuração não exporá seus detalhes financeiros.',

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
    'spectre_extra_key_current_date'       => 'Data atual',
    'spectre_extra_key_cards'              => 'Cartões',
    'spectre_extra_key_units'              => 'Unidades',
    'spectre_extra_key_unit_price'         => 'Preço unitário',
    'spectre_extra_key_transactions_count' => 'Contagem de transações',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'             => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'          => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'          => 'Valor do campo',
    'job_config_field_mapped'         => 'Mapeado para',
    'map_do_not_map'                  => '(não mapear)',
    'job_config_map_submit'           => 'Iniciar a importação',


    // import status page:
    'import_with_key'                 => 'Importar com a chave \':key\'',
    'status_wait_title'               => 'Por favor espere...',
    'status_wait_text'                => 'Esta caixa desaparecerá em um instante.',
    'status_running_title'            => 'A importação está em execução',
    'status_job_running'              => 'Aguarde por favor, a importação está sendo feita...',
    'status_job_storing'              => 'Aguarde por favor, gravando os dados...',
    'status_job_rules'                => 'Aguarde por favor, aplicando as regras...',
    'status_fatal_title'              => 'Erro fatal',
    'status_fatal_text'               => 'A importação sofreu um erro e não pode ser recuparada. Desculpe!',
    'status_fatal_more'               => 'Esta mensagem de erro (possivelmente muito crítiva) é complementada nos arquivos de log, que você pode encontrar no seu disco rígido, ou no recipiente Docker onde você executa Firefly III.',
    'status_finished_title'           => 'Importação concluída',
    'status_finished_text'            => 'Importação concluída.',
    'finished_with_errors'            => 'Houve alguns erros durante a importação. Por favor, revise-os cuidadosamente.',
    'unknown_import_result'           => 'Resultados da importação desconhecidos',
    'result_no_transactions'          => 'Nenhuma transação foi importada. Talvez fossem todos duplicados, simplesmente não há transações a serem importadas. Talvez os arquivos de log possam dizer o que aconteceu. Se você importar dados regularmente, isso é normal.',
    'result_one_transaction'          => 'Exatamente uma transação foi importada. Ela é armazenada sob a tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> onde você pode inspecioná-la ainda mais.',
    'result_many_transactions'        => 'Firefly III importou :count transações. Elas são armazenados na tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>, onde você pode inspecioná-los ainda mais.',

    // general errors and warnings:
    'bad_job_status'                  => 'Para acessar esta página, seu job de importação não pode ter status ":status".',

    // error message
    'duplicate_row'                   => 'Linha #:row (":description") não pôde ser importada. Ela já existe.',

];
