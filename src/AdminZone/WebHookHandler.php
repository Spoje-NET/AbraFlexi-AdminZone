<?php

namespace AdminZone;

/**
 * Description of WebHookHandler
 *
 * @author vitex
 */
class WebHookHandler extends \AbraFlexi\RW
{
    /**
     * Current processed Change id
     * @var int
     */
    public $changeid = null;

    // Do not redeclare $operation — AbraFlexi\RO already has protected ?string $operation.
    // Redeclaring as untyped public $operation fatals on PHP 8.4+.

    /**
     * External IDs for current record
     * @var array
     */
    public $extids = [];

    /**
     * Handle Incoming change
     *
     * @param int $id changed record id
     * @param array $options
     */
    public function __construct($id, $options)
    {
        try {
            parent::__construct($id, $options);
        } catch (\AbraFlexi\Exception $exc) {
            // A 404 means the record no longer exists in AbraFlexi (e.g. it
            // was deleted after the webhook was queued) - that is an
            // expected, recoverable situation, not an error.
            $this->addStatusMessage($exc->getMessage(), $exc->getCode() === 404 ? 'warning' : 'error');
            $this->setDataValue('typDokl', '');
        }
    }

    /**
     * SetUp Object to be ready for connect
     *
     * @param array $options Object Options (company,url,user,password,evidence,
     *                                       prefix,defaultUrlParams,debug)
     */
    public function setUp($options = []): bool
    {
        parent::setUp($options);
        if (isset($options['changeid'])) {
            $this->changeid = $options['changeid'];
        }
        if (isset($options['operation'])) {
            $this->setOperation($options['operation']);
            if ($options['operation'] == 'delete') {
                $this->ignore404(true);
            }
        }
        if (isset($options['external-ids'])) {
            $this->extids = $options['external-ids'];
        }
        return true;
    }

    /**
     *
     * @param type $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    public function flushCache()
    {
    }

    /**
     *
     */
    public function process($operation)
    {
        $result = false;
        switch ($operation) {
            case 'create':
                $result = $this->create();
                break;
            case 'update':
                $result = $this->update();
                break;
            case 'delete':
                $result = $this->delete();
                break;
        }
        return $result;
    }

    /**
     *
     */
    public function create()
    {
        if ($this->debug === true) {
            $this->addStatusMessage(
                \AbraFlexi\Functions::uncode($this->getRecordIdent()) . ': ' . _('No Create Action Defined'),
                'debug'
            );
        }
        return null;
    }

    /**
     *
     */
    public function update()
    {
        if ($this->debug === true) {
            $this->addStatusMessage(
                \AbraFlexi\Functions::uncode($this->getRecordIdent()) . ': ' . _('No Update Action Defined') . ' ' . json_encode($this->getChanges()),
                'debug'
            );
        }
        return null;
    }

    /**
     *
     */
    public function delete()
    {
        if ($this->debug === true) {
            $this->addStatusMessage(
                \AbraFlexi\Functions::uncode($this->getRecordIdent()) . ': ' . _('No Delete Action Defined'),
                'debug'
            );
        }
        return null;
    }

    /**
     *
     * @return array|null
     */
    public function getCurrentData()
    {

        $dataRaw = $this->getColumnsFromAbraFlexi(
            '*',
            ['id' => $this->getMyKey()]
        );
        return count($dataRaw) ? $dataRaw[0] : null;
    }

    /**
     *
     * @return array
     */
    public function getPreviousData()
    {
        $prevData = [];
        $prevDataRaw = $this->listingQuery()->select(['json'], true)->where('evidence', $this->getEvidence())->where('recordid', $this->getMyKey());
        if ($prevDataRaw) {
            $prevDataFetch = $prevDataRaw->fetchAll();
            if (array_key_exists('0', $prevDataFetch)) {
                $rawData = stripslashes($prevDataFetch[0]['json']);
                if (\Ease\Functions::isSerialized($rawData)) {
                    $prevData = unserialize($rawData);
                } else {
                    $prevData = json_decode($rawData, true);
                }
                if (is_bool($prevData)) {
                    $this->addStatusMessage('Unserializable: ' . $prevDataFetch[0]['json'], 'debug');
                    $prevData = [];
                }
            }
        }
        return $prevData;
    }

    /**
     *
     * @return array
     */
    public function getChanges()
    {
        return self::dataDiff($this->getData(), $this->getPreviousData());
    }

    /**
     * Result array contains different key with new|old values
     *
     * @param array $data
     * @param array $datb
     */
    public static function dataDiff($data, $datb)
    {
        $diff = [];
        $columns = array_merge(array_combine(array_keys($data), array_keys($data)), array_combine(array_keys($datb), array_keys($datb)));
        foreach ($columns as $column) {
            if (array_key_exists($column, $data) === false) {
                $data[$column] = '';
            }
            if (array_key_exists($column, $datb) === false) {
                $datb[$column] = '';
            }

            if (is_object($data[$column]) && get_class($data[$column]) == 'AbraFlexi\DateTime') {
                $data[$column] = strval($data[$column]);
            }
            if (is_object($datb[$column]) && get_class($datb[$column]) == 'AbraFlexi\DateTime') {
                $datb[$column] = strval($datb[$column]);
            }

            if (is_array($data[$column]) && is_array($datb[$column])) {
                $diff[$column] = self::dataDiff($data[$column], $datb[$column]);
            } else {
                if (serialize($data[$column]) != serialize($datb[$column])) {
                    if (!isset($diff[$column])) {
                        $diff[$column] = ['new' => $data[$column], 'old' => $datb[$column]];
                    }
                }
            }
        }
        return $diff;
    }

    /**
     *
     */
    public function getChangedData()
    {
    }
}
