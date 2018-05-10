<?php

namespace Icinga\Module\Vspheredb;

use DateTime;
use Exception;
use Icinga\Application\Logger;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;
use SoapVar;

/**
 * Class Api
 *
 * This is your main entry point when working with this library
 */
class Api
{
    /** @var CurlLoader */
    private $curl;

    /** @var string */
    private $host;

    /** @var string */
    private $user;

    /** @var string */
    private $pass;

    /** @var string */
    private $wsdlDir;

    /** @var string */
    private $cacheDir;

    /** @var mixed */
    private $serviceInstance;

    /** @var SoapClient */
    private $soapClient;

    /** @var VCenterServer */
    private $vCenterServer;

    /** @var string */
    private $binaryUuid;

    /** @var EventManager */
    private $eventManager;

    /** @var PerfManager */
    private $perfManager;

    /** @var PropertyCollector */
    private $propertyCollector;

    /**
     * Involved WSDL files
     *
     * We'll always fetch and store them in case they are not available. Pay
     * attention when modifying this list, we'll use the first one to start
     * with when connecting to the SOAP API
     *
     * @var array
     */
    private $wsdlFiles = [
        'vimService.wsdl',
        'vim.wsdl',
        'core-types.xsd',
        'query-types.xsd',
        'query-messagetypes.xsd',
        'reflect-types.xsd',
        'reflect-messagetypes.xsd',
        'vim-types.xsd',
        'vim-messagetypes.xsd',
    ];

