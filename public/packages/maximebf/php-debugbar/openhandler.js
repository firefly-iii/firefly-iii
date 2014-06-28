if (typeof(PhpDebugBar) == 'undefined') {
    // namespace
    var PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}

(function($) {

    var csscls = function(cls) {
        return PhpDebugBar.utils.csscls(cls, 'phpdebugbar-openhandler-');
    };

    PhpDebugBar.OpenHandler = PhpDebugBar.Widget.extend({

        className: 'phpdebugbar-openhandler',

        defaults: {
            items_per_page: 20
        },

        render: function() {
            var self = this;
            
            this.$el.appendTo('body').hide();
            this.$closebtn = $('<a href="javascript:"><i class="fa fa-times"></i></a>');
            this.$table = $('<tbody />');
            $('<div>PHP DebugBar | Open</div>').addClass(csscls('header')).append(this.$closebtn).appendTo(this.$el);
            $('<table><thead><tr><th>Load</th><th>Method</th><th>URL</th><th>Date</th><th>IP</th></tr></thead></table>').append(this.$table).appendTo(this.$el);
            this.$actions = $('<div />').addClass(csscls('actions')).appendTo(this.$el);

            this.$closebtn.on('click', function() {
                self.hide();
            });

            this.$loadmorebtn = $('<a href="javascript:">Load more</a>')
                .appendTo(this.$actions)
                .on('click', function() {
                    self.find(self.last_find_request, self.last_find_request.offset + self.get('items_per_page'), self.handleFind.bind(self));
                });

            this.$showonlycurrentbtn = $('<a href="javascript:">Show only current URL</a>')
                .appendTo(this.$actions)
                .on('click', function() {
                    self.$table.empty();
                    self.find({uri: window.location.pathname}, 0, self.handleFind.bind(self));
                });

            this.$showallbtn = $('<a href="javascript:">Show all</a>')
                .appendTo(this.$actions)
                .on('click', function() {
                    self.refresh();
                });

            this.$clearbtn = $('<a href="javascript:">Delete all</a>')
                .appendTo(this.$actions)
                .on('click', function() {
                    self.clear(function() {
                        self.hide();
                    });
                });
                
            this.addSearch();

            this.$overlay = $('<div />').addClass(csscls('overlay')).hide().appendTo('body');
            this.$overlay.on('click', function() {
                self.hide();
            });
        },

        refresh: function() {
            this.$table.empty();
            this.$loadmorebtn.show();
            this.find({}, 0, this.handleFind.bind(this));
        },
        
        addSearch: function(){
            var self = this;
            var searchBtn = $('<button />')
                .text('Search')
                .on('click', function(e) {
                    self.$table.empty();
                    var search = {};
                    var a = $(this).parent().serializeArray();
                    $.each(a, function() {
                        if(this.value){
                            search[this.name] = this.value;
                        }
                    });

                    self.find(search, 0, self.handleFind.bind(self));
                    e.preventDefault();
                });

            $('<form />')
                .append('<br/><b>Filter results</b><br/>')
                .append('Method: <select name="method"><option></option><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select><br/>')
                .append('Uri: <input type="text" name="uri"><br/>')
                .append('IP: <input type="text" name="ip"><br/>')
                .append(searchBtn)
                .appendTo(this.$actions);
        },

        handleFind: function(data) {
            var self = this;
            $.each(data, function(i, meta) {
               var a = $('<a href="javascript:" />')
                    .text('Load dataset')
                    .on('click', function(e) {
                        self.hide();
                        self.load(meta['id'], function(data) {
                            self.callback(meta['id'], data);
                        });
                        e.preventDefault();
                    });
                    
                var method = $('<a href="javascript:" />')
                    .text(meta['method'])
                    .on('click', function(e) {
                        self.$table.empty();
                        self.find({method: meta['method']}, 0, self.handleFind.bind(self));
                        e.preventDefault();
                    });

                var uri = $('<a href="javascript:" />')
                    .text(meta['uri'])
                    .on('click', function(e) {
                        self.$table.empty();
                        self.find({uri: meta['uri']}, 0, self.handleFind.bind(self));
                        e.preventDefault();
                    });

                var ip = $('<a href="javascript:" />')
                    .text(meta['ip'])
                    .on('click', function(e) {
                        self.$table.empty();
                        self.find({ip: meta['ip']}, 0, self.handleFind.bind(self));
                        e.preventDefault();
                    });
                    
                $('<tr />')
                    .append($('<td />').append(a))
                    .append($('<td />').append(method))
                    .append($('<td />').append(uri))
                    .append('<td>' + meta['datetime'] + '</td>')
                    .append($('<td />').append(ip))
                    .appendTo(self.$table);
            });
            if (data.length < this.get('items_per_page')) {
                this.$loadmorebtn.hide();
            }
        },

        show: function(callback) {
            this.callback = callback;
            this.$el.show();
            this.$overlay.show();
            this.refresh();
        },

        hide: function() {
            this.$el.hide();
            this.$overlay.hide();
        },

        find: function(filters, offset, callback) {
            var data = $.extend({}, filters, {max: this.get('items_per_page'), offset: offset || 0});
            this.last_find_request = data;
            this.ajax(data, callback);
        },

        load: function(id, callback) {
            this.ajax({op: "get", id: id}, callback);
        },

        clear: function(callback) {
            this.ajax({op: "clear"}, callback);
        },

        ajax: function(data, callback) {
            $.ajax({
                dataType: 'json',
                url: this.get('url'),
                data: data,
                success: callback,
                ignoreDebugBarAjaxHandler: true
            });
        }

    });

})(PhpDebugBar.$);
