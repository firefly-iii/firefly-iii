<?php

/**
 * demo.php
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
    'no_demo_text'           => 'Lamentablemente no hay textos de ayuda para <abbr title=":route">esta página</abbr>.',
    'see_help_icon'          => 'Sin embargo, el icono <i class="fa fa-question-circle"></i> en la esquina superior derecha puede tener más información.',
    'index'                  => '¡Bienvenido a <strong>Firefly III</strong>! En esta página tendrá una vista rápida de sus finanzas. Para más información, mire sus cuentas &rarr; <a href=":asset">Cuentas de activos</a> y, por supuesto, las páginas de <a href=":budgets">presupuestos</a> e <a href=":reports">Informes</a>. O simplemente investigue la aplicación por su cuenta.',
    'accounts-index'         => 'Las cuentas de activos son tus cuentas bancarias personales. Las cuentas de gastos son las que gastas dinero, como tiendas y amigos. Las cuentas de ingresos son cuentas de las que recibes dinero, como tu trabajo, el gobierno u otras fuentes de ingresos. Los pasivos son tus deudas y préstamos como deudas de tarjetas de crédito antiguas o préstamos estudiantiles. En esta página puedes editarlas o eliminarlas.',
    'budgets-index'          => 'Esta página le muestra una visión general de sus presupuestos. La barra superior muestra la cantidad que está disponible para ser presupuestada. Esto se puede personalizar para cualquier período haciendo clic en la cantidad a la derecha. La cantidad que ha gastado hasta ahora se muestra en la barra de abajo. Debajo están los gastos por presupuesto y lo que ha presupuestado para ellos.',
    'reports-index-start'    => 'Firefly III da soporte a un buen numero de tipos de informes. Lea sobre ellos haciendo clic en el icono <i class="fa fa-question-circle"></i> en la esquina superior derecha.',
    'reports-index-examples' => 'Asegúrese de revisar estos ejemplos: <a href=":one">un resumen financiero mensual</a>, <a href=":two">un resumen financiero anual</a> y <a href=":three">una vista general del presupuesto</a>.',
    'currencies-index'       => 'Firefly III admite múltiples monedas. A pesar de que la moneda por defecto es el Euro, se puede seleccionar el Dólar de EE. UU, y muchas otras monedas. Como se puede ver se ha incluido una pequeña selección de monedas, pero puede agregar su propia moneda si lo desea. Sin embargo, cambiar la moneda predeterminada no cambiará la moneda de las transacciones existentes: Firefly III admite el uso de varias monedas al mismo tiempo.',
    'transactions-index'     => 'Estos gastos, depósitos y transferencias no son particularmente imaginativos. Se han generado automáticamente.',
    'piggy-banks-index'      => 'Como puede ver, hay tres huchas. Utilice los botones más y menos para influir en la cantidad de dinero en cada hucha. Haga clic en el nombre de la hucha para ver la administración de cada una.',
    'import-index'           => 'Cualquier archivo CSV se puede importar en Firefly III. También soporta la importación de datos desde bunq y Spectre. Otros bancos y agregadores financieros se implementarán en el futuro. Sin embargo, como usuario de la demo, solo puede ver el "falso"-proveedor en acción. Generará algunas transacciones aleatorias para mostrarle cómo funciona el proceso.',
    'profile-index'          => 'Tenga en cuenta que el sitio de demostración se restablece cada cuatro horas. Su acceso puede ser revocado en cualquier momento. Esto ocurre automáticamente y no es un error.',
];
