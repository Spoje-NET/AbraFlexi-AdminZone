function extIdEscape(text) {
    return $('<div>').text(text === null || text === undefined ? '' : text).html();
}

$(function () {
    var columns = extIdColumns.map(function (col) {
        if (col.isId) {
            col.render = function (data, type, row) {
                return (type === 'display' && row._url) ? '<a href="' + extIdEscape(row._url) + '" target="_blank">' + extIdEscape(data) + '</a>' : data;
            };
        }
        return col;
    });

    if (extIdPrefill.id && extIdPrefill.code) {
        extIdRows.forEach(function (row) {
            if (String(row.id) === String(extIdPrefill.id) && !row._code) {
                row._proposed = extIdPrefill.code;
            }
        });
    }

    columns.push({
        data: '_code',
        title: extIdTitles.address,
        render: function (data, type, row) {
            if (type !== 'display') {
                return data || '';
            }
            var shown = data || row._proposed || '';
            var option = shown ? '<option value="' + extIdEscape(shown) + '" selected>' + extIdEscape(row._label || shown) + '</option>' : '';
            var confirm = row._proposed && !data ? ' <button type="button" class="btn btn-warning btn-xs extid-confirm" data-id="' + extIdEscape(row.id) + '" data-code="' + extIdEscape(row._proposed) + '">' + extIdEscape(extIdTitles.confirm) + '</button>' : '';
            return '<select class="extid-select" style="width:100%" data-id="' + extIdEscape(row.id) + '"><option></option>' + option + '</select>' + confirm;
        }
    });

    var table = $('#extidgrid').DataTable({
        data: extIdRows,
        columns: columns,
        pageLength: 25,
        language: {url: 'assets/i18n/Czech.lang'},
        createdRow: function (tr, row) {
            $(tr).addClass(row._code ? 'success' : (row._proposed ? 'warning' : 'danger'));
        },
        drawCallback: function () {
            initSelects();
        }
    });

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!$('#extid-only-unassigned').is(':checked')) {
            return true;
        }
        return !table.row(dataIndex).data()._code;
    });

    $('#extidgrid_filter').append(' <label style="margin-left:1em"><input type="checkbox" id="extid-only-unassigned"> ' + extIdEscape(extIdTitles.only) + '</label>');
    $('#extid-only-unassigned').on('change', function () {
        table.draw();
    });

    function showStatus(ok, message) {
        $('#extid-status').attr('class', 'alert alert-' + (ok ? 'success' : 'danger')).text(message).show();
    }

    function assign(tr, id, code, text) {
        $.post('extidassign.php', {service: extIdService, id: id, code: code}, null, 'json')
                .done(function (response) {
                    showStatus(response.success, response.message);
                    if (response.success) {
                        var rowData = table.row(tr).data();
                        rowData._code = code;
                        rowData._label = text;
                        delete rowData._proposed;
                        tr.removeClass('danger warning').addClass('success');
                        tr.find('.extid-confirm').remove();
                    }
                })
                .fail(function () {
                    showStatus(false, 'Error');
                });
    }

    $('#extidgrid').on('click', '.extid-confirm', function () {
        var button = $(this);
        assign(button.closest('tr'), button.data('id'), button.data('code'), button.data('code'));
    });

    function initSelects() {
        $('.extid-select').not('.select2-hidden-accessible').each(function () {
            var select = $(this);
            select.select2({
                width: '100%',
                placeholder: extIdTitles.placeholder,
                minimumInputLength: 2,
                ajax: {
                    url: 'addresssuggest.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {term: params.term};
                    }
                }
            }).on('select2:select', function (event) {
                assign(select.closest('tr'), select.data('id'), event.params.data.id, event.params.data.text);
            });
        });
    }
});
