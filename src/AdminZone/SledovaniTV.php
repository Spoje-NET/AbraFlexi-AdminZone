<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

/**
 * Description of SledovaniTV
 *
 * @author vitex
 */
class SledovaniTV extends \Ease\Molecule
{
    /**
     * Version of SledovaniTV library
     *
     * @var string
     */
    public static $libVersion = '0.1';

    /**
     * Curl Handle.
     *
     * @var resource
     */
    public $curl = null;

    /**
     * Informace o posledním HTTP requestu.
     *
     * @var *
     */
    public $curlInfo;

    /**
     * Informace o poslední HTTP chybě.
     *
     * @var string
     */
    public $lastCurlError = null;

    /**
     *
     * @var boolean
     */
    public bool $debug = false;

    /**
     *
     * @var string
     */
    public $baseUrl = 'https://sledovanitv.cz/partner/api/';

    /**
     *
     * @var string
     */
    public $username = null;

    /**
     *
     * @var string
     */
    public $password = null;

    /**
     *
     * @var int
     */
    private $lastResponseCode = null;
    private $lastCurlResponse;

    public function __construct()
    {
        $this->setUp(\Ease\Shared::singleton()->configuration);
        $this->curlInit();
    }

    /**
     * Inicializace CURL
     */
    public function curlInit()
    {
        $this->curl = \curl_init(); // create curl resource
        curl_setopt(
            $this->curl,
            CURLOPT_USERPWD,
            $this->username . ":" . $this->password
        );
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); // return content as a string from curl_exec
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true); // follow redirects (compatibility for future changes in IPEX)
        curl_setopt($this->curl, CURLOPT_HTTPAUTH, true);       // HTTP authentication
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // IPEX by default uses Self-Signed certificates
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_VERBOSE, ($this->debug === true)); // For debugging
    }

    /**
     * SetUp Object to be ready for connect
     *
     * @param array $options Object Options (company,url,user,password,section,
     *                                       defaultUrlParams,debug)
     */
    public function setUp($options = [])
    {
        $this->setupProperty($options, 'username', 'SLTV_LOGIN');
        $this->setupProperty($options, 'password', 'SLTV_PASSWORD');
        $this->setupProperty($options, 'debug');
    }

    /**
     * Vykonej HTTP požadavek
     *
     * @param string $url    URL požadavku
     *
     * @return int HTTP Response CODE
     */
    public function doCurlRequest($url)
    {



        curl_setopt($this->curl, CURLOPT_URL, $url);

        $httpHeaders = ['User-Agent' => ' v' . self::$libVersion];

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $httpHeaders);

