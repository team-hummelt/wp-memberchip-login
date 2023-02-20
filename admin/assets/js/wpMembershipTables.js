let dataTableMembershipsOverview

function wpMembershipTable() {

    dataTableMembershipsOverview = new DataTable('#dataTablesMembership', {
        "language": {
            "url": wml_ajax_obj.data_table
        },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Alle"]],
        "pageLength": 10,
        "searching": true,
        "paging": true,
        "autoWidth": true,
        "columns": [
            null,
            null,
            {
                "width": "20%"
            },
            {
                "width": "30%"
            },
            null,
            null,
            null,
            null,
            {
                "width": "6%"
            },
            {
                "width": "6%"
            },
        ],
        columnDefs: [{
            orderable: false,
            targets:[8,9]
        }, {
            targets: [0, 1, 7],
            className: 'align-middle'
        }, {
            targets: [2, 3, 4, 5, 6, 8, 9],
            className: 'align-middle text-center'
        }
        ],
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": {
            url: wml_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'MembershipLogin',
                _ajax_nonce: wml_ajax_obj.nonce,
                method: 'wp_membership_table'
            }
        }
    });
}


let dataTableDocuments;
function documentsTable() {
    dataTableDocuments = new DataTable('#dataTablesDocuments', {
        "language": {
            "url": wml_ajax_obj.data_table
        },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Alle"]],
        "pageLength": 10,
        "searching": true,
        "paging": true,
        "autoWidth": true,
        "select": true,
        "columns": [
            null,
            {
                "width": "30%"
            },
            null,
            {
                "width": "30%"
            },
            null,
            null,
            null,
            null,
            null,
            null,
            {
                "width": "6%"
            },
            {
                "width": "6%"
            },
            {
                "width": "6%"
            },
        ],
        columnDefs: [{
            orderable: false,
            targets: [0, 10, 11, 12]
        }, {
            targets: [ 1, 2, 3, 4, 5, 9],
            className: 'align-middle'
        }, {
            targets: [0, 6, 7, 8, 10, 11, 12],
            className: 'align-middle text-center'
        }
        ],
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": {
            url: wml_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'MembershipLogin',
                _ajax_nonce: wml_ajax_obj.nonce,
                method: 'wp_membership_document_table'
            }
        }
    });
}

let dataTableMembershipsDownloads

function wpMembershipDownloadTable() {

    dataTableMembershipsDownloads = new DataTable('#dataTablesDocumentsDownload', {
        "language": {
            "url": wml_ajax_obj.data_table
        },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Alle"]],
        "pageLength": 10,
        "searching": true,
        "paging": true,
        "autoWidth": true,
        "columns": [
            null,
            {
                "width": "20%"
            },
            {
                "width": "20%"
            },
            null,
            null,
            {
                "width": "30%"
            },
            {
                "width": "6%"
            },
        ],
        columnDefs: [{
            orderable: false,
            targets:[0,6]
        }, {
            targets: [1,2,3,4,5],
            className: 'align-middle'
        }, {
            targets: [0,6],
            className: 'align-middle text-center'
        }
        ],
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": {
            url: wml_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'MembershipLogin',
                _ajax_nonce: wml_ajax_obj.nonce,
                method: 'wp_membership_download_table'
            }
        }
    });
}