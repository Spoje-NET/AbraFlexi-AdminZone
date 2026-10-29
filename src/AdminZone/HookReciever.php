<?php

/**
 * AdminZone - Příjemce WebHooku
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2025 Spoje.Net
 */

namespace AdminZone;

/**
 * Description of HookReciver
 *
 * @author vitex
 */
class HookReciever extends \AbraFlexi\Changes
{

    /**
     *
     * @var boolean
     */
    public $locked = false;

    public string $format = 'json';

    /**
     * Changess to process
     * @var array
     */
    public $changes = null;

   /**
     *
     * @var array
     */
    public $handlerCache = [];

    /**
     * Posledni zpracovana verze
     * @var int
     */
    public $lastProcessedVersion = null;

    /**
     * Database helper
     * @var \Ease\Brick
     */
    public $sqlHelper = null;

    /**
     * Webhook Processor lockfile
     * @var string
     */
    private $lockfile = '/tmp/webhook.lock';

    /**
     * Prijmac WebHooku
     */
    public function __construct()
    {
        parent::__construct();
        $this->lockfile = sys_get_temp_dir() . '/webhook.lock';
        $this->lastProcessedVersion = $this->getLastProcessedVersion();
        $this->locked = $this->locked();
    }

    /**
     * Poslouchá standartní vstup
     *
     * @return array zaslaná data
     */
    public function listen($what = 'php://input')
    {
        $input = null;
        $inputJSON = file_get_contents($what);
        if ($this->debug) {
            $logf = sys_get_temp_dir() . '/webpush_' . time() . '.json';
            if (file_put_contents($logf, $inputJSON)) {
                $this->addStatusMessage('Recieved webhook saved to ' . $logf);
            }
        }
        if (strlen($inputJSON)) {
            $input = json_decode($inputJSON, true); //convert JSON into array
            $lastError = json_last_error();
            if ($lastError) {
                $this->addStatusMessage(json_last_error_msg(), 'warning');
            }
        }
        if (isset($_SERVER['REMOTE_HOST']) && $this->debug) {
            $this->addStatusMessage(sprintf(
                            _('Recieved Webhook from %s %s'),
                            $_SERVER['REMOTE_ADDR'],
                            $_SERVER['REMOTE_HOST']
                    ), 'debug');
        }
        return $input;
    }

    /**
     * Zpracuje změny
     *
     * @param array $changes
     *
     * @return array list IDS processed
     */
    function processAbraFlexiChanges(array $changes)
    {
        $changepos = 0;
        $doneIDd = [];
        foreach ($changes as $change) {
            $changepos++;
            $evidence = $change['@evidence'];
            $inVersion = intval($change['@in-version']);
            $operation = $change['@operation'];
            $id = intval($change['id']);
            $externalIDs = isset($change['external-ids']) ? $change['external-ids'] : [];

            $docId = empty($externalIDs) ? $id : current($externalIDs);

            $handlerClassName = \AbraFlexi\Functions::evidenceToClassName($evidence);
            $handlerClassFile = dirname(__FILE__) . '/whplugins/' . $handlerClassName . '.php';
            if (file_exists($handlerClassFile)) {
                include_once $handlerClassFile;
            }

            $handlerClass = '\\AdminZone\\whplugins\\' . $handlerClassName;
            if (class_exists($handlerClass)) {
                $changeMeta = ['evidence' => $evidence, 'operation' => $operation,
                    'external-ids' => $externalIDs,
                    'changeid' => $inVersion];
                try {
                    $saver = $this->getHandler(
                            $handlerClass,
                            $docId,
                            $changeMeta
                    );
                    if ($saver->process($operation) && ($this->debug === true)) {
                        $ident = \AbraFlexi\Functions::uncode($saver->getRecordIdent());
                        if (!empty($ident)) {
                            $id .= ' ' . $ident;
                        }
                        $this->addStatusMessage(sprintf(
                                        _('Processing Change %s/%s version %d ( %s %s/%s ) Last %d'),
                                        $changepos,
                                        count($this->changes),
                                        $inVersion,
                                        $operation,
                                        $evidence,
                                        $id,
                                        $this->lastProcessedVersion
                                ), 'success');
                    }
                } catch (\Throwable $exc) {
                    // One broken change must never block the ones behind it:
                    // FlexiBee keeps sending new events regardless, so we log
                    // and move on instead of letting the whole batch (and every
                    // following cron run, since this is always the oldest
                    // queued change) die on the same record.
                    $this->addStatusMessage(sprintf(
                                    _('Change %s/%s version %d ( %s %s/%s ) failed: %s'),
                                    $changepos,
                                    count($this->changes),
                                    $inVersion,
                                    $operation,
                                    $evidence,
                                    $id,
                                    $exc->getMessage()
                            ), 'error');
                }
            }
            $this->wipeCacheRecord($inVersion);
            $doneIDd[$inVersion] = $inVersion;
        }
        return $doneIDd;
    }

