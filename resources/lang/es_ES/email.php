<?php

/**
 * email.php
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
    // common items
    'greeting'                         => 'Hola,',
    'closing'                          => 'Bip bop,',
    'signature'                        => 'El Robot de Correo de Firefly III',
    'footer_ps'                        => 'PD: Este mensaje fue enviado porque una solicitud de IP :ipAddress lo activó.',

    // admin test
    'admin_test_subject'               => 'Un mensaje de prueba de su instalación de Firefly III',
    'admin_test_body'                  => 'Este es un mensaje de prueba de tu instancia de Firefly III. Fue enviado a :email.',

    // new IP
    'login_from_new_ip'                => 'Nuevo inicio de sesión en Firefly III',
    'new_ip_body'                      => 'Firefly III detectó un nuevo inicio de sesión en su cuenta desde una dirección IP desconocida. Si nunca ha iniciado sesión desde la dirección IP de abajo, o fué hace más de seis meses, Firefly III le avisará.',
    'new_ip_warning'                   => 'Si reconoce esta dirección IP o el inicio de sesión, puede ignorar este mensaje. Si no ha iniciado sesión, o sí no tiene idea de qué es esto, verifique la seguridad de su contraseña, cámbiela y cierre todas las demás sesiones. Para hacer esto, valla a su página de perfil. Por supuesto que ya tiene A2F habilitado, ¿verdad? ¡Manténgase seguro!',
    'ip_address'                       => 'Dirección IP',
    'host_name'                        => 'Servidor',
    'date_time'                        => 'Fecha + hora',

    // access token created
    'access_token_created_subject'     => 'Se ha creado un nuevo token de acceso',
    'access_token_created_body'        => 'Alguien (esperemos que usted) acaba de crear un nuevo token de acceso a la API de Firefly III para tu cuenta de usuario.',
    'access_token_created_explanation' => 'Con este token, pueden acceder a <strong>todos</strong> sus registros financieros a través de la API de Firefly III.',
    'access_token_created_revoke'      => 'Si no fue usted, por favor revoca este token tan pronto como sea posible en :url.',

    // registered
    'registered_subject'               => 'Bienvenido a Firefly III!',
    'registered_welcome'               => 'Bienvenido a <a style="color:#337ab7" href=":address">Firefly III</a>. Se ha hecho su registro, y este correo electrónico está aquí para confirmarlo. ¡Si!',
    'registered_pw'                    => 'Si ya ha olvidado su contraseña, por favor restáurela usando <a style="color:#337ab7" href=":address/password/reset">la herramienta de restablecimiento de contraseña</a>.',
    'registered_help'                  => 'Hay un icono de ayuda en la esquina superior derecha de cada página. Si necesita ayuda, ¡Haga clic en él!',
    'registered_doc_html'              => 'Si aún no lo ha hecho, por favor lea la <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">gran teoría</a>.',
    'registered_doc_text'              => 'Si aún no lo ha hecho, por favor lea la primera guía de uso y la descripción completa.',
    'registered_closing'               => '¡Disfrute!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Restablecer contraseña:',
    'registered_doc_link'              => 'Documentación:',

    // email change
    'email_change_subject'             => 'Se cambió su dirección de email de Firefly III',
    'email_change_body_to_new'         => 'Usted o alguien con acceso a su cuenta de Firefly III ha cambiado su dirección de correo electrónico. Si no esperabas este mensaje, por favor ignórelo y elimínelo.',
    'email_change_body_to_old'         => 'Usted o alguien con acceso a su cuenta de Firefly III ha cambiado su dirección de correo electrónico. Si no esperaba que esto suceda, <strong>debe</strong> seguir el enlace "deshacer" para proteger tu cuenta.',
    'email_change_ignore'              => 'Si inició este cambio, puede ignorar este mensaje de forma segura.',
    'email_change_old'                 => 'La antigua dirección de correo electrónico era: :email',
    'email_change_old_strong'          => 'La antigua dirección de correo electrónico era: <strong>:email</strong>',
    'email_change_new'                 => 'La nueva dirección de correo es: :email',
    'email_change_new_strong'          => 'La nueva dirección de correo electrónico es: <strong>:email</strong>',
    'email_change_instructions'        => 'No puede usar Firefly III hasta que confirme este cambio. Por favor, siga el enlace de abajo para hacerlo.',
    'email_change_undo_link'           => 'Para deshacer el cambio, siga este enlace:',

    // OAuth token created
    'oauth_created_subject'            => 'Se ha creado un nuevo cliente OAuth',
    'oauth_created_body'               => 'Alguien (esperemos que usted) acaba de crear un nuevo cliente API OAuth de Firefly III para su cuenta de usuario. Está etiquetado como ":name" y tiene la URL de devolución de llamada <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Con este cliente, puede acceder a <strong>todos</strong> sus registros financieros a través de la API de Firefly III.',
    'oauth_created_undo'               => 'Si no fue usted, por favor revoca este cliente tan pronto como sea posible en :url.',

    // reset password
    'reset_pw_subject'                 => 'Su solicitud de restablecimiento de contraseña',
    'reset_pw_instructions'            => 'Alguien intentó restablecer su contraseña. Si fue usted, por favor siga el enlace de abajo para hacerlo.',
    'reset_pw_warning'                 => '<strong>¡POR FAVOR</strong> verifique que el enlace vaya a Firefly III que espera que vaya!',

    // error
    'error_subject'                    => 'Ocurrió un error en Firefly III',
    'error_intro'                      => 'Firefly III v:version tuvo un error: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'El error fue de tipo ":class".',
    'error_timestamp'                  => 'El error ocurrió el: :time.',
    'error_location'                   => 'Este error ocurrió en el archivo "<span style="font-family: monospace;">:file</span>" en línea :line con código :code.',
    'error_user'                       => 'El error fue encontrado por el usuario #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'No había ningún usuario conectado para este error o no se detectó ningún usuario.',
    'error_ip'                         => 'La dirección IP relacionada con este error es: :ip',
    'error_url'                        => 'La URL es: :url',
    'error_user_agent'                 => 'Agente de usuario: :userAgent',
    'error_stacktrace'                 => 'El stacktrace completo está a continuación. Si cree que esto es un error en Firefly III, puede reenviar este mensaje a <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Esto puede ayudar a solucionar el error que acaba de encontrar.',
    'error_github_html'                => 'Si prefiere, también puede abrir un nuevo issue en <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Si prefiere, también puedes abrir un nuevo problema en https://github.com/firefly-iiii/firefly-iiii/issues.',
    'error_stacktrace_below'           => 'El stacktrace completo está a continuación:',

    // report new journals
    'new_journals_subject'             => 'Firefly III ha creado una nueva transacción|Firefly III ha creado :count nuevas transacciones',
    'new_journals_header'              => 'Firefly III ha creado una transacción para usted. La puede encontrar en su instalación de Firefly III:|Firefly III ha creado :count transacciones para usted. Las puede encontrar en su instalación de Firefly III:',
];
