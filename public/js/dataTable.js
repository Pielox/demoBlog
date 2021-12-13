$(document).ready(function() {
    $('#table-backoffice').DataTable({
        language: {

            url: 'js/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 1,4,5,6 ] }
        ]
    });

    $('#table-backoffice-commentaires').DataTable({
        language: {

            url: 'js/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 4 ] }
        ]
    });
});