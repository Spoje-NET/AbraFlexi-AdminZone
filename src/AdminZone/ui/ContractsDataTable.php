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
class ContractsDataTable extends DataTable
{
    public $columnDefs;

    public function prepareColumns($easeColumns)
    {
        $columns = [];
        $allColumns = \Ease\Functions::reindexArrayBy(parent::prepareColumns($easeColumns), 'name');
        \Ease\Functions::divDataArray($allColumns, $columns, 'id');
        \Ease\Functions::divDataArray($allColumns, $columns, 'kod');
        \Ease\Functions::divDataArray($allColumns, $columns, 'stavSml');
        \Ease\Functions::divDataArray($allColumns, $columns, 'nazev');
        \Ease\Functions::divDataArray($allColumns, $columns, 'cisDosle');
        \Ease\Functions::divDataArray($allColumns, $columns, 'autoGen');
        \Ease\Functions::divDataArray($allColumns, $columns, 'popis');
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
                    return  "<a href=\"contract.php?id=" + row["id"] +"\">" + data;
                },
                "targets": [1,3]
            },
            ' . self::renderYesNo('4') . '
        ]            
,
';
    }
}
