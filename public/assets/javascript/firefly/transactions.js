if ($('input[name="expense_account"]').length > 0) {
    $.getJSON('json/expense-accounts').success(function (data) {
        $('input[name="expense_account"]').typeahead({source: data});
    });
}
if ($('input[name="revenue_account"]').length > 0) {
    $.getJSON('json/revenue-accounts').success(function (data) {
        $('input[name="revenue_account"]').typeahead({source: data});
    });
}
if ($('input[name="category"]').length > 0) {
    $.getJSON('json/categories').success(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });
}

$(document).ready(function () {
    $('#transactionTable').DataTable(
        {
            serverSide: true,
            ajax: URL,
            paging: true,
            processing: true,
            order: [],
            "lengthMenu": [[50, 100, 250, -1], [50, 100, 250, "All"]],
            columns: [
                {
                    name: 'date',
                    data: 'date',
                    searchable: false
                },
                {
                    name: 'description',
                    data: 'description',
                    render: function (data, type, full, meta) {
                        var icon = '';
                        if (display == 'expenses') {
                            icon = 'glyphicon-arrow-left';
                        }
                        if (display == 'revenue') {
                            icon = 'glyphicon-arrow-right';
                        }
                        if (display == 'transfers') {
                            icon = 'glyphicon-resize-full';
                        }

                        return '<span class="glyphicon ' + icon + '"></span> ' +
                        '<a href="' + data.url + '" title="' + data.description + '">' + data.description + '</a>';
                    }
                },
                {
                    name: 'amount',
                    data: 'amount',
                    'title': 'Amount (\u20AC)',
                    searchable: false,
                    render: function (data, type, full, meta) {
                        if (display == 'expenses') {
                            return '<span class="text-danger">\u20AC ' + data.toFixed(2) + '</span>';
                        }
                        if (display == 'revenue') {
                            return '<span class="text-success">\u20AC ' + data.toFixed(2) + '</span>';
                        }
                        if (display == 'transfers') {
                            return '<span class="text-info">\u20AC ' + data.toFixed(2) + '</span>';
                        }
                    }
                },
                {
                    name: 'from',
                    data: 'from',
                    searchable: false,
                    render: function (data, type, full, meta) {
                        return '<a href="' + data.url + '" title="' + data.name + '">' + data.name + '</a>';
                    }
                },
                {
                    name: 'to',
                    data: 'to',
                    searchable: false,
                    render: function (data, type, full, meta) {
                        return '<a href="' + data.url + '" title="' + data.name + '">' + data.name + '</a>';
                    }
                },
                {
                    name: 'components',
                    data: 'components',
                    searchable: true,
                    sortable: false,
                    title: '',
                    render: function (data, type, full, meta) {
                        var html = '';
                        if (data.budget_id > 0) {
                            html += '<a href="' + data.budget_url + '" title="' + data.budget_name + '"><i class="fa fa-tasks fa-fw"></i></a> ';
                        }
                        if (data.category_id > 0) {
                            html += '<a href="' + data.category_url + '" title="' + data.category_name + '"><i class="fa fa-bar-chart fa-fw"></i></a> ';
                        }
                        return html;
                    }
                },
                {
                    name: 'id',
                    data: 'id',
                    searchable: false,
                    sortable: false,
                    title: '',
                    render: function (data, type, full, meta) {
                        return '<div class="btn-group btn-group-xs">' +
                        '<a class="btn btn-default btn-xs" href="' + data.edit + '">' +
                        '<span class="glyphicon glyphicon-pencil"</a>' +
                        '<a class="btn btn-danger btn-xs" href="' + data.delete + '">' +
                        '<span class="glyphicon glyphicon-trash"</a>' +
                        '</a></div>';
                    }
                }
            ]
        }
    );
});