    /**
     * Get Changed Record Handler Class
     *
     * @param string $handlerClass
     * @param int    $id
     * @param array  $changeMeta
     *
     * @return WebHookHandler
     */
    public function &gethandler($handlerClass, $id, $changeMeta)
    {
        try {
            $handler = new $handlerClass($id, $changeMeta);
        } catch (\AbraFlexi\Exception $exc) {
            $handler = new $handlerClass(null, $changeMeta);
            $handler->dataReset();
            $handler->setDataValue('typDokl', '');
        }
        return $handler;
    }

    /**
     * Převezme změny z WebHooku do $this->changes
     *
     * @link https://www.abraflexi.eu/api/dokumentace/ref/changes-api/ Changes API
     *
     * @param array $changes pole změn nazvz sloupcu Json
     *
     * @return int Globální verze poslední přijaté změny
     */
    public function takeApiChanges(array $changes)
    {
        $result = null;
        if (array_key_exists('winstrom', $changes)) {
            $this->globalVersion = intval($changes['winstrom']['@globalVersion']);
            $this->changes = \Ease\Functions::reindexArrayBy(
                            $changes['winstrom']['changes'],
                            '@in-version'
            );

            ksort($this->changes);
        }
        $result = is_numeric($changes['winstrom']['next']) ? $changes['winstrom']['next'] - 1 : $this->globalVersion;

        foreach ($this->changes as $change) {
            $changesToLog[] = $change['@evidence'] . ' ' . $change['@operation'] . ':' . $change['id'] . (empty($change['external-ids']) ? '' : json_encode($change['external-ids']) );
        }

        $this->addStatusMessage(sprintf(
                        _('%s Changes to process: %s'),
                        count($this->changes),
                        implode(',', $changesToLog)
                ), 'debug');

        return $result;
    }

