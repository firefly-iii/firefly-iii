/*
 * webpack.mix.js
 * Copyright (c) 2020 james@firefly-iii.org
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

const mix = require('laravel-mix');
require('laravel-mix-bundle-analyzer');


// production
mix.webpackConfig({
                      resolve: {
                          alias: {
                              'vue$': 'vue/dist/vue.runtime.common.js'
                          }
                      }
                  });

mix
    // AUTO LOAD
    // .autoload({
    //              jquery: ['$', 'window.jQuery','jQuery']
    //          })

    // MIX IN CLASSIC SCRIPT
    // .babel([
    //              '../resources/assets/js/v2/classic/adminlte.js',
    //          ], 'public/v2/js/classic.js')

    // MIX IN CLASSIC SCRIPT
    // .scripts([
    //              '../resources/assets/js/v2/classic/adminlte.js',
    //          ], 'public/v2/js/classic.js')


    // COPY SCRIPT
    //.copy('../resources/assets/js/v2/classic/adminlte.js', 'public/v2/js/classic.js')

    // dashboard component (frontpage):
    .js('src/pages/dashboard.js', 'public/js')

    // register page
    .js('src/pages/register.js', 'public/js')

    .extract().sourceMaps()
    .sass('src/app.scss', 'public/css')

    // move to right dir
    .copy('public/js','../public/v2/js')
    .copy('fonts','../public/fonts')
    .copy('public/css','../public/v2/css')
;