// Proveď samotnou operaci
        $this->lastCurlResponse = curl_exec($this->curl);
        $this->curlInfo = curl_getinfo($this->curl);
        $this->curlInfo['when'] = microtime();
        $this->lastResponseCode = $this->curlInfo['http_code'];
        $this->lastCurlError = curl_error($this->curl);
        if (strlen($this->lastCurlError)) {
            $this->addStatusMessage(sprintf(
                'Curl Error (HTTP %d): %s',
                $this->lastResponseCode,
                $this->lastCurlError
            ), 'error');
        }

        return $this->lastResponseCode;
    }

    public function requestData($function, $parameters)
    {
        $this->rowCount = null;
        $url = $this->baseUrl . $function;
        $this->doCurlRequest(\Ease\Functions::addUrlParams($url, $parameters));
        return $this->rawResponseToArray($this->lastCurlResponse);
    }

    /**
     * Parse Raw IPEX response in several formats
     *
     * @param string $responseRaw raw response body
     * @param string $format      Raw Response format json|xml|etc
     *
     * @return array
     */
    public function rawResponseToArray($responseRaw)
    {
        $responseDecoded = json_decode($responseRaw, true, 10);
        $decodeError = json_last_error_msg();
        if ($decodeError != 'No error') {
            $this->addStatusMessage('JSON Decoder: ' . $decodeError, 'error');
            $this->addStatusMessage($responseRaw, 'debug');
        }
        return $responseDecoded;
    }

    /**
     * Parse Response array
     *
     * @param array $responseDecoded
     * @param int $responseCode Request Response Code
     *
     * @return array main data part of response
     */
    public function parseResponse($responseDecoded, $responseCode)
    {
        $response = null;
        switch ($responseCode) {
            case 201: //Success Write
                if (isset($responseDecoded[$this->resultField][0]['id'])) {
                    $this->lastInsertedID = $responseDecoded[$this->resultField][0]['id'];
                    $this->setMyKey($this->lastInsertedID);
                    $this->apiURL = $this->getSectionURL() . '/' . $this->lastInsertedID;
                } else {
                    $this->lastInsertedID = null;
                }
            case 200: //Success Read
                $response = $this->lastResult = $responseDecoded;
                break;

            case 500: // Internal Server Error
            case 404: // Page not found
                if ($this->ignoreNotFound === true) {
                    break;
                }
            case 400: //Bad Request parameters
            default: //Something goes wrong
                $this->addStatusMessage(
                    $responseCode . ': ' . $this->curlInfo['url'],
                    'warning'
                );
                if (is_array($responseDecoded)) {
                    $this->parseError($responseDecoded);
                }
                $this->logResult($responseDecoded, $this->curlInfo['url']);
                break;
        }
        return $response;
    }

    public function getUsers($limit = null, $offset = 0, $tags = null)
    {
        $usersRaw = $this->requestData(
            'get-users',
            ['limit' => $limit, 'offset' => $offset, 'tags' => $tags]
        );
        return (array_key_exists('status', $usersRaw) && $usersRaw['status'] == 1) ? $usersRaw['users'] : null;
    }

    /**
     * Změní zadané údaje uživatele. Údaje, které nejsou zadané jako parametry,
     * zůstanou nezměněny.
     * Uživatel musí být určen buď pomocí partnerid (id uživatele v systému
     * partnera), nebo userid (id uživatele v systému sledovanitv.cz).
     *
     * @param string  $partnerid     Identifikace uživatele ve vašem systému, max. 50 znaků
     * @param int     $userId        Identifikace uživatele v systémy sledovanitv.cz.
     * @param string  $userPassword  Heslo uživatele pro systém sledovanitv.cz, nebo hash hesla a salt, pokud uveden parametr salt. Viz dále.
     * @param string  $salt          max. 25 znaků, nepovinný **Náhodný řetězec, který se spolu s heslem zahashuje. Pokud není uveden, heslo je parametru userPassword v plaintextu.
     * @param string  $fullName      Celé jméno a příjmení uživatele
     * @param string  $address       Ulice bydliště uživatele
     * @param string  $city          Město bydliště uživatele
     * @param int     $postcode      5  číslic PSČ bydliště uživatele
     * @param string  $phone         Telefonní číslo uživatele
     * @param int     $streamQuality nepovinnýMax. kvalita / tok streamů pro uživatele, seznam kvalit je ve Správě partnera
     * @param string  $tags          nepovinnýNastaví uživateli tagy. Více tagů lze oddělit čárkou. Pokud je na začátku uveden symbol +, tak dojde k přidání zadaných tagů místopřepsání všech zadanými. Pokud je na začátku uveden symbol -, tak dojde k odebrání zadaných tagů místo přepsání všech zadanými.
     *
     * @return array result
     */
    public function modifyUser(
        $partnerid = null,
        $userId = null,
        $userPassword = null,
        $salt = null,
        $fullName = null,
        $address = null,
        $city = null,
        $postcode = null,
        $phone = null,
        $streamQuality = null,
        $tags = null
    ) {
        $usersRaw = $this->requestData(
            'modify-user',
            ['partnerid' => $partnerid,
                    'userId' => $userId, 'userPassword' => $userPassword,
                    'salt' => $salt, 'fullName' => $fullName, 'address' => $address,
                    'city' => $city, 'postcode' => $postcode, 'phone' => $phone,
            'streamQuality' => $streamQuality,
            'tags' => $tags]
        );
        return $usersRaw['status'] == 1 ? $usersRaw['id'] : $usersRaw['dataErrors'];
    }

    /**
     * Pošle informaci o uživateli systému sledovanitv.cz, ten vyhledá
     * partnerského uživatele a vrátí URL, na kterou má být uživatel
     * přesměrován. Neregistrovaný uživatel je přesměrován na registraci s
     * předvyplněnými údaji, které byly předány při volání funkce.
     * Registrovaný uživatel je přihlášen a přesměrován na přehrávání.
     *
     * @param string $partnerid      Identifikace uživatele ve vašem systému, max. 50 znaků
     * @param string $email          E-mailová adresa uživatele, slouží jako login pro uživatele
     * @param string $login          Umožňuje zadat jiný login než e-mail. Tato možnost musí být u partnera povolena v systému sledovanitv.cz.
     * @param string $fullName       Křestní jméno a příjmení uživatele, max. 150 znaků
     * @param string $address        Ulice a číslo popisné, max. 255 znaků
     * @param string $city           Město, max. 255 znaků
     * @param int    $postcode       PSČ
     * @param string $package        označení balíčku, který uživateli aktivovat po registraci; při vynechání parametru se použije výchozí balíček pro vaše uživatele
     * @param string $to             Dokdy aktivovat partnerský balíček. Vynechání parametru znamená aktivaci na dobu neurčitou
     * @param string $ip             Lokální IP adresa uživatele ve formátu a.b.c.d
     * @param string $services       Další rozšířené služby, které aktivovat po registraci. Formát parametru je specifikován v sekci Objednávka rozšířených služeb. Doba, do kdy budou aktivovány, se řídí parametrem to
     * @param string $promo          Pokud má parametr hodnotu 1, uživateli se místo partnerského balíčku a rozšířených služeb aktivují promo služby s omezenou platností.Nekombinujte s parametry package a service, parametr promozpůsobí, že budou ignorovány.
     * @param int    $data           Libovolný partnerský údaj, který se uloží k uživateli, uživateli se nezobrazuje. Max. 50 znaků.
     * @param string $userPassword   Heslo uživatele. Tato možnost musí být u partnera povolena v systému sledovanitv.cz.
     * @param int    $streamQuality  Max. kvalita / tok streamů pro uživatele, seznam kvalit je ve Správě partnera
     * @param string $serviceCode    Kód ceníkové položky (z funkce get-pricelist), která má být přidána do nákupního košíku; po přihlášení bude uživatel přesměrován na rekapitulaci objednávky
     *
     * @return array action: Na co vede URL: 'login' – uživatel je registrován, URL vede na přehrávání; 'register'  – uživatel není registrovaný, URL vede na registraci. url: URL, na které přesměrovat uživatele. token: Autentizační token pro registraci nebo přihlášení
     */
    public function identifyUser(
        $partnerid,
        $email = null,
        $login = null,
        $fullName = null,
        $address = null,
        $city = null,
        $postcode = null,
        $package = null,
        $to = null,
        $ip = null,
        $services = null,
        $promo = null,
        $data = null,
        $userPassword = null,
        $streamQuality = null,
        $serviceCode = null
    ) {
        return $this->requestData(
            'identify-user',
            ['partnerid' => $partnerid, 'email' => $email, 'login' => $login,
                            'fullName' => $fullName,
                            'address' => $address, 'city' => $city, 'postcode' => $postcode,
                            'package' => $package,
                            'to' => $to, 'ip' => $ip, 'services' => $services, 'promo' => $promo,
                            'data' => $data, 'userPassword' => $userPassword, 'streamQuality' => $streamQuality,
            'serviceCode' => $serviceCode]
        );
    }
}
