(function(Icinga) {

    var Vspheredb = function (module) {
        this.module = module;

        this.matchingLinks = null;

        this.initialize();

        this.module.icinga.logger.debug('VsphereDB module loaded');
    };

    Vspheredb.prototype = {
        initialize: function () {
            this.module.on('sparklineRegionChange', '.overspark', this.change);
            this.module.on('mouseleave', '.overspark', this.leave);
            this.module.on('render', this.rendered);
            this.module.on('mouseover', 'thead tr', this.checkForHeaderHref);
            this.module.on('mouseover', '.inline-perf-container', this.inlinePerfSmall);
            this.module.on('mouseout', '.inline-perf-container', this.inlinePerfSmallOut);
            this.module.on('mouseover', '.content [href]', this.highlightMatchingLinks);
            this.module.on('mouseout', '.content [href]', this.removeMatchingLinksHighlight);
            this.module.on('keydown', '', this.keyDown);
            this.module.on('keyup', '', this.keyUp);
            this.module.on('keyup', 'form.quicksearch input.search', this.keyUpInQuickSearch);
            $(document).keydown(this.bodyKeyDown);
        },

        inlinePerfSmall: function (ev) {
            var $el = $(ev.currentTarget);
            if (! $el.hasClass('hovered')) {
                $el.addClass('hovered');
            }
            return true;
            // console.log($(ev.currentTarget));
        },

        inlinePerfSmallOut: function (ev) {
            $(ev.currentTarget).removeClass('hovered');
        },

        removeMatchingLinksHighlight: function (ev) {
            if (this.matchingLinks !== null) {
                this.matchingLinks.each(function (idx, el) {
                    $(el).removeClass('same-link-hovered');
                });
            }

            this.matchingLinks = null;
        },

        highlightMatchingLinks: function (ev) {
            // console.log('in', ev);
            var $link = $(ev.currentTarget);
            var href = $link.attr('href');
            if (typeof href === 'undefined') {
                console.log('undef', $link);
                return;
            }
            // console.log(href);
            this.removeMatchingLinksHighlight(ev);
            var match = href.match(/uuid=([a-f0-9]{32,40})/);
            if (match) {
                this.matchingLinks = $('div.container.module-vspheredb .content [href*="' + match[1] + '"]').not($link);
                this.matchingLinks.addClass('same-link-hovered');
            }
        },

        checkForHeaderHref: function (ev) {
            // href will be added because of sort icons
            $(ev.currentTarget).removeAttr('href');
        },

        bodyKeyDown: function (ev) {
            if (document.activeElement.id !== 'body') {
                return;
            }

            if (ev.ctrlKey || ev.altKey) {
                return;
            }

            if (ev.keyCode < 31 && ev.keyCode !== 8 && ev.keyCode !== 27) {
                return;
            }

            var $input = $('form.quicksearch input.search').last();
            var $container = $input.closest('.container.module-vspheredb');
            if ($container.length) {
                $input.focus();
            }
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

        getInfoArea: function (ev) {
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
            $('.overspark').bind('sparklineRegionChange', function (ev) {
                console.log(ev);
                return;
                var sparkline = ev.sparklines[0],
                    region = sparkline.getCurrentRegionFields(),
                    value = region.y;
                $('.mouseoverregion').text("x="+region.x+" y="+region.y);
            }).bind('mouseleave', function () {
                $('.mouseoverregion').text('');
            });
        }
    };

    Icinga.availableModules.vspheredb = Vspheredb;

}(Icinga));
