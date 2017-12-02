<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Configuración de importación (1/3) - Configuración de importación de CSV simple',
    'initial_text'                  => 'Para poder importar correctamente el archivo, por favor comprueba las opciones a continuación.',
    'initial_box'                   => 'Configuración de importación de CSV simple',
    'initial_box_title'             => 'Opciones de configuración para importación de CSV simple',
    'initial_header_help'           => 'Marque aquí si el CSV contiene títulos de columna en la primera fila.',
    'initial_date_help'             => 'Formato de fecha y hora en el CSV. Siga un formato como los que indica <a href="https://secure.php.net/manual/es/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta página</a>. El valor por defecto interpretará fechas que se vean así: :dateExample.',
    'initial_delimiter_help'        => 'Elija el delimitador de campos de su archivo de entrada. Si no está seguro, la coma es la opción más segura.',
    'initial_import_account_help'   => 'Si su archivo CSV NO contiene información sobre su(s) caja(s) de ahorros, seleccione una opción del desplegable para definir a qué cuenta pertenecen las transacciones del CSV.',
    'initial_submit'                => 'Continúe con el paso 2/3',

    // new options:
    'apply_rules_title'             => 'Apply rules',
    'apply_rules_description'       => 'Apply your rules. Note that this slows the import significantly.',
    'match_bills_title'             => 'Match bills',
    'match_bills_description'       => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // roles config
    'roles_title'                   => 'Configuración de importación (2/3) - Define el rol de cada columna',
    'roles_text'                    => 'Cada columna en su archivo CSV contiene ciertos datos. Indique qué tipo de datos debe esperar el importador. La opción de "mapear" datos significa que enlazará cada entrada encontrada en la columna con un valor en su base de datos. Una columna a menudo mapeada es la columna que contiene el IBAN de la cuenta de contrapartida. Eso puede enlazarse fácilmente con cuentas IBAN ya presentes en su base de datos.',
    'roles_table'                   => 'Tabla',
    'roles_column_name'             => 'Nombre de la columna',
    'roles_column_example'          => 'Ejemplo de datos de columna',
    'roles_column_role'             => 'Significado de los datos de la columna',
    'roles_do_map_value'            => 'Mapear estos valores',
    'roles_column'                  => 'Columna',
    'roles_no_example_data'         => 'No hay datos de ejemplo disponibles',
    'roles_submit'                  => 'Continúe con el paso 3/3',
    'roles_warning'                 => 'Como mínimo, marque una columna como la columna de importe. También es aconsejable seleccionar una columna para la descripción, fecha y la cuenta de contrapartida.',

    // map data
    'map_title'                     => 'Configuración de la importación (3/3) - Conecta los datos de importación a los datos de Firefly III',
    'map_text'                      => 'En las siguientes tablas el valor de la izquierda muestra información encontrada en el CSV cargado. Es su tarea mapear este valor, si es posible, a un valor ya presente en su base de datos. Firefly respeterá este mapeo. Si no hay un valor hacia el cual mapear o no desea mapear un valor específico, no seleccione ninguno.',
    'map_field_value'               => 'Valor del campo',
    'map_field_mapped_to'           => 'Mapeado a',
    'map_do_not_map'                => '(no mapear)',
    'map_submit'                    => 'Iniciar la importación',

    // map things.
    'column__ignore'                => '(ignorar esta columna)',
    'column_account-iban'           => 'Caja de ahorro (CBU)',
    'column_account-id'             => 'ID de la caja de ahorro (coincide con Firefly)',
    'column_account-name'           => 'Caja de ahorro (nombre)',
    'column_amount'                 => 'Monto',
    'column_amount_debet'           => 'Amount (debet column)',
    'column_amount_credit'          => 'Amount (credit column)',
    'column_amount-comma-separated' => 'Monto (coma como separador de decimales)',
    'column_bill-id'                => 'ID de factura (coincide con Firefly)',
    'column_bill-name'              => 'Nombre de factura',
    'column_budget-id'              => 'ID de presupuesto (coincide con Firefly)',
    'column_budget-name'            => 'Nombre de presupuesto',
    'column_category-id'            => 'ID de categoría (coincide con Firefly)',
    'column_category-name'          => 'Nombre de categoría',
    'column_currency-code'          => 'Código de moneda (ISO 4217)',
    'column_currency-id'            => 'ID de moneda (coincide con Firefly)',
    'column_currency-name'          => 'Nombre de moneda (coincide con Firefly)',
    'column_currency-symbol'        => 'Símbolo de moneda (coincide con Firefly)',
    'column_date-interest'          => 'Fecha de cálculo de intereses',
    'column_date-book'              => 'Fecha de registro de transacción',
    'column_date-process'           => 'Fecha de proceso de transacción',
    'column_date-transaction'       => 'Fecha',
    'column_description'            => 'Descripción',
    'column_opposing-iban'          => 'Cuenta opuesta (CBU)',
    'column_opposing-id'            => 'ID de cuenta opuesta (coincide con Firefly)',
    'column_external-id'            => 'ID externo',
    'column_opposing-name'          => 'Cuenta opuesta (nombre)',
    'column_rabo-debet-credit'      => 'Indicador de débito/crédito específico de Rabobank',
    'column_ing-debet-credit'       => 'Indicador de débito/crédito específico de ING',
    'column_sepa-ct-id'             => 'ID de transferencia de crédito end-to-end de SEPA',
    'column_sepa-ct-op'             => 'Transferencia de crédito a cuenta opuesta SEPA',
    'column_sepa-db'                => 'Débito directo SEPA',
    'column_tags-comma'             => 'Etiquetas (separadas por comas)',
    'column_tags-space'             => 'Etiquetas (separadas por espacios)',
    'column_account-number'         => 'Caja de ahorro (número de cuenta)',
    'column_opposing-number'        => 'Cuenta opuesta (número de cuenta)',
];
