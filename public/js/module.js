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
            this.module.on('keydown', '', this.keyDown);
            this.module.on('keyup', '', this.keyUp);
            this.module.on('keyup', 'form.quicksearch input.search', this.keyUpInQuickSearch);
        },

        keyDown: function (ev) {
            if ((ev.keyCode > 31 || ev.keyCode === 8)  && ! (ev.ctrlKey || ev.altKey)) {
                $(ev.currentTarget).find('input.search').first().focus();
            }
        },

        keyUp: function (ev) {
            if (ev.keyCode === 27 && ! (ev.ctrlKey || ev.altKey)) {
                this.clearQuickSearch($(ev.currentTarget).find('input.search'));
            }
        },

        keyUpInQuickSearch: function (ev) {
            if (ev.keyCode === 27 && ! (ev.ctrlKey || ev.altKey)) {
                ev.stopPropagation();
                this.clearQuickSearch($(ev.currentTarget));
            }
        },

        clearQuickSearch: function ($input) {
            if ($input.length > 0) {
                $input = $input.first();
            } else {
                return;
            }

            if ($input.val().length === 0) {
                return;
            }

            var attrValue = $input.attr('value');
            $input.val('');
            if (typeof attrValue !== 'undefined' && attrValue !== '') {
                $input.closest('form').submit();
            }
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
