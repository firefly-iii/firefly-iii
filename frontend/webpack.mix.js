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
mix.js('src/pages/dashboard.js', 'public/js').vue();
// accounts.
mix.js('src/pages/accounts/index.js', 'public/js/accounts').vue();
mix.js('src/pages/accounts/show.js', 'public/js/accounts').vue();


// transactions.
mix.js('src/pages/transactions/create.js', 'public/js/transactions')
mix.js('src/pages/transactions/edit.js', 'public/js/transactions')

// static pages
mix.js('src/pages/empty.js', 'public/js').vue();
mix.js('src/pages/register.js', 'public/js')


mix.extract().sourceMaps();

mix.sass('src/app.scss', 'public/css', [
        //
    ]);

// move to right dir
mix.copy('public/js','../public/v2/js')
    .copy('fonts','../public/fonts')
    .copy('images','../public/images')
    .copy('public/css','../public/v2/css');

