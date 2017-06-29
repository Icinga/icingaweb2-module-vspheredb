<?php

namespace Icinga\Module\Vsphere;

use SoapVar;

class Api
{
    private $curl;
    private $host;
    private $user;
    private $pass;
    private $wsdlDir;
    private $serviceInstance;

    /** @var MySoapClient */
    private $soapClient;

    public function __construct($host, $user, $pass)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }

    protected function curl()
    {
        if ($this->curl === null) {
            $this->curl = new CurlLoader($this->host());
        }

        return $this->curl;
    }

    protected function host()
    {
        return $this->host;
    }

    public function soapCall($method)
    {
        $arguments = func_get_args();
        array_shift($arguments);
        return $this->soapClient()->__soapCall($method, $arguments);
    }

    protected function soapClient()
    {
        if ($this->soapClient === null) {
            $this->prepareWsdl();
            $wsdlFile = $this->wsdlDir() . '/vimService.wsdl';
            $options = array(
                'trace' => true,
                'location' => 'https://' . $this->host() . '/sdk',
                'exceptions' => true,
                'connection_timeout' => 10,
                // 'classmap' => $this->wsdlClassMapper->getClassMap(),
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE
            );

            $soap = new MySoapClient($wsdlFile, $options);
            $soap->setCurl($this->curl());
            $this->soapClient = $soap;
        }

        return $this->soapClient;
    }

    protected function wsdlDir()
    {
        if ($this->wsdlDir === null) {
            $dir = '/tmp/vmwareWsdl';
            if (! is_dir($dir)) {
                mkdir($dir);
            }

            $this->wsdlDir = $dir;
        }

        return $this->wsdlDir;
    }

    protected function prepareWsdl()
    {
        $files = array(
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

        $curl = $this->curl();
        $dir = $this->wsdlDir();
        foreach ($files as $file) {
            if (! file_exists("$dir/$file")) {
                $wsdl = $curl->get($curl->url("sdk/$file"));
                file_put_contents("$dir/$file", $wsdl);
            }
        }
    }

    public function getServiceInstance()
    {
        if ($this->serviceInstance === null) {
            $this->serviceInstance = $this->fetchServiceInstance();
        }

        return $this->serviceInstance;
    }

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

    public function logout()
    {
        $request = array(
            '_this' => $this->getServiceInstance()->sessionManager
        );
        $this->soapCall('Logout', $request);
        $this->curl()->forgetCookie();
    }

    protected function makeVar($key, $val)
    {
        return new SoapVar($val, XSD_STRING, $key, null, 'ns1:_this');
    }
}
