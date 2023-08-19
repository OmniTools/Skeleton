$(function ( ) {

    /**
     *
     */
    $(document).on('submit', 'form.ajax', function(event) {

        event.preventDefault();
        event.stopImmediatePropagation();

        if ($(this).data('confirm') && !confirm($(this).data('confirm'))) {
            return;
        }

        var form = $(this);

        form.removeClass('has-warning');

        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method'),
            data: $(this).serialize(),
            dataType: "json",
            success: function ( response ) {

                form.find('.form-autogenerated-button-capture').remove();

                form.trigger('on-success', [ response ]);

                if (typeof response.redirect != 'undefined') {
                    window.location.href = response.redirect;
                    return;
                }

                if (typeof response.clearSelf != 'undefined') {
                    form.find('input').val('');
                }

                if (typeof response.success != 'undefined') {
                    toastr.success(response.success);
                }

                if (typeof response.remove != 'undefined') {
                    $(response.remove).remove();
                }

                if (typeof response.setValues != 'undefined') {

                    $(response.setValues).each(function(index, data) {
                        $('[name="' + data.name + '"]').val(data.value);
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

                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                }

                if (typeof response.append != 'undefined') {
                    $(response.append.selector).append(response.append.html);


                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                }

                if (typeof response.triggerClick != 'undefined') {
                    $(response.triggerClick).trigger('click');
                }

                if (typeof response.callback != 'undefined') {
                    window[response.callback]();
                }

                if (typeof response.modalDismiss != 'undefined') {
                    avaroGenericModal.hide();
                }
            },
            error: function(xhr) {

                form.find('.form-autogenerated-button-capture').remove();

                if (xhr.responseText.charAt(0) == '{') {
                    var response = JSON.parse(xhr.responseText);
                }
                else {
                    var response = {
                        'error': xhr.responseText
                    }
                }
                toastr.error(response.error);
            }
        });
    });

    /**
     * Work around jquery not serializing button values
     */
    $(document).on('click', 'form.ajax button', function(event) {

        var button = $(event.target);
        var form = button.parents('form');

        // Remove pre existing button captures
        form.find('.form-autogenerated-button-capture').remove();

        // CLicked button has no "name" so we de nothing here
        if (typeof button.attr('name') == "undefined") {
            return;
        }

        // Append hidden input to mimik input behaviour for buttons
        $(form).append('<input class="form-autogenerated-button-capture" type="hidden" name="' + button.attr('name') + '" value="' + button.val() + '" />');
    });
});
