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
    'index_breadcrumb'                    => 'Importar datos a Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Requisitos para el proveedor de importación falso',
    'prerequisites_breadcrumb_spectre'    => 'Requisitos para bunq',
    'job_configuration_breadcrumb'        => 'Configuración para ":key"',
    'job_status_breadcrumb'               => 'Estado de importación de ":key"',
    'disabled_for_demo_user'              => 'deshabilitado en demo',

    // index page:
    'general_index_intro'                 => 'Bienvenido a la rutina de importación de Firefly III. Hay algunas formas de importar datos a Firefly III, que se muestran aquí como botones.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Como se describe en <a href="https://www.patreon.com/posts/future-updates-30012174">esta publicación de patreón</a>, la forma en que Firefly III administra los datos importados va a cambiar. Esto significa que el importador CSV se trasladará a una nueva herramienta separada. Ya puede probar esta herramienta si visita <a href="https://github.com/firefly-iii/csv-importer">este repositorio de GitHub</a>. Le agradecería que probara el nuevo importador y me hiciera saber lo que piensa.',
    'final_csv_import'     => 'Como se describe en <a href="https://www.patreon.com/posts/future-updates-30012174">esta publicación de patreón</a>, la forma en que Firefly III administra la importación de datos va a cambiar. Esto significa que esta es la última versión de Firefly III que contará con un importador de CSV. Una herramienta separada está disponible que debería probar usted mismo: <a href="https://github.com/firefly-iii/csv-importer">el importador CSV III Firefly</a>. Le agradecería que probara el nuevo importador y me hiciera saber lo que piensa.',

    // import provider strings (index):
    'button_fake'                         => 'Simular una importación',
    'button_file'                         => 'Importar un archivo',
    'button_spectre'                      => 'Importar usando Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Importar requisitos previos',
    'need_prereq_intro'                   => 'Algunos métodos de importación necesitan su atención antes de que se pueden utilizar. Por ejemplo, podrían requerir claves API especiales o secretos de aplicación. Usted puede configurarlos aquí. El icono indica si se han cumplido los requisitos previos.',
    'do_prereq_fake'                      => 'Pre requisitos para el proveedor de falso',
    'do_prereq_file'                      => 'Pre requisitos para las importaciones de archivos',
    'do_prereq_spectre'                   => 'Pre requisitos para las importaciones usando Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Pre requisitos para una importación desde el proveedor de importación falso',
    'prereq_fake_text'                    => 'Este proveedor falso requiere una clave de API falsa. Debe ser 32 caracteres de largo. Puede utilizar este: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Pre requisitos para una importación usando la API de Spectre',
    'prereq_spectre_text'                 => 'Para importar datos usando la API de Spectre (v4), usted debe proveer a FIrefly III dos valores secretos. Se pueden encontrar en <a href="https://www.saltedge.com/clients/profile/secrets">pagina de secretos</a>.',
    'prereq_spectre_pub'                  => 'Del mismo modo, la API de Spectre necesita saber la clave pública que ve debajo. Sin ella, no lo reconocerá. Por favor, ingrese esta clave pública en su <a href="https://www.saltedge.com/clients/profile/secrets">pagina de secretos</a>.',
    'callback_not_tls'                    => 'Firefly III ha detectado la siguiente URI de devolución de llamada. Parece que su servidor no está configurado para aceptar conecciones TLS (https). YNAB no aceptará esta URI. Usted puede continuar con la importación ( ya que Firefly III puede equivocarse) pero por favor, téngalo en mente.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Clave API falsa guardada con éxito!',
    'prerequisites_saved_for_spectre'     => '¡App ID y secreto guardados!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Configuración de trabajo - aplicar sus reglas?',
    'job_config_apply_rules_text'         => 'Una vez que el proovedor falso se ejecutó, sus reglas pueden ser aplicadas a las transacciones. Esto agrega tiempo a la importación.',
    'job_config_input'                    => 'Su entrada',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Introduzca el nombre del álbum',
    'job_config_fake_artist_text'         => 'Muchas de las rutinas de importación tienen unos pasos de configuración que debe atravesar. En el caso del proveedor de importación falso, debe responder algunas preguntas extrañas. En este caso, escriba "David Bowie" para continuar.',
    'job_config_fake_song_title'          => 'Introduzca el nombre de la canción',
    'job_config_fake_song_text'           => 'Mencione la canción "Golden years" para continuar con la importación falsa.',
    'job_config_fake_album_title'         => 'Introduzca el nombre del álbum',
    'job_config_fake_album_text'          => 'Algunas rutinas de importación requieren datos adicionales a medio camino a través de la importación. En el caso del proveedor de importación falso, debe responder algunas preguntas extrañas. Entra en "Estación a estación" para continuar.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Configuración de importación (1/4) - Subir archivo',
    'job_config_file_upload_text'         => 'Esta rutina le ayudará a importar archivos de su banco en Firefly III. ',
    'job_config_file_upload_help'         => 'Seleccione su archivo. Por favor, asegúrese de que el archivo está codificado en UTF-8.',
    'job_config_file_upload_config_help'  => 'Si previamente ha importado datos en Firefly III, puede tener un archivo de configuración, el cual preestablecerá valores de configuración por usted. Para algunos bancos, otros usuarios han proporcionado amablemente sus <a href="https://github.com/firefly-iii/import-configurations/wiki">archivo de configuración</a>',
    'job_config_file_upload_type_help'    => 'Seleccione el tipo de archivo que subirá',
    'job_config_file_upload_submit'       => 'Subir archivos',
    'import_file_type_csv'                => 'CSV (valores separados por comas)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'El archivo que ha subido no es codificado como UTF-8 o ASCII. Firefly III no puede manejar este tipo de archivos. Utilice Notepad++ ó Sublime para convertir el archivo a UTF-8.',
    'job_config_uc_title'                 => 'Configuración de importación (2/4) - Configuración básica de archivo',
    'job_config_uc_text'                  => 'Para poder importar correctamente el archivo, por favor valide las opciones a continuación.',
    'job_config_uc_header_help'           => 'Marque esta casilla si la primera fila del archivo CSV son los títulos de columna.',
    'job_config_uc_date_help'             => 'Formato de fecha y hora en su archivo. Siga un formato como los que indica <a href="https://secure.php.net/manual/es/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta página</a>. El valor por defecto interpretará fechas que se vean así: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Elija el delimitador de campo que se utiliza en el archivo de entrada. Si no está seguro, coma es la opción más segura.',
    'job_config_uc_account_help'          => 'Si su archivo NO contiene información sobre sus cuenta(s) de activo(s), utilice esta lista desplegable para seleccionar la cuenta a la que pertenecen las transacciones en el archivo.',
    'job_config_uc_apply_rules_title'     => 'Aplicar reglas',
    'job_config_uc_apply_rules_text'      => 'Aplica las reglas a cada transacción importada. Tenga en cuenta que esto reduce significativamente la velocidad de importación.',
    'job_config_uc_specifics_title'       => 'Opciones específicas del Banco',
    'job_config_uc_specifics_txt'         => 'Algunos bancos ofrecen archivos mal formateados. Firefly III los puede corregir automáticamente. Si su banco ofrece este tipo de archivos pero no aparece aquí, por favor abre un tema en GitHub.',
    'job_config_uc_submit'                => 'Continuar',
    'invalid_import_account'              => 'Ha seleccionado una cuenta inválida a la cuál importar.',
    'import_liability_select'             => 'Pasivo',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Escoja su login',
    'job_config_spectre_login_text'       => 'Firefly III ha encontrado :count login(s) existente(s) en su cuenta de Spectre. ¿Cual desea utilizar para importar?',
    'spectre_login_status_active'         => 'Activo',
    'spectre_login_status_inactive'       => 'Inactivo',
    'spectre_login_status_disabled'       => 'Deshabilitado',
    'spectre_login_new_login'             => 'Iniciar sesión con otro banco, o uno de estos bancos con credenciales diferentes.',
    'job_config_spectre_accounts_title'   => 'Seleccione las cuentas desde las cuáles importar',
    'job_config_spectre_accounts_text'    => 'Usted ha seleccionado ":name" (:country). Tienes :count cuenta(s) de este proveedor. Por favor, seleccione las cuentas de activo de Firefly III donde las transacciones provenientes de estas cuentas deben ser guardadas. Recuerde que, para poder importar datos, la cuenta de Firefly III y el ":name"-cuenta deben tener la misma moneda.',
    'spectre_do_not_import'               => '(no importar)',
    'spectre_no_mapping'                  => 'Parece que no ha seleccionado ninguna cuenta desde la cual importar.',
    'imported_from_account'               => 'Importado de ":account"',
    'spectre_account_with_number'         => 'Cuenta :number',
    'job_config_spectre_apply_rules'      => 'Aplicar reglas',
    'job_config_spectre_apply_rules_text' => 'De forma predeterminada, sus reglas se aplicarán a las transacciones creadas durante esta rutina de importación. Si no desea que esto suceda, desactive esta casilla de verificación.',

    // job configuration for bunq:
    'should_download_config'              => 'Debería descargar <a href=":route">el archivo de configuración</a> para este trabajo. Esto hará las importaciones futuras de manera más fácil.',
    'share_config_file'                   => 'Si ha importado los datos de un banco público, debe <a href="https://github.com/firefly-iii/import-configurations/wiki">compartir su archivo de configuración</a> para que sea fácil para otros usuarios importar sus propios datos. Compartiendo su archivo de configuración no expondrá sus datos financieros.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Estado',
    'spectre_extra_key_card_type'          => 'Tipo de tarjeta',
    'spectre_extra_key_account_name'       => 'Nombre de la cuenta',
    'spectre_extra_key_client_name'        => 'Nombre del cliente',
    'spectre_extra_key_account_number'     => 'Número de cuenta',
    'spectre_extra_key_blocked_amount'     => 'Cantidad bloqueada',
    'spectre_extra_key_available_amount'   => 'Cantidad disponible',
    'spectre_extra_key_credit_limit'       => 'Limite de crédito',
    'spectre_extra_key_interest_rate'      => 'Tasa de interés',
    'spectre_extra_key_expiry_date'        => 'Fecha de vencimiento',
    'spectre_extra_key_open_date'          => 'Fecha de apertura',
    'spectre_extra_key_current_time'       => 'Tiempo actual',
    'spectre_extra_key_current_date'       => 'Fecha actual',
    'spectre_extra_key_cards'              => 'Tarjetas',
    'spectre_extra_key_units'              => 'Unidades',
    'spectre_extra_key_unit_price'         => 'Precio unitario',
    'spectre_extra_key_transactions_count' => 'Nº de transacciones',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Configuración de importación (4/4) - Conectar datos importados a datos de Firefly III',
    'job_config_map_text'             => 'En las siguientes tablas, el valor de la izquierda muestra información encontrada en el archivo cargado. Es su tarea mapear este valor, si es posible, a un valor ya presente en su base de datos. Firefly Iii respetará este mapeo. Si no hay un valor hacia el cual mapear o no se desea mapear un valor especifico, no seleccione ninguno.',
    'job_config_map_nothing'          => 'No hay datos presentes en su archivo que pueda asignar a los valores existentes. Por favor presione "comenzar la importación" para continuar.',
    'job_config_field_value'          => 'Valor del campo',
    'job_config_field_mapped'         => 'Mapeado a',
    'map_do_not_map'                  => '(no mapear)',
    'job_config_map_submit'           => 'Iniciar la importación',


    // import status page:
    'import_with_key'                 => 'Importar con la clave \':key\'',
    'status_wait_title'               => 'Por favor espere...',
    'status_wait_text'                => 'Esta caja va a desaparecer en un momento.',
    'status_running_title'            => 'La importación se está ejecutando',
    'status_job_running'              => 'Por favor espere, ejecutando la importación...',
    'status_job_storing'              => 'Por favor espere, guardando datos...',
    'status_job_rules'                => 'Por favor espere, ejecutando reglas...',
    'status_fatal_title'              => 'Error fatal',
    'status_fatal_text'               => 'La importación ha sufrido un error del cual no pudo recuperarse, Disculpas!',
    'status_fatal_more'               => 'Este (posiblemente muy críptico) mensaje de error, se complementa con archivos de log, que puedes encontrar en tu HDD o en tu contenedor de Docker en el cual corres Firefly III.',
    'status_finished_title'           => 'Importación finalizada',
    'status_finished_text'            => 'La importación ha terminado.',
    'finished_with_errors'            => 'Han habido algunos errores durante la importación. Por favor, revíselos cuidadosamente.',
    'unknown_import_result'           => 'Resultado de importación desconocido',
    'result_no_transactions'          => 'No se han importado transacciones. Quizás habían sólo duplicados y por eso no hubo transacciones que importar. Quizás los archivos log puedan decirle que sucedió. Si importa data regularmente, esto es normal.',
    'result_one_transaction'          => 'Exactamente una transacción fue importada. Se encuentra guardada bajo el tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> donde puedes inspeccionarla mas a fondo.',
    'result_many_transactions'        => 'Firefly III ha importado :count transacciones. Se encuentran guardadas bajo el tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> donde puedes inspeccionarlas mas a fondo.',

    // general errors and warnings:
    'bad_job_status'                  => 'Para acceder a esta página, tu trabajo de importación no puede tener el status ":status".',

    // error message
    'duplicate_row'                   => 'La fila #:row (":description") no se pudo importar. Ya existe.',

];