    /**
     * Directory for webhook processor state (last version, changes cache).
     */
    protected function stateDir(): string
    {
        $dir = (string) \Ease\Shared::cfg('STATE_DIR', '/var/lib/abraflexi-adminzone');
        if (!is_dir($dir) || !is_writable($dir)) {
            $dir = sys_get_temp_dir() . '/abraflexi-adminzone';
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        return $dir;
    }

    /**
     * File holding last processed change version of current AbraFlexi server.
     */
    protected function versionFile(): string
    {
        return $this->stateDir() . '/changeid-' . md5((string) \Ease\Shared::cfg('ABRAFLEXI_URL')) . '.txt';
    }

    /**
     * Directory with received but not yet processed changes.
     */
    protected function cacheDir(): string
    {
        $dir = $this->stateDir() . '/changes_cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        return $dir;
    }

    /**
     * Ulozi posledni zpracovanou verzi
     *
     * @param int $version
     */
    public function saveLastProcessedVersion($version)
    {
        $this->lastProcessedVersion = $version;
        if (file_put_contents($this->versionFile(), (string) intval($version), LOCK_EX) === false) {
            $this->addStatusMessage(
                    _("Last Processed Change ID Saving Failed"),
                    'error'
            );
        }
    }

    /**
     * Nacte posledni zpracovanou verzi
     *
     * @return int|null $version
     */
    public function getLastProcessedVersion()
    {
        $file = $this->versionFile();
        if (is_readable($file)) {
            return intval(file_get_contents($file));
        }
        $this->addStatusMessage(
                _("Last Processed Change ID Loading Failed"),
                'warning'
        );
        return null;
    }

    /**
     * Highest change version waiting in cache (or last processed one)
     *
     * @return int|null
     */
    public function getLastCachedVersion()
    {
        $files = glob($this->cacheDir() . '/*.json') ?: [];
        if (empty($files)) {
            return $this->getLastProcessedVersion();
        }
        sort($files);
        return intval(basename(end($files), '.json'));
    }

    /**
     * Lock into lockfile
     *
     * @return int size of saved lockfile in bytes
     */
    public function lock()
    {
        return file_put_contents($this->lockfile, getmypid());
    }

    /**
     * Webhook processor lock check
     *
     * @returm locked by PID
     */
    public function locked()
    {
        return $this->isLocked() ? intval(file_get_contents($this->lockfile)) : 0;
    }

    /**
     *
     * @return boolean
     */
    public function isProcessRunning()
    {
        if (!file_exists($this->lockfile) || !is_file($this->lockfile)) {
            return false;
        }
        $pid = file_get_contents($this->lockfile);
        return posix_kill($pid, 0);
    }

    /**
     *
     * @return boolean
     */
    public function isLocked()
    {
        $locked = false;
        $lockfilePresent = file_exists($this->lockfile);
        if ($lockfilePresent) {
            if ($this->isProcessRunning()) {
                $locked = true;
            } else {
                $ownerInfo = posix_getpwuid(fileowner($this->lockfile));
                $currentProcessPID = file_get_contents($this->lockfile);
                $locFileAge = time() - filemtime($this->lockfile);
                $this->addStatusMessage(sprintf(
                                'Orphaned lockfile found. pid: %d age: %s s. owned by %s',
                                $currentProcessPID,
                                $locFileAge,
                                $ownerInfo['name']
                        ), 'error');
                $this->unlock();
            }
        }
        return $locked;
    }

    /**
     * Remove lockfile
     */
    public function unlock()
    {
        return file_exists($this->lockfile) ? unlink($this->lockfile) : true;
    }

    /**
     * Load Webhooks from cache and Process
     *
     * @return boolean all changes processed successfully ?
     */
    public function processCachedChanges($limit = 50)
    {
        $result = false;
        $files = glob($this->cacheDir() . '/*.json') ?: [];
        sort($files);
        $changesToProcess = [];
        foreach (array_slice($files, 0, $limit) as $file) {
            $change = json_decode((string) file_get_contents($file), true);
            if (is_array($change)) {
                $changesToProcess[] = $change;
            }
        }
        if (!empty($changesToProcess)) {
            $this->changes = \Ease\Functions::reindexArrayBy(
                            $changesToProcess,
                            '@in-version'
            );
            $changesDone = $this->processAbraFlexiChanges($this->changes);
            if ($changesDone) {
                $this->saveLastProcessedVersion(max($changesDone));
            }
            $result = !empty($changesDone);
        }
        return $result;
    }

    /**
     * Save received changes into cache directory
     *
     * @param array $changes
     *
     * @return int lastChangeID
     */
    public function saveWebhookData($changes)
    {
        $apiData = [];
        foreach ($changes as $apiData) {
            $file = sprintf('%s/%012d.json', $this->cacheDir(), intval($apiData['@in-version']));
            file_put_contents($file, json_encode($apiData), LOCK_EX);
        }
        return array_key_exists('@in-version', $apiData) ? intval($apiData['@in-version']) : 0;
    }

    /**
     * skip webhooks with 'inversion' less or equal to $this->getLastProcessedVersion()
     *
     * @param array $webhookJsonData
     *
     * @return array
     */
    public function onlyFreshHooks($webhooksRawData)
    {
        $lastProcessed = $this->getLastProcessedVersion();
        foreach ($webhooksRawData as $recId => $webhookRawData) {
            if ($webhookRawData['@in-version'] <= $lastProcessed) {
                unset($webhooksRawData[$recId]);
            }
        }
        return $webhooksRawData;
    }

    /**
     * Empty given change version from cache
     *
     * @param int $inVersion
     *
     * @return bool
     */
    public function wipeCacheRecord($inVersion)
    {
        $file = sprintf('%s/%012d.json', $this->cacheDir(), intval($inVersion));
        return file_exists($file) ? unlink($file) : true;
    }
}
