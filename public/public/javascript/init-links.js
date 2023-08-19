$(function ( ) {

    /**
     *
     */
    $(document).on('click', 'a.history-back', function(event) {
        event.preventDefault();

        history.back();
    });

    /**
     *
     */
    $(document).on('click', 'a.ajax', function(event) {

        event.preventDefault();
        event.stopImmediatePropagation();

        if ($(this).data('confirm') && !confirm($(this).data('confirm'))) {
            return;
        }

        var element = $(this);

        $.ajax({
            url: $(this).attr('href'),
            dataType: "json",
            success: function(response) {

                element.trigger('on-success', [ response ]);

                if (typeof response.callback != 'undefined') {
                    window[response.callback]();
                }

                if (typeof response.redirect != 'undefined') {
                    window.location.href = response.redirect;
                    return;
                }

                if (typeof response.success != 'undefined') {
                    toastr.success(response.success);
                }

                if (typeof response.modalDismiss != 'undefined') {
                    avaroGenericModal.hide();
                }

                if (typeof response.fadeOut != 'undefined') {

                    $(response.fadeOut).fadeOut(500, function ( ) {

                        if (typeof response.replace != 'undefined') {
                            $(response.replace.selector).html(response.replace.html);
                        }

                    });
                }

                /**
                 * Replace values of input fields
                 *
                 * {
                 *     "inputValues": [
                 *         {
                 *             "name": "code",
                 *             "value": "NE4V4C"
                 *         }
                 *     ]
                 * }
                 */
                if (typeof response.inputValues != 'undefined') {
                    $.each(response.inputValues, function(index, inputValue) {
                        $('input[name="' + inputValue.name + '"]').val(inputValue.value);
                    });
                }

                if (typeof response.setClasses != 'undefined') {

                    if (typeof response.setClasses.remove != 'undefined') {
                        $(response.setClasses.selector).removeClass(response.setClasses.remove);
                    }

                    if (typeof response.setClasses.add != 'undefined') {
                        $(response.setClasses.selector).addClass(response.setClasses.add);
                    }
                }

                if (typeof response.replace != 'undefined') {

                    $(response.replace.selector).html(response.replace.html);

                    $('.tooltip.bs-tooltip-auto').remove();

                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                }
            },
            error: function(xhr) {

                if (xhr.responseText.charAt(0) == '{') {
                    let response = $.parseJSON(xhr.responseText);
                    var errorMessage = response.error;
                }
                else {
                    var errorMessage = xhr.responseText;
                }

                toastr.error(errorMessage);
            }
        });
    });

    /**
     *
     */
    $(document).on('mouseenter', 'tr[data-hover], tr[data-href], tr[data-modal]', function(event) {
        $(this).addClass('hovered');
    });

    /**
     *
     */
    $(document).on('mouseleave', 'tr[data-hover], tr[data-href], tr[data-modal]', function(event) {
        $(this).removeClass('hovered');
    });

    /**
     *
     */
    $(document).on('click', '[data-href]', function(event) {
        window.location.href = $(this).data('href');
    });

    var pathname = window.location.href;

    /**
     *
     */
    $('a').each(function ( ) {

        var href = $(this).attr('href');

        if (typeof href == 'undefined' || href.length == 0) {
            return;
        }

        if (href.substr(0,1) == "#") {

            if ($(this).attr('data-norewrite')) {
                return;
            }

            $(this).attr('href', pathname.split('#')[0] + href);

            return;
        }

        if (!href.match(/^http:/) && !href.match(/^https:/)) {
            return;
        }

        var match = '^' + settings.serverpathProtocol;

        var regex = new RegExp(match);

        if (href.match(regex)) {
            return;
        }

        var match = '^' + settings.serverpathProtocol.replace(/\/+$/, '') + ':443';

        var regex = new RegExp(match);

        if (href.match(regex)) {
            return;
        }

        // Mark link
        $(this).attr('target', '_blank');
    });
});
