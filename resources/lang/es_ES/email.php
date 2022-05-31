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
    'greeting'                                => 'Hola,',
    'closing'                                 => 'Bip bop,',
    'signature'                               => 'El Robot de Correo de Firefly III',
    'footer_ps'                               => 'PD: Este mensaje fue enviado porque una solicitud de IP :ipAddress lo activó.',

    // admin test
    'admin_test_subject'                      => 'Un mensaje de prueba de su instalación de Firefly III',
    'admin_test_body'                         => 'Este es un mensaje de prueba de tu instancia de Firefly III. Fue enviado a :email.',

    // new IP
    'login_from_new_ip'                       => 'Nuevo inicio de sesión en Firefly III',
    'new_ip_body'                             => 'Firefly III detectó un nuevo inicio de sesión en su cuenta desde una dirección IP desconocida. Si nunca ha iniciado sesión desde la dirección IP de abajo, o fué hace más de seis meses, Firefly III le avisará.',
    'new_ip_warning'                          => 'Si reconoce esta dirección IP o el inicio de sesión, puede ignorar este mensaje. Si no ha iniciado sesión, o sí no tiene idea de qué es esto, verifique la seguridad de su contraseña, cámbiela y cierre todas las demás sesiones. Para hacer esto, valla a su página de perfil. Por supuesto que ya tiene A2F habilitado, ¿verdad? ¡Manténgase seguro!',
    'ip_address'                              => 'Dirección IP',
    'host_name'                               => 'Servidor',
    'date_time'                               => 'Fecha + hora',

    // access token created
    'access_token_created_subject'            => 'Se ha creado un nuevo token de acceso',
    'access_token_created_body'               => 'Alguien (esperemos que usted) acaba de crear un nuevo token de acceso a la API de Firefly III para tu cuenta de usuario.',
    'access_token_created_explanation'        => 'Con este token, pueden acceder a **todos** sus registros financieros a través de la API de Firefly III.',
    'access_token_created_revoke'             => 'Si no era usted, por favor, revoque este token tan pronto como sea posible, en :url',

    // registered
    'registered_subject'                      => 'Bienvenido a Firefly III!',
    'registered_welcome'                      => 'Bienvenido/a a [Firefly III](:address). Su registro se ha realizado correctamente, y este correo electrónico está aquí para confirmarlo. ¡Yeah!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'Hay un icono de ayuda en la esquina superior derecha de cada página. Si necesita ayuda, ¡Haga clic en él!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => '¡Disfrute!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Restablecer contraseña:',
    'registered_doc_link'                     => 'Documentación:',

    // email change
    'email_change_subject'                    => 'Se cambió su dirección de email de Firefly III',
    'email_change_body_to_new'                => 'Usted o alguien con acceso a su cuenta de Firefly III ha cambiado su dirección de correo electrónico. Si no esperabas este mensaje, por favor ignórelo y elimínelo.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'Si inició este cambio, puede ignorar este mensaje de forma segura.',
    'email_change_old'                        => 'La antigua dirección de correo electrónico era: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'La nueva dirección de correo es: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'No puede usar Firefly III hasta que confirme este cambio. Por favor, siga el enlace de abajo para hacerlo.',
    'email_change_undo_link'                  => 'Para deshacer el cambio, siga este enlace:',

    // OAuth token created
    'oauth_created_subject'                   => 'Se ha creado un nuevo cliente OAuth',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'Su solicitud de restablecimiento de contraseña',
    'reset_pw_instructions'                   => 'Alguien intentó restablecer su contraseña. Si fue usted, por favor siga el enlace de abajo para hacerlo.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Ocurrió un error en Firefly III',
    'error_intro'                             => 'Firefly III v:version tuvo un error: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'El error fue de tipo ":class".',
    'error_timestamp'                         => 'El error ocurrió el: :time.',
    'error_location'                          => 'Este error ocurrió en el archivo "<span style="font-family: monospace;">:file</span>" en línea :line con código :code.',
    'error_user'                              => 'El error fue encontrado por el usuario #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'No había ningún usuario conectado para este error o no se detectó ningún usuario.',
    'error_ip'                                => 'La dirección IP relacionada con este error es: :ip',
    'error_url'                               => 'La URL es: :url',
    'error_user_agent'                        => 'Agente de usuario: :userAgent',
    'error_stacktrace'                        => 'El stacktrace completo está a continuación. Si cree que esto es un error en Firefly III, puede reenviar este mensaje a <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Esto puede ayudar a solucionar el error que acaba de encontrar.',
    'error_github_html'                       => 'Si prefiere, también puede abrir un nuevo issue en <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'Si prefiere, también puedes abrir un nuevo problema en https://github.com/firefly-iiii/firefly-iiii/issues.',
    'error_stacktrace_below'                  => 'El stacktrace completo está a continuación:',
    'error_headers'                           => 'Los siguientes encabezados también pueden ser relevantes:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III ha creado una nueva transacción|Firefly III ha creado :count nuevas transacciones',
    'new_journals_header'                     => 'Firefly III ha creado una transacción para usted. La puede encontrar en su instalación de Firefly III:|Firefly III ha creado :count transacciones para usted. Las puede encontrar en su instalación de Firefly III:',

    // bill warning
    'bill_warning_subject_end_date'           => 'Your bill ":name" is due to end in :diff days',
    'bill_warning_subject_now_end_date'       => 'Your bill ":name" is due to end TODAY',
    'bill_warning_subject_extension_date'     => 'Your bill ":name" is due to be extended or cancelled in :diff days',
    'bill_warning_subject_now_extension_date' => 'Your bill ":name" is due to be extended or cancelled TODAY',
    'bill_warning_end_date'                   => 'Your bill **":name"** is due to end on :date. This moment will pass in about **:diff days**.',
    'bill_warning_extension_date'             => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass in about **:diff days**.',
    'bill_warning_end_date_zero'              => 'Your bill **":name"** is due to end on :date. This moment will pass **TODAY!**',
    'bill_warning_extension_date_zero'        => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass **TODAY!**',
    'bill_warning_please_action'              => 'Please take the appropriate action.',

];
