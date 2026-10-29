<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of ClientsDataTable
 *
 * @author vitex
 */
class ClientsDataTable extends DataTable
{
    public $filter = ['typVztahuK' => 'typVztahu.odberDodav'];

    public function prepareColumns($easeColumns)
    {
        $columns = [];
        $allColumns = \Ease\Functions::reindexArrayBy(parent::prepareColumns($easeColumns), 'name');
        \Ease\Functions::divDataArray($allColumns, $columns, 'id');
        \Ease\Functions::divDataArray($allColumns, $columns, 'kod');
        \Ease\Functions::divDataArray($allColumns, $columns, 'nazev');
        \Ease\Functions::divDataArray($allColumns, $columns, 'email');
        \Ease\Functions::divDataArray($allColumns, $columns, 'tel');
        \Ease\Functions::divDataArray($allColumns, $columns, 'lastUpdate');
        return $columns;
    }

    /**
     * @link https://datatables.net/examples/advanced_init/column_render.html
     *
     * @return string Column rendering
     */
    public function columnDefs()
    {
        return '
"columnDefs": [
            {
                "render": function ( data, type, row ) {
                    return  "<a href=\"adresar.php?id=" + row["id"] +"\">" + data;
                },
                "targets": [1,2]
            },
            {
                "render": function ( data, type, row ) {
                    return  "<a href=\"mailto:" + data +"\">" + data;
                },
                "targets": 3
            },
            {
                "render": function ( data, type, row ) {
                    return  "<a href=\"call:" + data +"\">" + data;
                },
                "targets": 4
            },
            ' . self::renderDate('5') . '
            { "visible": false,  "targets": [ 0 ] }
        ]            
,
';
    }
}
