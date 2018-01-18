<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Vspheredb\DbObject\VmConfig;
use Icinga\Module\Vspheredb\ManagedObject\TraversalHelper;
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

    /** @var IdLookup */
    private $idLookup;

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
    private $wsdlFiles = array(
        'vimService.wsdl',
        'vim.wsdl',
        'core-types.xsd',
        'query-types.xsd',
        'query-messagetypes.xsd',
        'reflect-types.xsd',
        'reflect-messagetypes.xsd',
        'vim-types.xsd',
        'vim-messagetypes.xsd',
    );

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

    /**
     * @return CurlLoader
     */
    public function curl()
    {
        if ($this->curl === null) {
            $this->curl = new CurlLoader($this->host);
        }

        return $this->curl;
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
     */
    public function soapCall($method)
    {
        $arguments = func_get_args();
        array_shift($arguments);

        return $this->soapClient()->__soapCall($method, $arguments);
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
     */
    protected function soapClient()
    {
        if ($this->soapClient === null) {
            $this->prepareWsdl();
            $wsdlFile = $this->wsdlDir() . '/' . $this->wsdlFiles[0];
            $features = SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE;
            $options = array(
                'trace'              => true,
                'location'           => $this->makeLocation(),
                'exceptions'         => true,
                'connection_timeout' => 10,
                // 'classmap'        => $this->getClassMap(), // might become useful
                'features'           => $features,
                'cache_wsdl'         => WSDL_CACHE_NONE
            );

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
     */
    protected function fetchServiceInstance()
    {
        $param = array(
            $this->makeVar('ServiceInstance', 'ServiceInstance')
        );

        $result = $this->soapCall(
            'RetrieveServiceContent',
            new SoapVar($param, SOAP_ENC_OBJECT)
        );

        return $result->returnval;
    }

    /**
     * Log in to to API
     *
     * This will retrieve a session cookie and pass it with subsequent requests
     */
    public function login()
    {
        if ($this->curl()->hasCookie()) {
            return;
        }

        $request = array(
            '_this'    => $this->getServiceInstance()->sessionManager,
            'userName' => $this->user,
            'password' => $this->pass,
        );

        $this->soapCall('Login', $request);
    }

    /**
     * Logout, destroy our session
     */
    public function logout()
    {
        $request = array(
            '_this' => $this->getServiceInstance()->sessionManager
        );
        $this->soapCall('Logout', $request);
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
     * @param Db $db
     * @return IdLookup
     */
    public function idLookup(Db $db)
    {
        if ($this->idLookup === null) {
            $this->idLookup = new IdLookup($this, $db);
        }

        return $this->idLookup;
    }

    protected function cacheDir()
    {
        if ($this->cacheDir === null) {
            $user = $this->getCurrentUsername();
            $dirname = sprintf(
                '%s/%s-%s',
                sys_get_temp_dir(),
                'iwebVsphere',
                $user
            );

            if (file_exists($dirname)) {
                if ($this->uidToName(fileowner($dirname)) !== $user) {
                    throw new ConfigurationError(
                        '%s exists, but does not belong to %s',
                        $dirname,
                        $user
                    );
                }
            } else {
                if (! mkdir($dirname, 0700)) {
                    throw new ConfigurationError(
                        'Could not create %s',
                        $dirname
                    );
                }
            }

            $this->cacheDir = $dirname;
        }

        return $this->cacheDir;
    }

    protected function uidToName($uid)
    {
        $info = posix_getpwuid($uid);
        return $info['name'];
    }

    protected function getCurrentUsername()
    {
        if (function_exists('posix_geteuid')) {
            return $this->uidToName(posix_geteuid());
        } else {
            throw new ConfigurationError(
                'POSIX methods not available, is php-posix installed and enabled?'
            );
        }
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
