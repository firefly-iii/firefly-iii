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

let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// development:
// mix.webpackConfig({
//                       resolve: {
//                           alias: {
//                               'vue$': 'vue/dist/vue.runtime.js'
//                           }
//                       }
//                   });

// production
mix.webpackConfig({
                      resolve: {
                          alias: {
                              'vue$': 'vue/dist/vue.runtime.common.js'
                          }
                      }
                  });

//mix.sourceMaps().js('resources/assets/js/app.js', 'public/v1/js');
mix.js('resources/assets/js/app.js', 'public/v1/js');
mix.js('resources/assets/js/create_transaction.js', 'public/v1/js');
mix.js('resources/assets/js/edit_transaction.js', 'public/v1/js');
mix.js('resources/assets/js/profile.js', 'public/v1/js');