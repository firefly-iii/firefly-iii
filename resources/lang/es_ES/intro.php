<?php

/**
 * intro.php
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
    // index
    'index_intro'                                     => 'Bienvenido a la página de índice de Firefly III. Por favor tómate tu tiempo para revisar esta guía y que puedas hacerte una idea de cómo funciona Firefly III.',
    'index_accounts-chart'                            => 'Este gráfico muestra el saldo actual de tus cuentas. Puedes seleccionar las cuentas que se muestran en él desde tus preferencias.',
    'index_box_out_holder'                            => 'Esta pequeña caja y las cajas a continuación te darán una visión rápida de tu situación financiera.',
    'index_help'                                      => 'Si alguna vez necesitas ayuda en una página o formulario, pulsa este botón.',
    'index_outro'                                     => 'La mayoría de las páginas de Firefly III comenzarán con una pequeña introducción como ésta. Por favor, ponte en contacto conmigo si tienes preguntas o comentarios. ¡Disfruta!',
    'index_sidebar-toggle'                            => 'Para crear nuevas transacciones, cuentas u otros elementos, utiliza el menú bajo este icono.',
    'index_cash_account'                              => 'Estas son las cuentas creadas hasta ahora. Puedes usar la cuenta de efectivo para rastrear los gastos de efectivo pero no es obligatorio por supuesto.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Seleccione su cuenta de activos o pasivos favorita de este desplegable.',
    'transactions_create_withdrawal_destination'      => 'Seleccione una cuenta de gastos aquí. Deje en blanco si desea hacer un gasto en efectivo.',
    'transactions_create_withdrawal_foreign_currency' => 'Utilice este campo para establecer una moneda y cantidad extranjera.',
    'transactions_create_withdrawal_more_meta'        => 'Hay muchos otros metadatos que se han establecido en estos campos.',
    'transactions_create_withdrawal_split_add'        => 'Si quieres dividir una transacción, añade más divisiones con este botón',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Seleccione o escriba el pagador en este menú desplegable/cuadro de texto auto completable. Deje vacío si desea hacer un depósito en efectivo.',
    'transactions_create_deposit_destination'         => 'Seleccione una cuenta de activos o pasivos aquí.',
    'transactions_create_deposit_foreign_currency'    => 'Utilice este campo para establecer una moneda y cantidad extranjera.',
    'transactions_create_deposit_more_meta'           => 'Hay muchos otros metadatos que se han establecido en estos campos.',
    'transactions_create_deposit_split_add'           => 'Si quieres dividir una transacción, añade más divisiones con este botón',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Seleccione la cuenta de activos de origen aquí.',
    'transactions_create_transfer_destination'        => 'Seleccione la cuenta de activos de destino aquí.',
    'transactions_create_transfer_foreign_currency'   => 'Utilice este campo para establecer una moneda y cantidad extranjera.',
    'transactions_create_transfer_more_meta'          => 'Hay muchos otros metadatos que se han establecido en estos campos.',
    'transactions_create_transfer_split_add'          => 'Si quieres dividir una transacción, añade más divisiones con este botón',

    // create account:
    'accounts_create_iban'                            => 'Indica un IBAN válido en tus cuentas. Esto facilitará la importación de datos en el futuro.',
    'accounts_create_asset_opening_balance'           => 'Cuentas de ingreso deben tener un "saldo de apertura", indicando el inicio del historial de la cuenta en Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III admite múltiples divisas. Las cuentas tienen una divisa principal, que debes indicar aquí.',
    'accounts_create_asset_virtual'                   => 'A veces puede ayudar el darle a tu cuenta un balance virtual: una cantidad extra que se añade o resta siempre del balance real.',

    // budgets index
    'budgets_index_intro'                             => 'Los presupuestos se utilizan para administrar sus finanzas y son una de las funciones básicas de Firefly III.',
    'budgets_index_set_budget'                        => 'Coloque su presupuesto total para cada período y así Firefly III puede decirle si usted ha presupuestado todo el dinero disponible.',
    'budgets_index_see_expenses_bar'                  => 'Gastar dinero irá llenando poco a poco esta barra.',
    'budgets_index_navigate_periods'                  => 'Navega a través de períodos para configurar fácilmente presupuestos con anticipación.',
    'budgets_index_new_budget'                        => 'Crea nuevos presupuestos como mejor te parezca.',
    'budgets_index_list_of_budgets'                   => 'Use esta tabla para establecer las cantidades para cada presupuesto y ver cómo lo está haciendo.',
    'budgets_index_outro'                             => 'Para aprender mas acerca de los presupuestos, revise el icono de ayuda en el tope de la esquina derecha.',

    // reports (index)
    'reports_index_intro'                             => 'Utilice estos reportes para tener información detallada de sus finanzas.',
    'reports_index_inputReportType'                   => 'Escoja un tipo de reporte. Revise las páginas de ayuda para ver lo que le muestra cada reporte.',
    'reports_index_inputAccountsSelect'               => 'Usted puede excluir o incluir cuentas de activos como mejor le cuadre.',
    'reports_index_inputDateRange'                    => 'El rango de fecha seleccionada depende completamente de usted: de un día a 10 años.',
    'reports_index_extra-options-box'                 => 'Dependiendo del informe que usted haya seleccionado, puede seleccionar filtros y opciones extras aquí. Mire este recuadro cuando cambie los tipos de informes.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Este informe le dará un rápido y comprensivo resumen de sus finanzas. Si usted desea ver algo mas, ¡por favor no dude en ponerse en contacto conmigo!',
    'reports_report_audit_intro'                      => 'Este informe le dará información detallada en sus cuentas de activos.',
    'reports_report_audit_optionsBox'                 => 'Use estos recuadros de verificación para ver u ocultar las columnas que a usted le interesan.',

    'reports_report_category_intro'                  => 'Este informe le dará una idea en una o múltiples categorías.',
    'reports_report_category_pieCharts'              => 'Estos gráficos le darán una idea de sus gastos e ingresos por categoría o por cuenta.',
    'reports_report_category_incomeAndExpensesChart' => 'Estos gráficos muestran sus gastos e ingresos por categoría.',

    'reports_report_tag_intro'                  => 'Este informe le dará una idea de una o múltiples etiquetas.',
    'reports_report_tag_pieCharts'              => 'Estos gráficos le darán una idea de gastos e ingresos por etiqueta, cuenta, categoría o presupuesto.',
    'reports_report_tag_incomeAndExpensesChart' => 'Este gráfico le muestra gastos e ingresos por etiqueta.',

    'reports_report_budget_intro'                             => 'Este informe le dará una idea de uno o múltiples presupuestos.',
    'reports_report_budget_pieCharts'                         => 'Estos gráficos le darán a usted una idea de los gastos por presupuesto o por cuenta.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Este gráfico le muestra sus gastos por presupuesto.',

    // create transaction
    'transactions_create_switch_box'                          => 'Utilice estos botones para cambiar rápidamente el tipo de transacción que usted desea guardar.',
    'transactions_create_ffInput_category'                    => 'Usted puede escribir libremente en este campo. Se le sugerirán categorías creadas previamente.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Vincula su retiro con un presupuesto para un mejor control financiero.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Use esta lista desplegable cuando su retiro esté en otra moneda.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Use esta lista desplegable cuando su deposito esté en otra moneda.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Seleccione una alcancía y vincule esta transferencia con sus ahorros.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Este campo le muestra cuánto ha ahorrado usted en cada hucha.',
    'piggy-banks_index_button'                                => 'Junto con esta barra de progreso hay dos botones (+ y -) para añadir o quitar dinero de cada hucha.',
    'piggy-banks_index_accountStatus'                         => 'Para cada cuenta de activos con al menos una hucha, el estado está listado en esta tabla.',

    // create piggy
    'piggy-banks_create_name'                                 => '¿Cuál es tu meta? ¿Un nuevo sofá, una cámara, dinero para emergencias?',
    'piggy-banks_create_date'                                 => 'Usted puede establecer una fecha objetivo o una fecha limite para su alcancía.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Este informe le mostrara la historia de esta alcancía.',
    'piggy-banks_show_piggyDetails'                           => 'Algunos detalles sobre tu hucha',
    'piggy-banks_show_piggyEvents'                            => 'Cualquier adición o eliminación también se listan aquí.',

    // bill index
    'bills_index_rules'                                       => 'Aquí verá que reglas serán comprobadas si esta factura es seleccionada',
    'bills_index_paid_in_period'                              => 'Este campo indica cuando la factura fue pagada por última vez.',
    'bills_index_expected_in_period'                          => 'Este campo indica para cada factura, si y cuándo se espera que llegue la próxima factura.',

    // show bill
    'bills_show_billInfo'                                     => 'Esta tabla muestra alguna información general acerca de esta factura.',
    'bills_show_billButtons'                                  => 'Use este botón para volver a escanear transacciones viejas, para que coincidan con esta factura.',
    'bills_show_billChart'                                    => 'Este gráfico muestra las transacciones vinculadas con esta factura.',

    // create bill
    'bills_create_intro'                                      => 'Use facturas para rastrear la cantidad de dinero correspondiente a cada período. Piense en gastos como renta, seguro o pagos de hipoteca.',
    'bills_create_name'                                       => 'Use un nombre descriptivo como "alquiler" o "seguro de salud".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Seleccione un importe mínimo y máximo para esta factura.',
    'bills_create_repeat_freq_holder'                         => 'La mayoría de facturas se repiten mensualmente, pero usted puede establecer otra frecuencia aquí.',
    'bills_create_skip_holder'                                => 'Si una cuenta se repite cada 2 semanas, el campo "saltar" debe estar marcado como "1" para saltar una semana y generar el gasto cada 2.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III le permite administrar reglas que, automáticamente se aplicarán a cualquier transacción que cree o edite.',
    'rules_index_new_rule_group'                              => 'Usted puede combinar reglas en grupos para una administración mas fácil.',
    'rules_index_new_rule'                                    => 'Cree tantas reglas como usted quiera.',
    'rules_index_prio_buttons'                                => 'Ordénelos de la forma que mejor le parezca.',
    'rules_index_test_buttons'                                => 'Usted puede probar sus reglas o aplicarlas a transacciones existentes.',
    'rules_index_rule-triggers'                               => 'Las reglas tienen "disparadores" y "acciones" que usted puede ordenar arrastrando y soltando.',
    'rules_index_outro'                                       => '¡Asegúrese de revisar las páginas de ayuda usando el ícono (?) en el tope derecho!',

    // create rule:
    'rules_create_mandatory'                                  => 'Elija un título descriptivo, y establezca cuando la regla deba ser botada.',
    'rules_create_ruletriggerholder'                          => 'Añadir tantos desencadenantes como desee, pero recuerde que TODOS los desencadenantes deben coincidir antes de que cualquier acción sea eliminada.',
    'rules_create_test_rule_triggers'                         => 'Use este botón para ver cuáles transacciones coincidirán con su regla.',
    'rules_create_actions'                                    => 'Establezca tantas acciones como usted lo desee.',

    // preferences
    'preferences_index_tabs'                                  => 'Mas opciones están disponibles detrás de estas pestañas.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III da soporte a múltiples monedas, que usted puede cambiar en esta página.',
    'currencies_index_default'                                => 'Firefly III tiene una moneda por defecto.',
    'currencies_index_buttons'                                => 'Utilice estos botones para cambiar la moneda por defecto o habilitar otras monedas.',

    // create currency
    'currencies_create_code'                                  => 'Este código debe ser compatible con ISO (Googlee para su nueva moneda).',
];
