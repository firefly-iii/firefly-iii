$(document).ready(function () {
    $('#recurringTable').DataTable(
        {
            serverSide: true,
            ajax: URL,
            paging: true,
            processing: true,
            order: [],
            "lengthMenu": [[50, 100, 250, -1], [50, 100, 250, "All"]],
            columns: [
                {
                    name: 'name',
                    data: 'name',
                    searchable: true,
                    title: 'Name',
                    render: function (data) {
                        return '<a href="' + data.url + '" title="' + data.name + '">' + data.name + '</a>';
                    }
                },
                {
                    name: 'match',
                    data: 'match',
                    searchable: true,
                    title: 'Matches on',
                    render: function (data) {
                        var str = '';
                        for (x in data) {
                            str += '<span class="label label-info">' + data[x] + '</span> ';
                        }
                        return str;//return '<a href="' + data.url + '" title="' + data.name + '">' + data.name + '</a>';
                    }
                },
                {
                    name: 'amount_min',
                    data: 'amount_min',
                    searchable: false,
                    title: '&rarr;',
                    render: function (data) {
                        return '<span class="text-info">\u20AC ' + data.toFixed(2) + '</span>';
                    }
                },
                {
                    name: 'amount_max',
                    data: 'amount_max',
                    searchable: false,
                    title: '&larr;',
                    render: function (data) {
                        return '<span class="text-info">\u20AC ' + data.toFixed(2) + '</span>';
                    }

                },
                {
                    name: 'date',
                    data: 'date',
                    title: 'Expected on',
                    searchable: false
                },

                {
                    name: 'active',
                    data: 'active',
                    searchable: false,
                    sortable: false,
                    render: function(data) {
                        if(data == 1) {
                            return '<i class="fa fa-check fa-faw"></i>';
                        } else {
                            return '<i class="fa fa-remove fa-faw"></i>';
                        }
                    },
                    title: 'Is active?'
                },
                {
                    name: 'automatch',
                    data: 'automatch',
                    sortable: false,
                    searchable: false,
                    render: function(data) {
                        if(data == 1) {
                            return '<i class="fa fa-check fa-faw"></i>';
                        } else {
                            return '<i class="fa fa-remove fa-faw"></i>';
                        }
                    },
                    title: 'Automatch?'
                },
                {
                    name: 'repeat_freq',
                    data: 'repeat_freq',
                    searchable: false,
                    sortable: false,
                    title: 'Repeat frequency'
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