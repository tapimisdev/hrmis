      $(document).ready(function () {
            const items = $('.side-items');
            const btn = $('#switchMenuBtn');
            const icon = btn.find('i');

            //  Restore saved mode from localStorage
            let savedMode = localStorage.getItem('menuMode') || 'collapse';

            if (savedMode === 'dropdown') {
                switchToDropdown();
                icon.addClass('rotate'); // Keep icon rotated
            } else {
                switchToCollapse();
                icon.removeClass('rotate');
            }

            //  On click — toggle and save
            btn.on('click', function () {
                icon.toggleClass('rotate');
                $('.dropdown-menu').removeClass('show');
                $('.collapse.show').collapse('hide');

                if (items.first().hasClass('collapse-mode')) {
                    switchToDropdown();
                    localStorage.setItem('menuMode', 'dropdown');
                } else {
                    switchToCollapse();
                    localStorage.setItem('menuMode', 'collapse');
                }
            });

            //  Define collapse mode
            function switchToCollapse() {
                items.removeClass('dropdown-mode').addClass('collapse-mode');
                items.each(function (index) {
                    const item = $(this);
                    const link = item.find('> a');
                    const submenu = item.find('> ul');
                    const collapseId = 'collapseExample' + index;

                    item.removeClass('dropdown dropend').addClass('dropend');

                    link.removeAttr('class data-bs-toggle aria-expanded')
                        .attr({
                            'data-bs-toggle': ' ',
                            'href': '#' + collapseId,
                            'role': 'button',
                            'aria-expanded': 'false',
                            'aria-controls': collapseId
                        });

                    submenu.removeClass('dropdown-menu show').addClass('collapse').attr('id', collapseId);

                    submenu.find('a').each(function () {
                        $(this).removeClass('dropdown-item').attr('href', '');
                    });
                });
            }

            //  Define dropdown mode
            function switchToDropdown() {
                items.removeClass('collapse-mode').addClass('dropdown-mode');
                items.each(function () {
                    const item = $(this);
                    const link = item.find('> a');
                    const submenu = item.find('> ul');

                    item.removeClass('dropend').addClass('dropdown dropend');

                    link.removeAttr('data-bs-toggle href aria-controls')
                        .attr({
                            'class': 'dropdown-toggle',
                            'href': '#',
                            'role': 'button',
                            'data-bs-toggle': 'dropdown',
                            'aria-expanded': 'false'
                        });

                    submenu.removeClass('collapse').addClass('dropdown-menu').removeAttr('id');

                    submenu.find('a').each(function () {
                        $(this).addClass('dropdown-item').attr('href', '#');
                    });
                });
            }
    });