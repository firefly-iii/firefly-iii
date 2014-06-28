if (typeof(PhpDebugBar) == 'undefined') {
    // namespace
    var PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}

(function($) {

    if (typeof(localStorage) == 'undefined') {
        // provide mock localStorage object for dumb browsers
        localStorage = {
            setItem: function(key, value) {},
            getItem: function(key) { return null; }
        };
    }

    if (typeof(PhpDebugBar.utils) == 'undefined') {
        PhpDebugBar.utils = {};
    }

    /**
     * Returns the value from an object property.
     * Using dots in the key, it is possible to retrieve nested property values
     *
     * @param {Object} dict
     * @param {String} key
     * @param {Object} default_value
     * @return {Object}
     */
    var getDictValue = PhpDebugBar.utils.getDictValue = function(dict, key, default_value) {
        var d = dict, parts = key.split('.');
        for (var i = 0; i < parts.length; i++) {
            if (!d[parts[i]]) {
                return default_value;
            }
            d = d[parts[i]];
        }
        return d;
    }

    /**
     * Counts the number of properties in an object
     *
     * @param {Object} obj
     * @return {Integer}
     */
    var getObjectSize = PhpDebugBar.utils.getObjectSize = function(obj) {
        if (Object.keys) {
            return Object.keys(obj).length;
        }
        var count = 0;
        for (var k in obj) {
            if (obj.hasOwnProperty(k)) {
                count++;
            }
        }
        return count;
    }

    /**
     * Returns a prefixed css class name
     *
     * @param {String} cls
     * @return {String}
     */
    PhpDebugBar.utils.csscls = function(cls, prefix) {
        if (cls.indexOf(' ') > -1) {
            var clss = cls.split(' '), out = [];
            for (var i = 0, c = clss.length; i < c; i++) {
                out.push(PhpDebugBar.utils.csscls(clss[i], prefix));
            }
            return out.join(' ');
        }
        if (cls.indexOf('.') === 0) {
            return '.' + prefix + cls.substr(1);
        }
        return prefix + cls;
    };

    /**
     * Creates a partial function of csscls where the second
     * argument is already defined
     *
     * @param  {string} prefix
     * @return {Function}
     */
    PhpDebugBar.utils.makecsscls = function(prefix) {
        var f = function(cls) {
            return PhpDebugBar.utils.csscls(cls, prefix);
        };
        return f;
    }

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-');


    // ------------------------------------------------------------------
    
    /**
     * Base class for all elements with a visual component
     *
     * @param {Object} options
     * @constructor
     */
    var Widget = PhpDebugBar.Widget = function(options) {
        this._attributes = $.extend({}, this.defaults);
        this._boundAttributes = {};
        this.$el = $('<' + this.tagName + ' />');
        if (this.className) {
            this.$el.addClass(this.className);
        }
        this.initialize.apply(this, [options || {}]);
        this.render.apply(this);
    };

    $.extend(Widget.prototype, {

        tagName: 'div',

        className: null,

        defaults: {},

        /**
         * Called after the constructor
         * 
         * @param {Object} options
         */
        initialize: function(options) {
            this.set(options);
        },

        /**
         * Called after the constructor to render the element
         */
        render: function() {},

        /**
         * Sets the value of an attribute
         * 
         * @param {String} attr Can also be an object to set multiple attributes at once
         * @param {Object} value
         */
        set: function(attr, value) {
            if (typeof(attr) != 'string') {
                for (var k in attr) {
                    this.set(k, attr[k]);
                }
                return;
            }

            this._attributes[attr] = value;
            if (typeof(this._boundAttributes[attr]) !== 'undefined') {
                for (var i = 0, c = this._boundAttributes[attr].length; i < c; i++) {
                    this._boundAttributes[attr][i].apply(this, [value]);
                }
            }
        },

        /**
         * Checks if an attribute exists and is not null
         * 
         * @param {String} attr
         * @return {[type]} [description]
         */
        has: function(attr) {
            return typeof(this._attributes[attr]) !== 'undefined' && this._attributes[attr] !== null;
        },

        /**
         * Returns the value of an attribute
         * 
         * @param {String} attr
         * @return {Object}
         */
        get: function(attr) {
            return this._attributes[attr];
        },

        /**
         * Registers a callback function that will be called whenever the value of the attribute changes
         *
         * If cb is a jQuery element, text() will be used to fill the element
         * 
         * @param {String} attr
         * @param {Function} cb
         */
        bindAttr: function(attr, cb) {
            if ($.isArray(attr)) {
                for (var i = 0, c = attr.length; i < c; i++) {
                    this.bindAttr(attr[i], cb);
                }
                return;
            }

            if (typeof(this._boundAttributes[attr]) == 'undefined') {
                this._boundAttributes[attr] = [];
            }
            if (typeof(cb) == 'object') {
                var el = cb;
                cb = function(value) { el.text(value || ''); };
            }
            this._boundAttributes[attr].push(cb);
            if (this.has(attr)) {
                cb.apply(this, [this._attributes[attr]]);
            }
        }

    });


    /**
     * Creates a subclass
     *
     * Code from Backbone.js
     * 
     * @param {Array} props Prototype properties
     * @return {Function}
     */
    Widget.extend = function(props) {
        var parent = this;

        var child = function() { return parent.apply(this, arguments); };
        $.extend(child, parent);

        var Surrogate = function(){ this.constructor = child; };
        Surrogate.prototype = parent.prototype;
        child.prototype = new Surrogate;
        $.extend(child.prototype, props);

        child.__super__ = parent.prototype;

        return child;
    };

    // ------------------------------------------------------------------

    /**
     * Tab
     * 
     * A tab is composed of a tab label which is always visible and
     * a tab panel which is visible only when the tab is active.
     *
     * The panel must contain a widget. A widget is an object which has
     * an element property containing something appendable to a jQuery object.
     *
     * Options:
     *  - title
     *  - badge
     *  - widget
     *  - data: forward data to widget data
     */
    var Tab = Widget.extend({

        className: csscls('panel'),

        render: function() {
            this.$tab = $('<a href="javascript:" />').addClass(csscls('tab'));

            this.$icon = $('<i />').appendTo(this.$tab);
            this.bindAttr('icon', function(icon) {
                if (icon) {
                    this.$icon.attr('class', 'fa fa-' + icon);
                } else {
                    this.$icon.attr('class', '');
                }
            });

            this.bindAttr('title', $('<span />').addClass(csscls('text')).appendTo(this.$tab));

            this.$badge = $('<span />').addClass(csscls('badge')).appendTo(this.$tab);
            this.bindAttr('badge', function(value) {
                if (value !== null) {
                    this.$badge.text(value);
                    this.$badge.show();
                } else {
                    this.$badge.hide();
                }
            });

            this.bindAttr('widget', function(widget) {
                this.$el.empty().append(widget.$el);
            });

            this.bindAttr('data', function(data) {
                if (this.has('widget')) {
                    this.get('widget').set('data', data);
                }
            })
        }

    });

    // ------------------------------------------------------------------

    /**
     * Indicator
     *
     * An indicator is a text and an icon to display single value information
     * right inside the always visible part of the debug bar
     *
     * Options:
     *  - icon
     *  - title
     *  - tooltip
     *  - data: alias of title
     */
    var Indicator = Widget.extend({

        tagName: 'span',

        className: csscls('indicator'),

        render: function() {
            this.$icon = $('<i />').appendTo(this.$el);
            this.bindAttr('icon', function(icon) {
                if (icon) {
                    this.$icon.attr('class', 'fa fa-' + icon);
                } else {
                    this.$icon.attr('class', '');
                }
            });

            this.bindAttr(['title', 'data'], $('<span />').addClass(csscls('text')).appendTo(this.$el));

            this.$tooltip = $('<span />').addClass(csscls('tooltip disabled')).appendTo(this.$el);
            this.bindAttr('tooltip', function(tooltip) {
                if (tooltip) {
                    this.$tooltip.text(tooltip).removeClass(csscls('disabled'));
                } else {
                    this.$tooltip.addClass(csscls('disabled'));
                }
            });
        }

    });

    // ------------------------------------------------------------------

    /**
     * Dataset title formater
     *
     * Formats the title of a dataset for the select box
     */
    var DatasetTitleFormater = PhpDebugBar.DatasetTitleFormater = function(debugbar) {
        this.debugbar = debugbar;
    };

    $.extend(DatasetTitleFormater.prototype, {

        /**
         * Formats the title of a dataset
         * 
         * @this {DatasetTitleFormater}
         * @param {String} id
         * @param {Object} data
         * @param {String} suffix
         * @return {String}
         */
        format: function(id, data, suffix) {
            if (suffix) {
                suffix = ' ' + suffix;
            } else {
                suffix = '';
            }

            var nb = getObjectSize(this.debugbar.datasets) + 1;

            if (typeof(data['__meta']) === 'undefined') {
                return "#" + nb + suffix;
            }

            var filename = data['__meta']['uri'].substr(data['__meta']['uri'].lastIndexOf('/') + 1);
            var label = "#" + nb + " " + filename + suffix + ' (' + data['__meta']['datetime'].split(' ')[1] + ')';
            return label;
        }

    });

    // ------------------------------------------------------------------


    /**
     * DebugBar
     *
     * Creates a bar that appends itself to the body of your page
     * and sticks to the bottom.
     *
     * The bar can be customized by adding tabs and indicators.
     * A data map is used to fill those controls with data provided
     * from datasets.
     */
    var DebugBar = PhpDebugBar.DebugBar = Widget.extend({

        className: "phpdebugbar " + csscls('minimized'),

        options: {
            bodyPaddingBottom: true
        },

        initialize: function() {
            this.controls = {};
            this.dataMap = {};
            this.datasets = {};
            this.firstTabName = null;
            this.activePanelName = null;
            this.datesetTitleFormater = new DatasetTitleFormater(this);
            this.registerResizeHandler();
        },

        /**
         * Register resize event, for resize debugbar with reponsive css.
         *
         * @this {DebugBar}
         */
        registerResizeHandler: function() {
            var f = this.resize.bind(this);
            this.respCSSSize = 0;
            $(window).resize(f);
            setTimeout(f, 20);
        },

        /**
         * Resizes the debugbar to fit the current browser window
         */
        resize: function() {
            var contentSize = this.respCSSSize;
            if (this.respCSSSize == 0) {
                this.$header.find("> div > *:visible").each(function () {
                    contentSize += $(this).outerWidth();
                });
            }

            var currentSize = this.$header.width();
            var cssClass = "phpdebugbar-mini-design";
            var bool = this.$header.hasClass(cssClass);

            if (currentSize <= contentSize && !bool) {
                this.respCSSSize = contentSize;
                this.$header.addClass(cssClass);
            } else if (contentSize < currentSize && bool) {
                this.respCSSSize = 0;
                this.$header.removeClass(cssClass);
            }
        },

        /**
         * Initialiazes the UI
         *
         * @this {DebugBar}
         */
        render: function() {
            var self = this;
            this.$el.appendTo('body');
            this.$resizehdle = $('<div />').addClass(csscls('resize-handle')).appendTo(this.$el);
            this.$header = $('<div />').addClass(csscls('header')).appendTo(this.$el);
            this.$headerLeft = $('<div />').addClass(csscls('header-left')).appendTo(this.$header);
            this.$headerRight = $('<div />').addClass(csscls('header-right')).appendTo(this.$header);
            var $body = this.$body = $('<div />').addClass(csscls('body')).appendTo(this.$el);
            this.recomputeBottomOffset();

            // dragging of resize handle
            var dragging = false;
            this.$resizehdle.on('mousedown', function(e) {
                var orig_h = $body.height(), pos_y = e.pageY;
                dragging = true;

                $body.parents().on('mousemove', function(e) {
                    if (dragging) {
                        var h = orig_h + (pos_y - e.pageY);
                        $body.css('height', h);
                        localStorage.setItem('phpdebugbar-height', h);
                        self.recomputeBottomOffset();
                    }
                }).on('mouseup', function() {
                    dragging = false;
                });

                e.preventDefault();
            });
            
            // minimize button
            this.$closebtn = $('<a href="javascript:" />').addClass(csscls('close-btn')).appendTo(this.$headerRight);
            this.$closebtn.click(function() {
                self.close();
            });

            // minimize button
            this.$restorebtn = $('<a href="javascript:" />').addClass(csscls('restore-btn')).hide().appendTo(this.$el);
            this.$restorebtn.click(function() {
                self.restore();
            });

            // open button
            this.$openbtn = $('<a href="javascript:" />').addClass(csscls('open-btn')).appendTo(this.$headerRight).hide();
            this.$openbtn.click(function() {
                self.openHandler.show(function(id, dataset) {
                    self.addDataSet(dataset, id, "(opened)");
                    self.showTab();
                });
            });

            // select box for data sets
            this.$datasets = $('<select />').addClass(csscls('datasets-switcher')).appendTo(this.$headerRight);
            this.$datasets.change(function() {
                self.dataChangeHandler(self.datasets[this.value]);
                self.showTab();
            });
        },

        /**
         * Restores the state of the DebugBar using localStorage
         * This is not called by default in the constructor and
         * needs to be called by subclasses in their init() method
         *
         * @this {DebugBar}
         */
        restoreState: function() {
            // bar height
            var height = localStorage.getItem('phpdebugbar-height');
            if (height) {
                this.$body.css('height', height);
            } else {
                localStorage.setItem('phpdebugbar-height', this.$body.height());
            }

            // bar visibility
            var open = localStorage.getItem('phpdebugbar-open');
            if (open && open == '0') {
                this.close();
            } else {
                var visible = localStorage.getItem('phpdebugbar-visible');
                if (visible && visible == '1') {
                    var tab = localStorage.getItem('phpdebugbar-tab');
                    if (this.isTab(tab)) {
                        this.showTab(tab);
                    }
                }
            }
        },

        /**
         * Creates and adds a new tab
         *
         * @this {DebugBar}
         * @param {String} name Internal name
         * @param {Object} widget A widget object with an element property
         * @param {String} title The text in the tab, if not specified, name will be used
         * @return {Tab}
         */
        createTab: function(name, widget, title) {
            var tab = new Tab({
                title: title || (name.replace(/[_\-]/g, ' ').charAt(0).toUpperCase() + name.slice(1)), 
                widget: widget
            });
            return this.addTab(name, tab);
        },

        /**
         * Adds a new tab
         *
         * @this {DebugBar}
         * @param {String} name Internal name
         * @param {Tab} tab Tab object
         * @return {Tab}
         */
        addTab: function(name, tab) {
            if (this.isControl(name)) {
                throw new Error(name + ' already exists');
            }

            var self = this;
            tab.$tab.appendTo(this.$headerLeft).click(function() {
                if (!self.isMinimized() && self.activePanelName == name) {
                    self.minimize();
                } else {
                    self.showTab(name);
                }
            });
            tab.$el.appendTo(this.$body);

            this.controls[name] = tab;
            if (this.firstTabName == null) {
                this.firstTabName = name;
            }
            return tab;
        },

        /**
         * Creates and adds an indicator
         *
         * @this {DebugBar}
         * @param {String} name Internal name
         * @param {String} icon
         * @param {String} tooltip
         * @param {String} position "right" or "left", default is "right"
         * @return {Indicator}
         */
        createIndicator: function(name, icon, tooltip, position) {
            var indicator = new Indicator({
                icon: icon,
                tooltip: tooltip
            });
            return this.addIndicator(name, indicator, position);
        },

        /**
         * Adds an indicator
         * 
         * @this {DebugBar}
         * @param {String} name Internal name
         * @param {Indicator} indicator Indicator object
         * @return {Indicator}
         */
        addIndicator: function(name, indicator, position) {
            if (this.isControl(name)) {
                throw new Error(name + ' already exists');
            }

            if (position == 'left') {
                indicator.$el.insertBefore(this.$headerLeft.children().first());
            } else {
                indicator.$el.appendTo(this.$headerRight);
            }

            this.controls[name] = indicator;
            return indicator;
        },

        /**
         * Returns a control
         * 
         * @param {String} name
         * @return {Object}
         */
        getControl: function(name) {
            if (this.isControl(name)) {
                return this.controls[name];
            }
        },

        /**
         * Checks if there's a control under the specified name
         * 
         * @this {DebugBar}
         * @param {String} name
         * @return {Boolean}
         */
        isControl: function(name) {
            return typeof(this.controls[name]) != 'undefined';
        },

        /**
         * Checks if a tab with the specified name exists
         * 
         * @this {DebugBar}
         * @param {String} name
         * @return {Boolean}
         */
        isTab: function(name) {
            return this.isControl(name) && this.controls[name] instanceof Tab;
        },

        /**
         * Checks if an indicator with the specified name exists
         * 
         * @this {DebugBar}
         * @param {String} name
         * @return {Boolean}
         */
        isIndicator: function(name) {
            return this.isControl(name) && this.controls[name] instanceof Indicator;
        },

        /**
         * Removes all tabs and indicators from the debug bar and hides it
         * 
         * @this {DebugBar}
         */
        reset: function() {
            this.minimize();
            var self = this;
            $.each(this.controls, function(name, control) {
                if (self.isTab(name)) {
                    control.$tab.remove();
                }
                control.$el.remove();
            });
            this.controls = {};
        },

        /**
         * Open the debug bar and display the specified tab
         * 
         * @this {DebugBar}
         * @param {String} name If not specified, display the first tab
         */
        showTab: function(name) {
            if (!name) {
                if (this.activePanelName) {
                    name = this.activePanelName;
                } else {
                    name = this.firstTabName;
                }
            }

            if (!this.isTab(name)) {
                throw new Error("Unknown tab '" + name + "'");
            }

            this.$resizehdle.show();
            this.$body.show();
            this.recomputeBottomOffset();

            $(this.$header).find('> div > .' + csscls('active')).removeClass(csscls('active'));
            $(this.$body).find('> .' + csscls('active')).removeClass(csscls('active'));

            this.controls[name].$tab.addClass(csscls('active'));
            this.controls[name].$el.addClass(csscls('active'));
            this.activePanelName = name;

            this.$el.removeClass(csscls('minimized'));
            localStorage.setItem('phpdebugbar-visible', '1');
            localStorage.setItem('phpdebugbar-tab', name);
            this.resize();
        },

        /**
         * Hide panels and minimize the debug bar
         *
         * @this {DebugBar}
         */
        minimize: function() {
            this.$header.find('> div > .' + csscls('active')).removeClass(csscls('active'));
            this.$body.hide();
            this.$resizehdle.hide();
            this.recomputeBottomOffset();
            localStorage.setItem('phpdebugbar-visible', '0');
            this.$el.addClass(csscls('minimized'));
            this.resize();
        },

        /**
         * Checks if the panel is minimized
         * 
         * @return {Boolean}
         */
        isMinimized: function() {
            return this.$el.hasClass(csscls('minimized'));
        },
        
        /**
         * Close the debug bar
         *
         * @this {DebugBar}
         */
        close: function() {
            this.$resizehdle.hide();
            this.$header.hide();
            this.$body.hide();
            this.$restorebtn.show();
            localStorage.setItem('phpdebugbar-open', '0');
            this.$el.addClass(csscls('closed'));
            this.recomputeBottomOffset();
        },

        /**
         * Restore the debug bar
         *
         * @this {DebugBar}
         */
        restore: function() {
            this.$resizehdle.show();
            this.$header.show();
            this.$restorebtn.hide();
            localStorage.setItem('phpdebugbar-open', '1');
            var tab = localStorage.getItem('phpdebugbar-tab');
            if (this.isTab(tab)) {
                this.showTab(tab);
            }
            this.$el.removeClass(csscls('closed'));
            this.resize();
        },

        /**
         * Recomputes the padding-bottom css property of the body so
         * that the debug bar never hides any content
         */
        recomputeBottomOffset: function() {
            if (this.options.bodyPaddingBottom) {
                $('body').css('padding-bottom', this.$el.height());
            }
        },

        /**
         * Sets the data map used by dataChangeHandler to populate
         * indicators and widgets
         *
         * A data map is an object where properties are control names.
         * The value of each property should be an array where the first
         * item is the name of a property from the data object (nested properties
         * can be specified) and the second item the default value.
         *
         * Example:
         *     {"memory": ["memory.peak_usage_str", "0B"]}
         * 
         * @this {DebugBar}
         * @param {Object} map
         */
        setDataMap: function(map) {
            this.dataMap = map;
        },

        /**
         * Same as setDataMap() but appends to the existing map
         * rather than replacing it
         *
         * @this {DebugBar}
         * @param {Object} map
         */
        addDataMap: function(map) {
            $.extend(this.dataMap, map);
        },

        /**
         * Resets datasets and add one set of data
         *
         * For this method to be usefull, you need to specify
         * a dataMap using setDataMap()
         * 
         * @this {DebugBar}
         * @param {Object} data
         * @return {String} Dataset's id
         */
        setData: function(data) {
            this.datasets = {};
            return this.addDataSet(data);
        },

        /**
         * Adds a dataset
         *
         * If more than one dataset are added, the dataset selector
         * will be displayed.
         * 
         * For this method to be usefull, you need to specify
         * a dataMap using setDataMap()
         * 
         * @this {DebugBar}
         * @param {Object} data
         * @param {String} id The name of this set, optional
         * @param {String} suffix
         * @return {String} Dataset's id
         */
        addDataSet: function(data, id, suffix) {
            var label = this.datesetTitleFormater.format(id, data, suffix);
            id = id || (getObjectSize(this.datasets) + 1);
            this.datasets[id] = data;

            this.$datasets.append($('<option value="' + id + '">' + label + '</option>'));
            if (this.$datasets.children().length > 1) {
                this.$datasets.show();
            }

            this.showDataSet(id);
            return id;
        },

        /**
         * Loads a dataset using the open handler
         * 
         * @param {String} id
         */
        loadDataSet: function(id, suffix, callback) {
            if (!this.openHandler) {
                throw new Error('loadDataSet() needs an open handler');
            }
            var self = this;
            this.openHandler.load(id, function(data) {
                self.addDataSet(data, id, suffix);
                callback && callback(data);
            });
        },

        /**
         * Returns the data from a dataset
         * 
         * @this {DebugBar}
         * @param {String} id
         * @return {Object}
         */
        getDataSet: function(id) {
            return this.datasets[id];
        },

        /**
         * Switch the currently displayed dataset
         * 
         * @this {DebugBar}
         * @param {String} id
         */
        showDataSet: function(id) {
            this.dataChangeHandler(this.datasets[id]);
            this.$datasets.val(id);
        },

        /**
         * Called when the current dataset is modified.
         * 
         * @this {DebugBar}
         * @param {Object} data
         */
        dataChangeHandler: function(data) {
            var self = this;
            $.each(this.dataMap, function(key, def) {
                var d = getDictValue(data, def[0], def[1]);
                if (key.indexOf(':') != -1) {
                    key = key.split(':');
                    self.getControl(key[0]).set(key[1], d);
                } else {
                    self.getControl(key).set('data', d);
                }
            });
        },

        /**
         * Sets the handler to open past dataset
         * 
         * @this {DebugBar}
         * @param {object} handler
         */
        setOpenHandler: function(handler) {
            this.openHandler = handler;
            if (handler !== null) {
                this.$openbtn.show();
            } else {
                this.$openbtn.hide();
            }
        },

        /**
         * Returns the handler to open past dataset
         * 
         * @this {DebugBar}
         * @return {object}
         */
        getOpenHandler: function() {
            return this.openHandler;
        }

    });

    DebugBar.Tab = Tab;
    DebugBar.Indicator = Indicator;

    // ------------------------------------------------------------------
    
    /**
     * AjaxHandler
     *
     * Extract data from headers of an XMLHttpRequest and adds a new dataset
     */
    var AjaxHandler = PhpDebugBar.AjaxHandler = function(debugbar, headerName) {
        this.debugbar = debugbar;
        this.headerName = headerName || 'phpdebugbar';
    };

    $.extend(AjaxHandler.prototype, {

        /**
         * Handles an XMLHttpRequest
         * 
         * @this {AjaxHandler}
         * @param {XMLHttpRequest} xhr
         * @return {Bool}
         */
        handle: function(xhr) {
            if (!this.loadFromId(xhr)) {
                return this.loadFromData(xhr);
            }
            return true;
        },

        /**
         * Checks if the HEADER-id exists and loads the dataset using the open handler
         * 
         * @param {XMLHttpRequest} xhr
         * @return {Bool}
         */
        loadFromId: function(xhr) {
            var id = this.extractIdFromHeaders(xhr);
            if (id && this.debugbar.openHandler) {
                this.debugbar.loadDataSet(id, "(ajax)");
                return true;
            }
            return false;
        },

        /**
         * Extracts the id from the HEADER-id
         * 
         * @param {XMLHttpRequest} xhr
         * @return {String}
         */
        extractIdFromHeaders: function(xhr) {
            return xhr.getResponseHeader(this.headerName + '-id');
        },

        /**
         * Checks if the HEADER exists and loads the dataset
         * 
         * @param {XMLHttpRequest} xhr
         * @return {Bool}
         */
        loadFromData: function(xhr) {
            var raw = this.extractDataFromHeaders(xhr);
            if (!raw) {
                return false;
            }

            var data = this.parseHeaders(raw);
            if (data.error) {
                throw new Error('Error loading debugbar data: ' + data.error);
            } else if(data.data) {
                this.debugbar.addDataSet(data.data, data.id, "(ajax)");
            }
            return true;
        },

        /**
         * Extract the data as a string from headers of an XMLHttpRequest
         * 
         * @this {AjaxHandler}
         * @param {XMLHttpRequest} xhr
         * @return {string}
         */
        extractDataFromHeaders: function(xhr) {
            var data = xhr.getResponseHeader(this.headerName);
            if (!data) {
                return;
            }
            for (var i = 1;; i++) {
                var header = xhr.getResponseHeader(this.headerName + '-' + i);
                if (!header) {
                    break;
                }
                data += header;
            }
            return decodeURIComponent(data);
        },

        /**
         * Parses the string data into an object
         * 
         * @this {AjaxHandler}
         * @param {string} data
         * @return {string}
         */
        parseHeaders: function(data) {
            return JSON.parse(data);
        },

        /**
         * Attaches an event listener to jQuery.ajaxComplete()
         * 
         * @this {AjaxHandler}
         * @param {jQuery} jq Optional
         */
        bindToJquery: function(jq) {
            var self = this;
            jq(document).ajaxComplete(function(e, xhr, settings) {
                if (!settings.ignoreDebugBarAjaxHandler) {
                    self.handle(xhr);
                }
            });
        }

    });

})(PhpDebugBar.$);
