var _autoLoginCheckTimer = null;

$(function() {

    /**
     * Toggle menu
     */
    $(document).on('click', 'a.toggle-menu', function(event) {
        event.preventDefault();

        $('body').toggleClass('menu-visible');
    });

    /**
     * Toggle offcanvas menu for mobile
     */
    $(document).on('click', 'a.toggle-menu', function(event) {
        event.preventDefault();

        $('body').toggleClass('offcanvas-visible');
    });

    /**
     * Init nav panel
     */
    $(document).on('click', '[data-nav][data-toggle]', function(event) {

        event.preventDefault();

        $('[data-nav][data-panel]').hide();
        $('[data-nav="' + $(this).data('nav') + '"][data-panel="' + $(this).data('toggle') + '"]').show();

        $('[data-nav="' + $(this).data('nav') + '"][data-toggle]').removeClass('active');
        $(this).addClass('active');

    });

    $('[data-nav][data-panel]').hide();

    $('.active[data-nav][data-toggle]').trigger('click');

    /**
     * Parse dynamic ellipses
     */
    $(window).resize(function() {

        $('.top-spacer').css('height', $('#banner').outerHeight() + 'px');

        $('.ellipsis-dynamic').removeClass('ellipsis');
        $('.ellipsis-dynamic').css('width', 'auto');

        $('.ellipsis-dynamic').each(function() {

            let width = parseInt($(this).width());

            $(this).css('width', width + 'px');
            $(this).addClass('ellipsis');
        });


        $('[data-equalheight]').removeAttr('data-heightset');

        $('[data-equalheight]').each(function ( ) {

            let equalId = $(this).attr('data-equalheight');

            if ($(window).width() < 420) {
                $('[data-equalheight="' + equalId + '"]').css('height', 'auto');
                return;
            }

            if ($(this).attr('data-heightset')) {
                return;
            }

            // Get max height
            let height = 0;

            $('[data-equalheight="' + equalId + '"]').each(function ( ) {

                if ($(this).outerHeight() > height) {
                    height = $(this).outerHeight();
                }
            });

            $('[data-equalheight="' + equalId + '"]').css('height', height + 'px');
            $('[data-equalheight="' + equalId + '"]').attr('data-heightset', '1');
        });
    });

    $(window).trigger('resize');

    /**
     * Auto grow textareas
     */
    $(document).on('keyup', 'textarea.autogrow', function(){
        this.style.height = "5px";
        this.style.height = (this.scrollHeight + 20)+"px";
    });

    $('textarea.autogrow').trigger('keyup');

    /**
     * Init sortable lists
     */
    $('ul[data-sort]').sortable({
        axis: "y",
        handle: ".handle",
        stop: function(event, ui) {

            let ids = [];

            $(this).find('li').each(function() {
                ids.push($(this).attr("data-id"));
            });

            $.ajax({
                url: $(this).data('sort'),
                dataType: "json",
                data: {
                    ids: ids,
                },
                success: function(response) {

                    console.log(response);
                },
                error: function(xxx) {
                    console.log("ERROR");
                }
            });
        }
    });

    _InitUserInterface();

    /**
     *
     */
    _autoLoginCheckTimer = window.setInterval(function() {

        $.ajax({
            url: urls.checkLogin,
            dataType: "json",
            success: function(response) {

                if (response.isLoggedIn) {
                    return;
                }

                window.clearTimeout(_autoLoginCheckTimer);

                console.log("OPEN POPUP");

                if (typeof avaroGenericModal == "undefined") {
                    avaroGenericModal = new bootstrap.Modal(document.getElementById('genericModal'));
                }

                $('#genericModal .modal-title').html('Sitzung beendet');

                avaroGenericModal.show();

                $.ajax({
                    url: urls.modalLogin,
                    success: function(html) {
                        $('#genericModal .modal-x-content').html(html);

                        window.setTimeout(function ( ) {
                            $('#genericModal .modal-x-content input').first().focus();
                        }, 500);
                    },
                    error: function(xhr) {
                        $('#genericModal .modal-x-content').html('<div class="modal-body">' + xhr.responseText + '</div>');
                    }
                });
            }
        });

    }, 15000);
});

function _InitUserInterface() {

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
}