let mix = require('laravel-mix');
// mix.autoload({
//                  jquery: ['$', 'window.jQuery']
//              });

//mix.extract(['jquery','chart.js','accounting','chartjs-color-string','chartjs-color','moment','color-name'],'public/v1/js/ff/vendor.js');
mix.extract([],'public/v1/js/ff/vendor.js');

mix.extend(
    'firefly_iii',
    new class {
        constructor() {
            this.toCompile = [];
        }

        /**
         * The API name for the component.
         */
        name() {
            return 'firefly_iii';
        }

        /**
         * Register the component.
         *
         * @param {*} entry
         * @param {string} output
         */
        register(entry, output) {

            entry = [].concat(entry).map(file => new File(file));
            output = new File(output);

            this.toCompile.push({ entry, output });

            Mix.bundlingJavaScript = true;
        }

        /**
         * Assets to append to the webpack entry.
         *
         * @param {Entry} entry
         */
        webpackEntry(entry) {
            this.toCompile.forEach(js => {
                entry.addFromOutput(
                    js.entry.map(file => file.path()),
                    js.output,
                    js.entry[0]
                );
            });
        }


        dependencies() {
            return ["@babel/preset-flow",'@babel/preset-react'];
        }

        /**
         * webpack rules to be appended to the master config.
         */
        webpackRules() {
            return [].concat([
                                 {
                                     test: /\.jsx?$/,
                                     exclude: /(node_modules|bower_components)/,
                                     use: [
                                         {
                                             loader: 'babel-loader',
                                             options: this.babelConfig()
                                         }
                                     ]
                                 }
                             ]);
        }

        /**
         * Babel config to be merged with Mix's defaults.
         */
        babelConfig() {
            return {
                presets: ["@babel/preset-flow",'@babel/preset-react'],
                plugins: [["@babel/plugin-proposal-class-properties"]]
            };
        }
    }()
);
mix.firefly_iii('resources/js/v1/index.js', 'public/v1/js/ff/index.js');
