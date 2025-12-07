(function($) {
    "use strict"

    var table = $('#datatable').DataTable({
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
    "use strict"

    var table = $('#datatable_download').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
        },
        dom: 'Blfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'EXCEL',
                className: 'btn btn-primary btn-sm mb-2 rounded-0',
                customizeData: function(data) {
                    for (var i = 0; i < data.body.length; i++) {
                        for (var j = 0; j < data.body[i].length; j++) {
                            data.body[i][j] = data.body[i][j].replace(/\n/g, " ");
                        }
                    }
                },
                filename: 'tabela',
                exportOptions: {
                    format: {
                        body: function (data) {
                            return data.replace(/;/g, ","); // evita conflito com separador
                        }
                    }
                }
            }
        ],
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