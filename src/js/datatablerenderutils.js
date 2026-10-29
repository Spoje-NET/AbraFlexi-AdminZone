/**
 * broker.dbfinance.cz
 * 
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright (c) 2017-2019, Vítězslav Dvořák
 */

/**
 * obtain Current column name
 * 
 * @param {Object} row
 * @param {Object} opts
 * 
 * @return {String}
 */
function getIdColumnName(row, opts) {
    var myColumnName = opts.settings.aoColumns[ opts.col ].data;
    var idColumnName = null;

    var idColumnCandidate = opts.settings.aoColumns[ opts.col ].idColumn ? opts.settings.aoColumns[ opts.col ].idColumn : myColumnName + '_id';
    if (idColumnCandidate in row) {
        idColumnName = idColumnCandidate;
    } else {
        console.log(idColumnCandidate + " not found");
    }
    return idColumnName;
}

/**
 * 
 * @param {type} data
 * @param {type} type
 * @param {type} row
 * @param {type} opts
 * @return {String}
 */
function renderIdLink(data, type, row, opts) {
    var idColumnName = getIdColumnName(row, opts);
    if (idColumnName) {
        data = '<a href="' + opts.settings.aoColumns[ opts.col ].detailPage + '?id=' + row[idColumnName] + '">' + data + '</a>';
    } else {
        data += " !!!";
    }
    return data;
}
