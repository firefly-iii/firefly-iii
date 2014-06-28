if (typeof(PhpDebugBar) == 'undefined') {
    // namespace
    var PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}

(function($) {

    /**
     * @namespace
     */
    PhpDebugBar.Widgets = {};

    /**
     * Replaces spaces with &nbsp; and line breaks with <br>
     *
     * @param {String} text
     * @return {String}
     */
    var htmlize = PhpDebugBar.Widgets.htmlize = function(text) {
        return text.replace(/\n/g, '<br>').replace(/\s/g, "&nbsp;")
    };

    /**
     * Returns a string representation of value, using JSON.stringify
     * if it's an object.
     *
     * @param {Object} value
     * @param {Boolean} prettify Uses htmlize() if true
     * @return {String}
     */
    var renderValue = PhpDebugBar.Widgets.renderValue = function(value, prettify) {
        if (typeof(value) !== 'string') {
            if (prettify) {
                return htmlize(JSON.stringify(value, undefined, 2));
            }
            return JSON.stringify(value);
        }
        return value;
    };

    /**
     * Highlights a block of code
     *
     * @param  {String} code
     * @param  {String} lang
     * @return {String}
     */
    var highlight = PhpDebugBar.Widgets.highlight = function(code, lang) {
        if (typeof(code) === 'string') {
            if (typeof(hljs) === 'undefined') {
                return htmlize(code);
            }
            if (lang) {
                return hljs.highlight(lang, code).value;
            }
            return hljs.highlightAuto(code).value;
        }

        if (typeof(hljs) === 'object') {
            code.each(function(i, e) { hljs.highlightBlock(e); });
        }
        return code;
    };

    /**
     * Creates a <pre> element with a block of code
     *
     * @param  {String} code
     * @param  {String} lang
     * @return {String}
     */
    var createCodeBlock = PhpDebugBar.Widgets.createCodeBlock = function(code, lang) {
        var pre = $('<pre />');
        $('<code />').text(code).appendTo(pre);
        if (lang) {
            pre.addClass("language-" + lang);
        }
        highlight(pre);
        return pre;
    };

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');


    // ------------------------------------------------------------------
    // Generic widgets
    // ------------------------------------------------------------------

    /**
     * Displays array element in a <ul> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    var ListWidget = PhpDebugBar.Widgets.ListWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('list'),

        initialize: function(options) {
            if (!options['itemRenderer']) {
                options['itemRenderer'] = this.itemRenderer;
            }
            this.set(options);
        },

        render: function() {
            this.bindAttr(['itemRenderer', 'data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var data = this.get('data');
                for (var i = 0; i < data.length; i++) {
                    var li = $('<li />').addClass(csscls('list-item')).appendTo(this.$el);
                    this.get('itemRenderer')(li, data[i]);
                }
            });
        },

        /**
         * Renders the content of a <li> element
         *
         * @param {jQuery} li The <li> element as a jQuery Object
         * @param {Object} value An item from the data array
         */
        itemRenderer: function(li, value) {
            li.html(renderValue(value));
        }

    });

    // ------------------------------------------------------------------

    /**
     * Displays object property/value paris in a <dl> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    var KVListWidget = PhpDebugBar.Widgets.KVListWidget = ListWidget.extend({

        tagName: 'dl',

        className: csscls('kvlist'),

        render: function() {
            this.bindAttr(['itemRenderer', 'data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var self = this;
                $.each(this.get('data'), function(key, value) {
                    var dt = $('<dt />').addClass(csscls('key')).appendTo(self.$el);
                    var dd = $('<dd />').addClass(csscls('value')).appendTo(self.$el);
                    self.get('itemRenderer')(dt, dd, key, value);
                });
            });
        },

        /**
         * Renders the content of the <dt> and <dd> elements
         *
         * @param {jQuery} dt The <dt> element as a jQuery Object
         * @param {jQuery} dd The <dd> element as a jQuery Object
         * @param {String} key Property name
         * @param {Object} value Property value
         */
        itemRenderer: function(dt, dd, key, value) {
            dt.text(key);
            dd.html(htmlize(value));
        }

    });

    // ------------------------------------------------------------------
    
    /**
     * An extension of KVListWidget where the data represents a list
     * of variables
     * 
     * Options:
     *  - data
     */
    var VariableListWidget = PhpDebugBar.Widgets.VariableListWidget = KVListWidget.extend({

        className: csscls('kvlist varlist'),

        itemRenderer: function(dt, dd, key, value) {
            $('<span />').attr('title', key).text(key).appendTo(dt);

            var v = value;
            if (v && v.length > 100) {
                v = v.substr(0, 100) + "...";
            }
            var prettyVal = null;
            dd.text(v).click(function() {
                if (dd.hasClass(csscls('pretty'))) {
                    dd.text(v).removeClass(csscls('pretty'));
                } else {
                    prettyVal = prettyVal || createCodeBlock(value);
                    dd.addClass(csscls('pretty')).empty().append(prettyVal);
                }
            });
        }

    });

    // ------------------------------------------------------------------
    
    /**
     * Iframe widget
     *
     * Options:
     *  - data
     */
    var IFrameWidget = PhpDebugBar.Widgets.IFrameWidget = PhpDebugBar.Widget.extend({

        tagName: 'iframe',

        className: csscls('iframe'),

        render: function() {
            this.$el.attr({
                seamless: "seamless",
                border: "0",
                width: "100%",
                height: "100%"
            });
            this.bindAttr('data', function(url) { this.$el.attr('src', url); });
        }

    });


    // ------------------------------------------------------------------
    // Collector specific widgets
    // ------------------------------------------------------------------

    /**
     * Widget for the MessagesCollector
     *
     * Uses ListWidget under the hood
     *
     * Options:
     *  - data
     */
    var MessagesWidget = PhpDebugBar.Widgets.MessagesWidget = PhpDebugBar.Widget.extend({

        className: csscls('messages'),

        render: function() {
            var self = this;

            this.$list = new ListWidget({ itemRenderer: function(li, value) {
                var m = value.message;
                if (m.length > 100) {
                    m = m.substr(0, 100) + "...";
                }

                var val = $('<span />').addClass(csscls('value')).text(m).appendTo(li);
                if (!value.is_string || value.message.length > 100) {
                    var prettyVal = value.message;
                    if (!value.is_string) {
                        prettyVal = null;
                    }
                    li.css('cursor', 'pointer').click(function() {
                        if (val.hasClass(csscls('pretty'))) {
                            val.text(m).removeClass(csscls('pretty'));
                        } else {
                            prettyVal = prettyVal || createCodeBlock(value.message, 'php');
                            val.addClass(csscls('pretty')).empty().append(prettyVal);
                        }
                    });
                }

                if (value.label) {
                    val.addClass(csscls(value.label));
                    $('<span />').addClass(csscls('label')).text(value.label).appendTo(li);
                }
                if (value.collector) {
                    $('<span />').addClass(csscls('collector')).text(value.collector).appendTo(li);
                }
            }});

            this.$list.$el.appendTo(this.$el);
            this.$toolbar = $('<div><i class="fa fa-search"></i></div>').addClass(csscls('toolbar')).appendTo(this.$el);

            $('<input type="text" />')
                .on('change', function() { self.set('search', this.value); })
                .appendTo(this.$toolbar);

            this.bindAttr('data', function(data) {
                this.set({ exclude: [], search: '' });
                this.$toolbar.find(csscls('.filter')).remove();

                var filters = [], self = this;
                for (var i = 0; i < data.length; i++) {
                    if (!data[i].label || $.inArray(data[i].label, filters) > -1) {
                        continue;
                    }
                    filters.push(data[i].label);
                    $('<a href="javascript:" />')
                        .addClass(csscls('filter'))
                        .text(data[i].label)
                        .attr('rel', data[i].label)
                        .on('click', function() { self.onFilterClick(this); })
                        .appendTo(this.$toolbar);
                }
            });

            this.bindAttr(['exclude', 'search'], function() {
                var data = this.get('data'),
                    exclude = this.get('exclude'), 
                    search = this.get('search'),
                    fdata = [];

                for (var i = 0; i < data.length; i++) {
                    if ((!data[i].label || $.inArray(data[i].label, exclude) === -1) && (!search || data[i].message.indexOf(search) > -1)) {
                        fdata.push(data[i]);
                    }
                }

                this.$list.set('data', fdata);
            });
        },

        onFilterClick: function(el) {
            $(el).toggleClass(csscls('excluded'));

            var excludedLabels = [];
            this.$toolbar.find(csscls('.filter') + csscls('.excluded')).each(function() {
                excludedLabels.push(this.rel);
            });

            this.set('exclude', excludedLabels);
        }

    });

    // ------------------------------------------------------------------

    /**
     * Widget for the TimeDataCollector
     *
     * Options:
     *  - data
     */
    var TimelineWidget = PhpDebugBar.Widgets.TimelineWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('timeline'),

        render: function() {
            this.bindAttr('data', function(data) {
                this.$el.empty();
                if (data.measures) {
                    for (var i = 0; i < data.measures.length; i++) {
                        var li = $('<li />').addClass(csscls('measure')),
                            left = Math.round(data.measures[i].relative_start * 100 / data.duration),
                            width = Math.min(Math.round(data.measures[i].duration * 100 / data.duration), 100 - left);

                        li.append($('<span />').addClass(csscls('value')).css({
                            left: left + "%",
                            width: width + "%"
                        }));
                        li.append($('<span />').addClass(csscls('label')).text(data.measures[i].label + " (" + data.measures[i].duration_str + ")"));
                        this.$el.append(li);
                    }
                }
            });
        }

    });

    // ------------------------------------------------------------------
    
    /**
     * Widget for the displaying exceptions
     *
     * Options:
     *  - data
     */
    var ExceptionsWidget = PhpDebugBar.Widgets.ExceptionsWidget = PhpDebugBar.Widget.extend({

        className: csscls('exceptions'),

        render: function() {
            this.$list = new ListWidget({ itemRenderer: function(li, e) {
                $('<span />').addClass(csscls('message')).text(e.message).appendTo(li);
                if (e.file) {
                    $('<span />').addClass(csscls('filename')).text(e.file + "#" + e.line).appendTo(li);
                }
                if (e.type) {
                    $('<span />').addClass(csscls('type')).text(e.type).appendTo(li);
                }
                if (e.surrounding_lines) {
                    var pre = createCodeBlock(e.surrounding_lines.join(""), 'php').addClass(csscls('file')).appendTo(li);
                    li.click(function() {
                        if (pre.is(':visible')) {
                            pre.hide();
                        } else {
                            pre.show();
                        }
                    });
                }
            }});
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function(data) {
                this.$list.set('data', data);
                if (data.length == 1) {
                    this.$list.$el.children().first().find(csscls('.file')).show();
                }
            });

        }

    });
    

})(PhpDebugBar.$);
