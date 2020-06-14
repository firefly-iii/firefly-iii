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
    .sass('src/app.scss', 'public/css');

if (!mix.inProduction()) {
    mix.bundleAnalyzer();
}
