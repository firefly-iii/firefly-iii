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
    'status_wait_title'                    => 'Por favor espere...',
    'status_wait_text'                     => 'Esta caja va a desaparecer en un momento.',
    'status_fatal_title'                   => 'Un error fatal ha ocurrido',
    'status_fatal_text'                    => 'Un error fatal ocurrió, que la rutina de importación no se puede recuperar. Por favor vea la explicación en el rojo a continuación.',
    'status_fatal_more'                    => 'Si el error es un tiempo en espera, la importación se detendrá a mitad de camino. Para algunas configuraciones de servidor, es simplemente que el servidor se detuvo mientras la importación se sigue ejecutando en segundo plano. Para verificar esto, revise los archivos de registro. Si el problema persiste, considere importar a través de la linea de comando.',
    'status_ready_title'                   => 'La Importación esta lista para comenzar',
    'status_ready_text'                    => 'La importación esta lista para comenzar. Toda la configuración que necesitaba hacer ya esta hecha. Por favor descargue el archivo de configuración. Le ayudara con la importación en caso de que no vaya según lo planeado. Para realmente ejecutar la importación, usted puede ejecutar el siguiente comando en su consola, o ejecutar la importación basada en web. Dependiendo de su configuración, la importación de la consola le dará mas comentarios de retroalimentacion.',
    'status_ready_noconfig_text'           => 'La importación esta lista para comenzar. Toda la configuración que usted necesitaba hacer ya esta hecha. para ejecutar realmente la importación puede ejecutar el siguiente comando en su consola o ejecutar la importación basada en la web. Dependiendo de su configuración, la importación de la consola le dará mas retroalimentacion.',
    'status_ready_config'                  => 'Configuración de descarga',
    'status_ready_start'                   => 'Comenzar la importación',
    'status_ready_share'                   => 'Por favor considere descargar su configuración y compartirla en <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">centro de configuración de importación</a></strong>. Esto le permitirá a otros usuarios de Firefly III importar sus archivos de manera mas fácil.',
    'status_job_new'                       => 'El trabajo es completamente nuevo.',
    'status_job_configuring'               => 'La importación se está configurando.',
    'status_job_configured'                => 'La importación está configurada.',
    'status_job_running'                   => 'La importación se esta ejecutando.. por favor espere..',
    'status_job_error'                     => 'El trabajo generó un error.',
    'status_job_finished'                  => '¡La importación ha terminado!',
    'status_running_title'                 => 'La importación se esta ejecutando',
    'status_running_placeholder'           => 'Por favor espere por la actualización...',
    'status_finished_title'                => 'Rutina de importación terminada',
    'status_finished_text'                 => 'La rutina de importación ha importado sus datos.',
    'status_errors_title'                  => 'Errores durante la importación',
    'status_errors_single'                 => 'Ha ocurrido un error durante la importación. no parece ser fatal.',
    'status_errors_multi'                  => 'Algunos errores ocurrieron durante la importación. Estos no parecen ser fatales.',
    'status_bread_crumb'                   => 'Estado de importación',
    'status_sub_title'                     => 'Estado de importación',
    'config_sub_title'                     => 'Configure su importación',
    'status_finished_job'                  => 'Las :count transacciones importadas pueden ser encontradas en la etiqueta <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">.',
    'status_finished_no_tag'               => 'Firefly III no ha recogido ninguna revistas de su archivo de importación.',
    'import_with_key'                      => 'Importar con la clave \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Confirguracion de importacion (1/4) suba su archivo',
    'file_upload_text'                     => 'Esta rutina le ayudara a usted a importar archivos de su banco a firefly III. Por favor chequee las paginas de ayuda en la esquina superior derecha.',
    'file_upload_fields'                   => 'Campos',
    'file_upload_help'                     => 'Seleccionar sus archivos',
    'file_upload_config_help'              => 'Si previamente ha importado datos en Firefly III, usted puede tener un archivo de configuración, el cual preestablecera valores de configuración para usted. para algunos bancos, otros usuarios han proporcionado amablemente su archivo <a href="https://github.com/firefly-iii/import-configurations/wiki">configuración</a>',
    'file_upload_type_help'                => 'Selecciona el tipo de archivo que subirá',
    'file_upload_submit'                   => 'Subir archivos',

    // file, upload types
    'import_file_type_csv'                 => 'CSV ( valores separados por comas)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Configuración de importación (2/4) - Configuración básica CSV de importación',
    'csv_initial_text'                     => 'Para poder importar su archivo correctamente, por favor valide las opciones a continuación.',
    'csv_initial_box'                      => 'Configuración de importación de CSV simple',
    'csv_initial_box_title'                => 'Opciones de configuración para importación de CSV simple',
    'csv_initial_header_help'              => 'Marque esta casilla si la primera fila de su archivo CSV son los títulos de las columnas.',
    'csv_initial_date_help'                => 'Formato de fecha y hora en el CSV. siga un formato como <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta pagina</a>indica.El valor por defecto.',
    'csv_initial_delimiter_help'           => 'Elija el delimitador de campos de su archivo de entrada. si no esta seguro, la coma es la opción.',
    'csv_initial_import_account_help'      => 'Si su archivo CSV NO contiene información sobre su (s) cuenta (s) de activos. Use este menú desplegable para seleccionar a que cuenta pertenecen las transacciones en el archivo CSV.',
    'csv_initial_submit'                   => 'Continúe con el paso 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Aplicar reglas',
    'file_apply_rules_description'         => 'Aplique sus reglas. tenga en cuenta que esto reduce significativamente la importación.',
    'file_match_bills_title'               => 'Unir facturas',
    'file_match_bills_description'         => 'Haga coincidir sus facturas con los retiros recién creados. Tenga en cuenta que esto reduce significativamente la importación.',

    // file, roles config
    'csv_roles_title'                      => 'Configuración de importación (3/4) defina el rol de cada columna',
    'csv_roles_text'                       => 'Cada columna en su archivo CSV contiene cierto datos. Por favor indique que tipo de datos debe esperar el importador. la opción de "map" datos significa que enlazara cada entrada encontrada en la columna con un valor en su base de datos. A menudo una columna mapeada es la columna que contiene el IBAN ya presentes en su base de datos.',
    'csv_roles_table'                      => 'Tabla',
    'csv_roles_column_name'                => 'Nombre de la columna',
    'csv_roles_column_example'             => 'Datos de ejemplo de columna',
    'csv_roles_column_role'                => 'Significado de los datos de la columna',
    'csv_roles_do_map_value'               => 'Mapear estos valores',
    'csv_roles_column'                     => 'Columna',
    'csv_roles_no_example_data'            => 'No hay datos de ejemplo disponibles',
    'csv_roles_submit'                     => 'Continúe en el paso 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'Por lo menos, marque una columna como la columna de importe. también es aconsejable seleccionar una columna para la descripción. la fecha y la cuenta contraria.',
    'foreign_amount_warning'               => 'Si usted marca una columna que contiene un importe en una moneda extranjera, usted también debe establecer la columna que contiene que moneda es.',
    // file, map data
    'file_map_title'                       => 'Configuración de importación (4/4) - Conecta datos de importación a los datos de Firefly III',
    'file_map_text'                        => 'En las siguientes tablas, el valor de la izquierda muestra información encontrada en el Csv cargado. Es su tarea mapear este valor, si es posible, a un valor ya presente en su base de datos. Firefly Iii respetara este mapeo. Si no hay un valor hacia el cual mapear o no se desea mapear un valor especifico, no seleccione ninguno.',
    'file_map_field_value'                 => 'Valor del campo',
    'file_map_field_mapped_to'             => 'Asignado a',
    'map_do_not_map'                       => '(no mapear)',
    'file_map_submit'                      => 'Comenzar la importación',
    'file_nothing_to_map'                  => 'No hay datos presentes en su archivo que pueda asignar a los valores existentes. Por favor presione "comenzar la importación" para continuar.',

    // map things.
    'column__ignore'                       => '(Ignorar esta columna)',
    'column_account-iban'                  => 'Caja de ahorros (IBAN)',
    'column_account-id'                    => 'Identificación de Cuenta de ingresos (coincide con FF3)',
    'column_account-name'                  => 'Caja de ahorros (nombre)',
    'column_amount'                        => 'Cantidad',
    'column_amount_foreign'                => 'Monto ( en moneda extranjera)',
    'column_amount_debit'                  => 'Cantidad (columna de débito)',
    'column_amount_credit'                 => 'Cantidad (columna de crédito)',
    'column_amount-comma-separated'        => 'Cantidad (coma como decimal separador)',
    'column_bill-id'                       => 'ID factura (coincide FF3)',
    'column_bill-name'                     => 'Nombre de la factura',
    'column_budget-id'                     => 'ID presupuesto (coincide FF3)',
    'column_budget-name'                   => 'Nombre del presupuesto',
    'column_category-id'                   => 'ID de categoría (coincide FF3)',
    'column_category-name'                 => 'Nombre de la categoría',
    'column_currency-code'                 => 'Código de la moneda (ISO 4217)',
    'column_foreign-currency-code'         => 'Código de moneda extranjera ( ISO 4217)',
    'column_currency-id'                   => 'ID de moneda (coincide FF3)',
    'column_currency-name'                 => 'Nombre de moneda (coincide FF3)',
    'column_currency-symbol'               => 'Símbolo de moneda (coincide FF3)',
    'column_date-interest'                 => 'Fecha de cálculo de intereses',
    'column_date-book'                     => 'Fecha de registro de la transacción',
    'column_date-process'                  => 'Fecha del proceso de transacción',
    'column_date-transaction'              => 'Fecha',
    'column_description'                   => 'Descripción',
    'column_opposing-iban'                 => 'Cuenta opuesta (IBAN)',
    'column_opposing-id'                   => 'ID de cuenta opuesta (coincide FF3)',
    'column_external-id'                   => 'Identificación externa',
    'column_opposing-name'                 => 'Cuenta opuesta (nombre)',
    'column_rabo-debit-credit'             => 'Indicador especifico débito/crédito de Rabobank',
    'column_ing-debit-credit'              => 'Indicador especifico débito/crédito de ING',
    'column_sepa-ct-id'                    => 'ID transferencia de crédito extremo a extremo',
    'column_sepa-ct-op'                    => 'Transferencia de crédito a cuenta opuesta SEPA',
    'column_sepa-db'                       => 'SEPA débito directo',
    'column_tags-comma'                    => 'Etiquetas ( separadas por comas)',
    'column_tags-space'                    => 'Etiquetas ( separadas por espacio)',
    'column_account-number'                => 'Cuenta de archivos ( numero de cuenta)',
    'column_opposing-number'               => 'Cuenta opuesta (numero de cuenta)',
    'column_note'                          => 'Nota (s)',

    // prerequisites
    'prerequisites'                        => 'Prerequisitos',

    // bunq
    'bunq_prerequisites_title'             => 'Pre requisitos para una importación de bunq',
    'bunq_prerequisites_text'              => 'Para importar de bunq, usted necesita obtener una clave API. usted puede hacerlo a través de la aplicación.',

    // Spectre
    'spectre_title'                        => 'Importar usando Spectre',
    'spectre_prerequisites_title'          => 'Pre requisitos para una importación usando Spectre',
    'spectre_prerequisites_text'           => 'Para importar datos usando la API de Spectre, Usted debe proveer FIrefly III dos valores secretos. Se pueden encontrar en <a href="https://www.saltedge.com/clients/profile/secrets">pagina secretas</a>.',
    'spectre_enter_pub_key'                => 'La importación solo funcionara cuando ingrese esta clave publica en su <a href="https://www.saltedge.com/clients/security/edit">pagina secreta</a>.',
    'spectre_accounts_title'               => 'Seleccionar cuentas para importar desde',
    'spectre_accounts_text'                => 'Cada cuenta a la izquierda abajo ha sido encontrada por Spectre y puede ser importada en Firefly III. Por favor seleccione la cuenta de activo que debe contener cualquier transacción determinada. Si usted no desea importar desde una cuenta en particular, elimine el cheque de la casilla de verificación.',
    'spectre_do_import'                    => 'Si, importar desde esta cuenta',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Estatus',
    'spectre_extra_key_card_type'          => 'Tipo de tarjeta',
    'spectre_extra_key_account_name'       => 'Nombre de la cuenta',
    'spectre_extra_key_client_name'        => 'Nombre del cliente',
    'spectre_extra_key_account_number'     => 'Numero de cuenta',
    'spectre_extra_key_blocked_amount'     => 'Monto bloqueado',
    'spectre_extra_key_available_amount'   => 'Monto disponible',
    'spectre_extra_key_credit_limit'       => 'Limite de credito',
    'spectre_extra_key_interest_rate'      => 'Tasa de interés',
    'spectre_extra_key_expiry_date'        => 'Fecha de vencimiento',
    'spectre_extra_key_open_date'          => 'Fecha de apertura',
    'spectre_extra_key_current_time'       => 'Tiempo actual',
    'spectre_extra_key_current_date'       => 'Fecha actual',
    'spectre_extra_key_cards'              => 'Tarjetas',
    'spectre_extra_key_units'              => 'Unidades',
    'spectre_extra_key_unit_price'         => 'Precio unitario',
    'spectre_extra_key_transactions_count' => 'Cuenta de transacciones',

    // various other strings:
    'imported_from_account'                => 'Importado de ":account"',
];

