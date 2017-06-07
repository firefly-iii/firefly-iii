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

    'import_configure_title' => 'Configurar su importación',
    'import_configure_intro' => 'Hay algunas opciones para su importación desde CSV. Por facor indique si su CSV contiene encabezados en la primera fila, y cuál es el formato de fecha utilizado. ¡Puede requerir un poco de experimentación! El delimitador de campos es usualmente ",", pero también puede ser ";". Verifíquelo cuidadosamente.',
    'import_configure_form'  => 'Opciones básicas de importación desde CSV',
    'header_help'            => 'Marque aquí si el CSV contiene títulos de columna en la primera fila',
    'date_help'              => 'Formato de fecha y hora en el CSV. Siga el formato que <a href="https://secure.php.net/manual/es/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta página</a> indica. El valor por defecto interpretará fechas que se vean así: :dateExample.',
    'delimiter_help'         => 'Elija el delimitador de campos del archivo de entrada. Si no está seguro, la coma es la opción más segura.',
    'import_account_help'    => 'Si el archivo NO contiene información sobre su(s) caja(s) de ahorros seleccion una opción para definir a qué cuenta pertenecen las transacciones del CSV.',
    'upload_not_writeable'   => 'El texto en gris indica un directorio. Debe tener permiso de escritura. Por favor verifíquelo.',

    // roles
    'column_roles_title'     => 'Definir roles de las columnas',
    'column_roles_table'     => 'Tabla',
    'column_name'            => 'Nombre de la columna',
    'column_example'         => 'Ejemplo de datos de columna',
    'column_role'            => 'Significado de los datos de la columna',
    'do_map_value'           => 'Mapear estos valores',
    'column'                 => 'Columna',
    'no_example_data'        => 'No hay datos de ejemplo disponibles',
    'store_column_roles'     => 'Continuar importación',
    'do_not_map'             => '(no mapear)',
    'map_title'              => 'Conectar datos de importación con datos de Firefly-III',
    'map_text'               => 'En las siguientes tablas el valor de la izquierda muestra información encontrada en el CSV cargado. Es su tarea mapear este valor, si es posible, a un valor ya presente en su base de datos. Firefly respeterá este mapeo. Si no hay un valor hacia el cual mapear o no desea mapear un valor específico, no seleccione ninguno.',

    'field_value'          => 'Valor del campo',
    'field_mapped_to'      => 'Mapeado a',
    'store_column_mapping' => 'Guardar mapeo',

    // map things.


    'column__ignore'                => '(ignorar esta columna)',
    'column_account-iban'           => 'Caja de ahorro (CBU)',
    'column_account-id'             => 'ID de la caja de ahorro (coincide con Firefly)',
    'column_account-name'           => 'Caja de ahorro (nombre)',
    'column_amount'                 => 'Monto',
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