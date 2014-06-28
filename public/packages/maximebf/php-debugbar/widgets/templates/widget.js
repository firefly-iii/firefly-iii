(function($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying templates data
     *
     * Options:
     *  - data
     */
    var TemplatesWidget = PhpDebugBar.Widgets.TemplatesWidget = PhpDebugBar.Widget.extend({

        className: csscls('templates'),

        render: function() {
            this.$status = $('<div />').addClass(csscls('status')).appendTo(this.$el);

            this.$list = new  PhpDebugBar.Widgets.ListWidget({ itemRenderer: function(li, tpl) {
                $('<span />').addClass(csscls('name')).text(tpl.name).appendTo(li);
                if (tpl.render_time_str) {
                    $('<span title="Render time" />').addClass(csscls('render_time')).text(tpl.render_time_str).appendTo(li);
                }
            }});
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function(data) {
                this.$list.set('data', data.templates);
                var sentence = data.sentence || "templates were rendered";
                this.$status.empty().append($('<span />').text(data.templates.length + " " + sentence));
                if (data.accumulated_render_time_str) {
                    this.$status.append($('<span title="Accumulated render time" />').addClass(csscls('render_time')).text(data.accumulated_render_time_str));
                }
            });
        }

    });

})(PhpDebugBar.$);