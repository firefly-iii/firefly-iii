let mix = require('laravel-mix');
let Assert = require('laravel-mix/src/Assert');
let glob = require('glob');

mix.extend(
    'foo',
    new class {

        constructor() {
            this.toCompile = [];
        }
        /**
         * The API name for the component.
         */
        name() {
            return 'foo';
        }
        register(entry, output) {
            if (typeof entry === 'string' && entry.includes('*')) {
                entry = glob.sync(entry);
            }

            Assert.js(entry, output);

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


mix.foo('resources/js/app.js', 'public/v2/assets/js').sass('resources/sass/app.scss', 'public/v2/assets/css');
