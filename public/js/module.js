(function(Icinga) {

    var Vspheredb = function (module) {
        this.module = module;

        this.matchingLinks = null;

        this.initialize();

        this.module.icinga.logger.debug('VsphereDB module loaded');
    };

    Vspheredb.prototype = {
        initialize: function () {
            this.module.on('mouseover', 'thead tr', this.checkForHeaderHref);
            this.module.on('mouseover', '.content [href]', this.highlightMatchingLinks);
            this.module.on('mouseout', '.content [href]', this.removeMatchingLinksHighlight);
            this.module.on('click', '.tree li a', this.activateTreeLink);
            this.module.on('keydown', '', this.keyDown);
            this.module.on('keyup', '', this.keyUp);
            this.module.on('keyup', 'form.quicksearch input.search', this.keyUpInQuickSearch);
            $(document).on('keydown', this.bodyKeyDown);
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
                // Link w/o href, ignore
                // console.log('undef', $link);
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

        activateTreeLink: function (ev) {
            // href will be added because of sort icons
            $(ev.currentTarget).closest('.tree').find('a').removeClass('active');
            $(ev.currentTarget).addClass('active');
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
            // 34, 33 -> ignore PGUP/DOWN
            // 38, 40 -> ignore arrow UP/DOWN
            if ((ev.keyCode > 31 || ev.keyCode === 8)
                && ev.keyCode !== 38 && ev.keyCode !== 40
                && ev.keyCode !== 33 && ev.keyCode !== 34
                && ! (ev.ctrlKey || ev.altKey)
            ) {
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
        }
    };

    Icinga.availableModules.vspheredb = Vspheredb;

}(Icinga));
