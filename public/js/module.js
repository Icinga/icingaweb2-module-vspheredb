(function(Icinga) {

    var Vspheredb = function(module) {
        this.module = module;

        this.initialize();

        this.module.icinga.logger.debug('VsphereDB module loaded');
    };

    Vspheredb.prototype = {
        initialize: function()
        {
            this.module.on('sparklineRegionChange', '.overspark', this.change);
            this.module.on('mouseleave', '.overspark', this.leave);
            this.module.on('render', this.rendered);
        },

        leave: function (ev) {
            this.getInfoArea(ev).text('');
        },

        rendered: function (ev) {
            // $('.sparkline')
        },

        change: function (ev) {
            var sparkline = ev.sparklines[0],
                region = sparkline.getCurrentRegionFields(),
                value = region.y;

            var data = $(ev.target).data();
            var d = new Date(data.first + region.x * data.interval * 1000);
            this.getInfoArea(ev).text('[' + d.toLocaleTimeString() + '] ' + region.y);
        },

        getInfoArea: function(ev)
        {
            var $el = $(ev.currentTarget);
            $parent = $el.closest('td');

            $info = $parent.find('.sparkinfo');
            if ($info.length === 0) {
                $info = $('<span class="sparkinfo"></span>');
                $parent.append($info);
            }

            return $info;
        },

        showInfo: function () {
            $('.overspark').bind('sparklineRegionChange', function(ev) {
                console.log(ev);
                return;
                var sparkline = ev.sparklines[0],
                    region = sparkline.getCurrentRegionFields(),
                    value = region.y;
                $('.mouseoverregion').text("x="+region.x+" y="+region.y);
            }).bind('mouseleave', function() {
                $('.mouseoverregion').text('');
            });
        }
    };

    Icinga.availableModules.vspheredb = Vspheredb;

}(Icinga));
