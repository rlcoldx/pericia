(function($) {
    "use strict"

    var table = $('#datatable').DataTable({
        lengthMenu: [[25, 50, -1], [25, 50, "Todos"]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
        },
        createdRow: function ( row, data, index ) {
           $(row).addClass('selected');
        } 
    });
      
    table.on('click', 'tbody tr', function() {
        var $row = table.row(this).nodes().to$();
        var hasClass = $row.hasClass('selected');
        if (hasClass) {
            $row.removeClass('selected');
        } else {
            $row.addClass('selected');
        }
    })
    
    table.rows().every(function() {
        this.nodes().to$().removeClass('selected');
    });

})(jQuery);

(function($) {
    "use strict";

    var table = $('#datatable_nosort').DataTable({ 
        lengthMenu: [[25, 50, -1], [25, 50, "Todos"]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
        },
        ordering: false, // Desabilita a ordenação
        createdRow: function ( row, data, index ) {
           $(row).addClass('selected');
        } 
    });
      
    table.on('click', 'tbody tr', function() {
        var $row = table.row(this).nodes().to$();
        var hasClass = $row.hasClass('selected');
        if (hasClass) {
            $row.removeClass('selected');
        } else {
            $row.addClass('selected');
        }
    });
    
    table.rows().every(function() {
        this.nodes().to$().removeClass('selected');
    });

})(jQuery);


(function ($) {
    "use strict";

    var table = $('#datatable_nosort_nopagination').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
        },
        paging: false,
        ordering: false, // Desabilita a ordenação
        createdRow: function (row, data, index) {
            $(row).addClass('selected');
        },
        dom: 'frtip',
        initComplete: function() {
            var saveBtnLeft = $('<button id="save-order-btn" class="btn btn-success btn-sm me-2">Salvar Ordem</button>');
            var filter = $('#datatable_nosort_nopagination_filter');
            filter.prepend(saveBtnLeft);
        }
    });

    table.on('click', 'tbody tr', function () {
        var $row = table.row(this).nodes().to$();
        var hasClass = $row.hasClass('selected');
        if (hasClass) {
            $row.removeClass('selected');
        } else {
            $row.addClass('selected');
        }
    });

    table.rows().every(function () {
        this.nodes().to$().removeClass('selected');
    });

})(jQuery);