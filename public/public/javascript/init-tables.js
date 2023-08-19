$(function(){

    $('table[data-sort]').tableDnD({
        dragHandle: '.drag-handle',
       // serializeParamName: 'data-id',
        onDrop: function (table, row) {

            $.ajax({
                url: $(table).data('sort'),
                data: $(table).tableDnDSerialize(),
                dataType: "json",
                success: function() {

                    toastr.success('Die Sortierung wurde gespeichert.');
                }
            });
        },
    });

});
