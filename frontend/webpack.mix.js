/*
 * webpack.mix.js
 * Copyright (c) 2021 james@firefly-iii.org
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

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */


// production
// require('laravel-mix-bundle-analyzer');
mix.webpackConfig({
                      resolve: {
                          alias: {
                              'vue$': 'vue/dist/vue.runtime.common.js'
                          }
                      }
                  });


// dashboard and empty page
mix.js('src/pages/dashboard.js', 'public/js').vue({version: 2});
// accounts.
mix.js('src/pages/accounts/index.js', 'public/js/accounts').vue({version: 2});
mix.js('src/pages/accounts/show.js', 'public/js/accounts').vue({version: 2});


// transactions.
mix.js('src/pages/transactions/create.js', 'public/js/transactions').vue({version: 2});
mix.js('src/pages/transactions/edit.js', 'public/js/transactions').vue({version: 2});

// static pages
mix.js('src/pages/empty.js', 'public/js').vue({version: 2});
mix.js('src/pages/register.js', 'public/js').vue({version: 2})


mix.extract().sourceMaps();

mix.sass('src/app.scss', 'public/css', {
    sassOptions: {
        outputStyle: 'compressed'
    }
});

// move to right dir
mix.copy('public/js', '../public/v2/js')
    .copy('fonts', '../public/fonts')
    .copy('images', '../public/images')
    .copy('public/css', '../public/v2/css');