    /**
     * Api constructor.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     */
    public function __construct($host, $user, $pass)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }

    public static function forServer(VCenterServer $server)
    {
        $api = new static(
            $server->get('host'),
            $server->get('username'),
            $server->get('password')
        );

        $api->vCenterServer = $server;

        $curl = $api->curl();

        if ($type = $server->get('proxy_type')) {
            $curl->setProxy($server->get('proxy_address'), $type);

            if ($user = $server->get('proxy_user')) {
                $curl->setProxyAuth($user, $server->get('proxy_pass'));
            }
        }

        if ($server->get('scheme') === 'https') {
            if ($server->get('ssl_verify_peer') === 'n') {
                $curl->disableSslPeerVerification();
            }
            if ($server->get('ssl_verify_host') === 'n') {
                $curl->disableSslHostVerification();
            }
        }

        return $api;
    }

    public function getBinaryUuid()
    {
        if ($this->binaryUuid === null) {
            $about = $this->getAbout();

            if ($about->apiType === 'VirtualCenter') {
                $this->binaryUuid = Util::uuidToBin($about->instanceUuid);
            } elseif ($about->apiType === 'HostAgent') {
                /// NONO, TODO: bios uuid?!
                $this->binaryUuid = Util::uuidToBin(md5($this->host));
            } else {
                throw new IcingaException(
                    'Unsupported API type "%s"',
                    $about->apiType
                );
            }
        }

        return $this->binaryUuid;
    }

    public function makeGlobalUuid($moRefId)
    {
        return sha1($this->getBinaryUuid() . $moRefId, true);
    }

    public function getAbout()
    {
        return $this->getServiceInstance()->about;
    }

    public function getCurrentTime()
    {
        $result = $this->soapCall(
            'CurrentTime',
            $this->makeBaseServiceInstanceParam()
        );

        return new DateTime($result->returnval);
    }

    /**
     * @return CurlLoader
     */
    public function curl()
    {
        if ($this->curl === null) {
            $this->curl = new CurlLoader($this->host, null, null, $this->cacheDir());
            $this->curl->on('cookie', [$this, 'gotCookie']);
        }

        return $this->curl;
    }

    public function gotCookie($cookie)
    {
        Logger::info('Got new session cookie from VCenter');
    }

    public function eventManager()
    {
        if ($this->eventManager === null) {
            $this->eventManager = new EventManager($this);
        }

        return $this->eventManager;
    }

    public function perfManager()
    {
        if ($this->perfManager === null) {
            $this->perfManager = new PerfManager($this);
        }

        return $this->perfManager;
    }

    public function propertyCollector()
    {
        if ($this->propertyCollector === null) {
            $this->propertyCollector = new PropertyCollector($this);
        }

        return $this->propertyCollector;
    }

    /**
     * SOAP call wrapper
     *
     * Accepts the desired method name and optional arguments
     *
     * @param $method
     * @return mixed
     * @throws AuthenticationException
     * @throws ConfigurationError
     */
    public function soapCall($method)
    {
        $arguments = func_get_args();
        array_shift($arguments);

        try {
            return $this->soapClient()->__soapCall($method, $arguments);
        } catch (AuthenticationException $e) {
            if ($method === 'Login' || $method === 'Logout') {
                throw $e;
            } else {
                try {
                    $this->logout();
                } catch (AuthenticationException $ae) {
                    // That's fine.
                }
                $this->login();

                return $this->soapClient()->__soapCall($method, $arguments);
            }
        }
    }

    /**
     * Builds our base url
     *
     * @return string
     */
    protected function makeLocation()
    {
        return 'https://' . $this->host . '/sdk';
    }

    /**
     * Lazy-instantiation of our SoapClient
     *
     * @return SoapClient
     * @throws ConfigurationError
     */
    protected function soapClient()
    {
        if ($this->soapClient === null) {
            $this->prepareWsdl();
            $wsdlFile = $this->wsdlDir() . '/' . $this->wsdlFiles[0];
            $features = SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE;
            $options = [
                'trace'              => true,
                'location'           => $this->makeLocation(),
                'exceptions'         => true,
                'connection_timeout' => 10,
                'classmap'           => ApiClassMap::getMap(),
                'features'           => $features,
                'cache_wsdl'         => WSDL_CACHE_NONE
            ];

            $soap = new SoapClient($wsdlFile, $options);
            $soap->setCurl($this->curl());
            $this->soapClient = $soap;
        }

        return $this->soapClient;
    }

    /**
     * Our WSDL cache
     *
     * @return string
     * @throws ConfigurationError
     */
    protected function wsdlDir()
    {
        if ($this->wsdlDir === null) {
            $this->wsdlDir = $this->cacheDir();
        }

        return $this->wsdlDir;
    }

    /**
     * Make sure all our WSDL files are in place, fetch missing ones
     * @throws ConfigurationError
     */
    protected function prepareWsdl()
    {
        $curl = $this->curl();
        $dir = $this->wsdlDir();
        foreach ($this->wsdlFiles as $file) {
            if (! file_exists("$dir/$file")) {
                $wsdl = $curl->get($curl->url("sdk/$file"));
                file_put_contents("$dir/$file", $wsdl);
            }
        }
    }

    /**
     * A ServiceInstance, lazy-loaded only once
     *
     * This is a stdClass for now, might become a dedicated class
     *
     * @return mixed
     * @throws AuthenticationException
     */
    public function getServiceInstance()
    {
        if ($this->serviceInstance === null) {
            $this->serviceInstance = $this->fetchServiceInstance();
        }

        return $this->serviceInstance;
    }

    /**
     * Just a custom version string
     *
     * Please to not make assumptions based on the format of this string, it
     * is for visualization purposes only and might change without pre-announcement
     *
     * @return string
     * @throws AuthenticationException
     */
    public function getVersionString()
    {
        $about = $this->getServiceInstance()->about;

        return sprintf(
            "%s on %s, api=%s (%s)\n",
            $about->fullName,
            $about->osType,
            $about->apiType,
            $about->licenseProductName
        );
    }

    /**
     * Really fetch the ServiceInstance
     *
     * @see getServiceInstance()
     *
     * @return mixed
     * @throws AuthenticationException
     */
    protected function fetchServiceInstance()
    {
        $result = $this->soapCall(
            'RetrieveServiceContent',
            $this->makeBaseServiceInstanceParam()
        );

        return $result->returnval;
    }

    protected function makeBaseServiceInstanceParam()
    {
        $param = array(
            $this->makeVar('ServiceInstance', 'ServiceInstance')
        );

        return new SoapVar($param, SOAP_ENC_OBJECT);
    }

    /**
     * Log in to to API
     *
     * This will retrieve a session cookie and pass it with subsequent requests
     * @throws AuthenticationException
     */
    public function login()
    {
        if ($this->curl()->hasCookie()) {
            Logger::debug('Using existing Cookie');
            return $this;
        }

        Logger::debug('Sending Login request to %s', $this->makeLocation());
        $request = array(
            '_this'    => $this->getServiceInstance()->sessionManager,
            'userName' => $this->user,
            'password' => $this->pass,
        );

        $this->soapCall('Login', $request);

        return $this;
    }

    /**
     * Logout, destroy our session
     */
    public function logout()
    {
        $request = array(
            '_this' => $this->getServiceInstance()->sessionManager
        );
        try {
            $this->soapCall('Logout', $request);
        } catch (Exception $e) {
            // Do nothing
        }
        $this->curl()->forgetCookie();
    }

    protected function makeObjectSet(SelectSet $selectSet, $base = null)
    {
        if ($base === null) {
            $base = $this->getServiceInstance()->rootFolder;
        }

        return [
            'obj'   => $base,
            'skip'  => false,
            'selectSet' => $selectSet->toArray(),
        ];
    }

    public function makePropertyFilterSpec(PropertySet $propSet, SelectSet $selectSet)
    {
        return array(
            'propSet'   => $propSet->toArray(),
            'objectSet' => $this->makeObjectSet($selectSet)
        );
    }

    /**
     * @return string
     * @throws ConfigurationError
     */
    protected function cacheDir()
    {
        if ($this->cacheDir === null) {
            $this->cacheDir = SafeCacheDir::getDirectory();
        }

        return $this->cacheDir;
    }

    /**
     * Just a helper method
     *
     * @return SoapVar
     */
    public function makeVar($key, $val)
    {
        return new SoapVar($val, XSD_STRING, $key, null, 'ns1:_this');
    }

    public function __destruct()
    {
        unset($this->perfManager);
        unset($this->propertyCollector);
    }
}
