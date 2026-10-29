<?php

/**
 * Dotazník - vyhledávací třída.
 *
 * @author     Vítězslav Dvořák <dvorak@austro-bohemia.cz>
 * @copyright  2015 Austro-Bohemia s.r.o.
 */

namespace AdminZone;

/**
 *
 */
class Searcher extends AbraFlexiEngine
{
    /**
     * Prohledávaná tabulka.
     *
     * @var string
     */
    public $table = null;

    /**
     * Prohledávaný sloupeček.
     *
     * @var string
     */
    public $column = null;

    /**
     * Pole prohledávacích obejktů.
     *
     * @var array
     */
    public $sysClasses = [];

    /**
     * Třída pro hromadné operace s konfigurací.
     *
     * @param string $class Třída použitá k hledání
     */
    public function __construct($class = null, $selector = null)
    {
        parent::__construct();

        if (is_null($class)) {
            $this->registerClass('\AbraFlexi\Adresar');
            $this->registerClass('\AbraFlexi\Kontakt');
            $this->registerClass('\AbraFlexi\Cenik');
        } else {
            $this->registerClass($class);
        }
    }

    /**
     * Zaregistruje prohledávanou tabulku.
     *
     * @param string $className
     */
    public function registerClass($className)
    {
        $newClass = new $className();
        $this->sysClasses[get_class($newClass)] = $newClass;
    }

    /**
     * Prohledá zaregistrované tabulky.
     *
     * @param string $term
     *
     * @return array
     */
    public function searchAll($term)
    {
        $results = [];
        foreach ($this->sysClasses as $searcherClass) {
            if (!is_null($this->table) && ($searcherClass->getMyTable() != $this->table)) {
                continue;
            }
            if (!is_null($this->column)) {
                if (isset($searcherClass->useKeywords[$this->column])) {
                    $searcherClass->useKeywords = [$this->column => $searcherClass->useKeywords[$this->column]];
                }
            }
//SQL            $found = $ieClass->searchString($term);
            $found = self::searchUsingClass($term, $searcherClass);

            if ($found) {
                $results[$searcherClass->getEvidence()] = $found;
            }
        }

        return $results;
    }

    /**
     * Obtain AbraFlexi search term
     *
     * @param string $term
     * @param array $columns
     *
     * @return string
     */
    public static function getSearchQuery($term, $columns)
    {
        $search = [];
        foreach ($columns as $colName => $eProperty) {
            switch (gettype($term)) {
                case 'integer':
                    if ($eProperty['type'] == 'integer') {
                        $search [] = $colName . " eq '$term'";
                    }
                    break;
                case 'float':
                    if ($eProperty['type'] == 'numeric') {
                        $search [] = $colName . " eq '$term'";
                    }
                    break;
                case 'string':
                    if ($eProperty['type'] == 'string') {
                        if ($colName == 'stitky') {
                            $search [] = $colName . "='code:$term'";
                        } else {
                            $search [] = $colName . " like similar '$term'";
                        }
                    }
                    break;
            }
        }
        return implode(' or ', $search);
    }

    /**
     * Search evidence of class for term occurence
     *
     * @param string $term
     * @param \AbraFlexi\RO $classToSearch
     *
     * @return array
     */
    public static function searchUsingClass($term, $classToSearch)
    {
        $evidence = $classToSearch->getEvidence();
        $columns = $classToSearch->getColumnsInfo();

        if ($evidence == 'cenik') {
            unset($columns['stavy']);
        }

        if (array_key_exists('nazev', $columns)) {
            $sortBy = 'nazev';
        }
        if (array_key_exists('prijmeni', $columns)) {
            $sortBy = 'prijmeni';
        }
        $classToSearch->defaultUrlParams['relations'] = 'prilohy';
        $classToSearch->defaultUrlParams['limit'] = 0;
        $results = $classToSearch->getColumnsFromAbraFlexi(
            'summary',
            [self::getSearchQuery($term, $columns)]
        );
        if (!empty($results)) {
            foreach ($results as $resid => $result) {
                switch ($sortBy) {
                    case 'nazev':
                        $results[$resid][0] = $result['nazev'];
                        break;
                    case 'prijmeni':
                        $results[$resid][0] = $result['prijmeni'] . ' ' . $result['jmeno'];
                        break;
                }
                $results[$resid]['what'] = $classToSearch->getEvidence();
            }
        }
        return $results;
    }
}
