<?php

declare(strict_types=1);

/**
 * AdminZone - datagrid pairing service accounts with AbraFlexi addresses.
 *
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone\ui;

/**
 * Datagrid (DataTables) listing service accounts; each row has an AbraFlexi
 * address picker with suggestions. Chosen address is paired immediately.
 */
class ExtIdGrid extends \Ease\Html\TableTag
{
    /**
     * @param array{id?:string,code?:string} $prefill proposed pairing to confirm
     */
    public function __construct(private \AdminZone\ExtIdService $service, private array $prefill = [])
    {
        parent::__construct(null, ['class' => 'table table-striped table-bordered', 'id' => 'extidgrid', 'style' => 'width:100%']);
    }

    public function finalize(): void
    {
        \Ease\TWB\Part::jQueryze();
        $this->includeJavaScript('js/jquery.dataTables.min.js');
        $this->includeJavaScript('js/dataTables.bootstrap.min.js');
        $this->includeCss('css/dataTables.bootstrap.min.css');
        $this->includeJavaScript('js/select2.full.js');
        $this->includeCss('css/select2.css');

        $columns = [];

        foreach ($this->service->columns() as $field => $title) {
            $columns[] = ['data' => $field, 'title' => $title, 'defaultContent' => '', 'isId' => $field === 'id'];
        }

        $flags = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE;
        $this->addJavaScript('
var extIdService = '.json_encode($this->service->key(), $flags).';
var extIdColumns = '.json_encode($columns, $flags).';
var extIdRows = '.json_encode($this->service->items(), $flags).';
var extIdPrefill = '.json_encode($this->prefill, $flags).';
var extIdTitles = '.json_encode(['address' => _('AbraFlexi Address'), 'placeholder' => _('Type code or name of the customer'), 'only' => _('Only unassigned'), 'confirm' => _('Confirm')], $flags).';
'.file_get_contents(__DIR__.'/../../js/extidgrid.js'));
        parent::finalize();
    }
}
