var avaroGenericModal;

$(function ( ) {

    /**
     *
     */
    $(document).on('click', '[data-modal]', function(event) {

        if ($(event.target).prop('tagName') == 'A' || $(event.target).parents('a').length) {

            if (typeof $(event.target).data('modal') == 'undefined' && typeof $(event.target).parents('a').data('modal') == 'undefined') {
                return;
            }
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (typeof avaroGenericModal == "undefined") {
            avaroGenericModal = new bootstrap.Modal(document.getElementById('genericModal'));
        }

        if ($(this).data('title')) {
            $('#genericModal .modal-title').html($(this).data('title'));
        }
        else if ($(this).data('bs-title')) {
            $('#genericModal .modal-title').html($(this).data('bs-title'));
        }

        if ($(this).attr('data-bs-original-title')) {
            $('#genericModal .modal-title').html($(this).attr('data-bs-original-title'));
        }

        $('#genericModal .modal-x-content').html('<div class="modal-body"><p>wird geladen ...</p></div>');

        $('#genericModal .modal-dialog').removeClass('modal-sm modal-lg modal-xl');

        if ($(this).attr('data-size')) {
            $('#genericModal .modal-dialog').addClass('modal-' + $(this).attr('data-size'));
        }

        avaroGenericModal.show();

        var url = $(this).attr('href') ? $(this).attr('href') : $(this).data('modal');

        if (url.length == 0) {
            url = $(this).find('a').attr('href');
        }

        $.ajax({
            url: url,
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
    });
});
