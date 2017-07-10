<?php

namespace Icinga\Module\Vsphere\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Vsphere\Api;
use Icinga\Module\Vsphere\Util;

/**
 * Class ImportSource
 *
 * This is where we provide an Import Source for the Icinga Director
 */
class ImportSource extends ImportSourceHook
{
    /** @var Api */
    protected $api;

    public function getName()
    {
        return 'VMware vSphere';
    }

    public function fetchData()
    {
        $api = $this->api();
        $api->login();
        $objects = $this->callOnManagedObject('fetchWithDefaults', $api);
        $api->idLookup()->enrichObjects($objects);
        $api->logout();
        return Util::createNestedObjects($objects);
    }

    public function listColumns()
    {
        return $this->callOnManagedObject('getDefaultPropertySet');
    }

    protected function getManagedObjectClass()
    {
        return 'Icinga\\Module\\Vsphere\\ManagedObject\\'
            . $this->getSetting('object_type');
    }

    protected function callOnManagedObject($method)
    {
        $params = func_get_args();
        array_shift($params);

        return call_user_func_array(array(
            $this->getManagedObjectClass(),
            $method
        ), $params);
    }

    protected function api()
    {
        if ($this->api === null) {
            $scheme = $this->getSetting('scheme', 'HTTPS');
            $this->api = new Api(
                $this->getSetting('host'),
                $this->getSetting('username'),
                $this->getSetting('password'),
                $scheme
            );
            $curl = $this->api->curl();

            if ($proxy = $this->getSetting('proxy')) {
                if ($proxyType = $this->getSetting('proxy_type')) {
                    $curl->setProxy($proxy, $proxyType);
                } else {
                    $curl->setProxy($proxy);
                }

                if ($user = $this->getSetting('proxy_user')) {
                    $curl->setProxyAuth($user, $this->getSetting('proxy_pass'));
                }
            }

            if ($scheme === 'HTTPS') {
                if ($this->getSetting('ssl_verify_peer', 'y') === 'n') {
                    $curl->disableSslPeerVerification();
                }
                if ($this->getSetting('ssl_verify_host', 'y') === 'n') {
                    $curl->disableSslHostVerification();
                }
            }
        }

        return $this->api;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultKeyColumnName()
    {
        return 'name';
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
        if (! class_exists('SoapClient')) {
            $form->addError($form->translate(
                'The PHP SOAP extension (php-soap) is not installed/enabled'
            ));

            return;
        }

        $form->addElement('select', 'object_type', array(
            'label' => $form->translate('Object Type'),
            'description' => $form->translate(
                'The managed vSphere object type this Import Source should fetch'
            ),
            'multiOptions' => $form->optionalEnum(array(
                'VirtualMachine' => 'Virtual Machines',
                'HostSystem'     => 'Host Systems',
            )),
            'required' => true,
        ));

        $form->addElement('select', 'scheme', array(
            'label' => $form->translate('Protocol'),
            'description' => $form->translate(
                'Whether to use encryption when talking to your vCenter'
            ),
            'multiOptions' => array(
                'HTTPS' => $form->translate('HTTPS (strongly recommended)'),
                'HTTP'  => $form->translate('HTTP (this is plaintext!)'),
            ),
            'class' => 'autosubmit',
            'value' => 'HTTPS',
            'required' => true,
        ));

        $ssl = ! ($form->getSentOrObjectSetting('scheme', 'HTTPS') === 'HTTP');

        if ($ssl) {
            static::addBoolean($form, 'ssl_verify_peer', array(
                'label'       => $form->translate('Verify Peer'),
                'description' => $form->translate(
                    'Whether we should check that our peer\'s certificate has'
                    . ' been signed by a trusted CA. This is strongly recommended.'
                )
            ), 'y');
            static::addBoolean($form, 'ssl_verify_host', array(
                'label'       => $form->translate('Verify Host'),
                'description' => $form->translate(
                    'Whether we should check that the certificate matches the'
                    . 'configured host'
                )
            ), 'y');
        }

        $form->addElement('text', 'host', array(
            'label' => $form->translate('vCenter (or ESX) host'),
            'description' => $form->translate(
                'It is strongly suggested to access the API through your vCenter.'
                . ' Usually this is just a fully qualified domain name (and should'
                . ' match it\'s certificate. Alternatively, an IP address is fine.'
                . ' Please use <host>:<port> in case you\'re not using default'
                . ' HTTP(s) ports'
            ),
            'required' => true,
        ));

        $form->addElement('text', 'username', array(
            'label' => $form->translate('Username'),
            'description' => $form->translate(
                'Will be used for SOAP authentication against your vCenter'
            ),
            'required' => true,
        ));

        $form->addElement('password', 'password', array(
            'label' => $form->translate('Password'),
            'required' => true,
        ));

        $form->addElement('select', 'proxy_type', array(
            'label' => $form->translate('Proxy'),
            'description' => $form->translate(
                'In case your vCenter is only reachable through a proxy, please'
                . ' choose it\'s protocol right here'
            ),
            'multiOptions' => $form->optionalEnum(array(
                'HTTP'   => $form->translate('HTTP proxy'),
                'SOCKS5' => $form->translate('SOCKS5 proxy'),
            )),
            'class' => 'autosubmit'
        ));

        $proxyType = $form->getSentOrObjectSetting('proxy_type');

        if ($proxyType) {
            $form->addElement('text', 'proxy', array(
                'label' => $form->translate('Proxy Address'),
                'description' => $form->translate(
                    'Hostname, IP or <host>:<port>'
                ),
                'required' => true,
            ));
            if ($proxyType === 'HTTP') {
                $form->addElement('text', 'proxy_user', array(
                    'label' => $form->translate('Proxy Username'),
                    'description' => $form->translate(
                        'In case your proxy requires authentication, please'
                        . ' configure this here'
                    ),
                ));

                $passRequired = strlen($form->getSentOrObjectSetting('proxy_user')) > 0;

                $form->addElement('password', 'proxy_pass', array(
                    'label' => $form->translate('Proxy Password'),
                    'required' => $passRequired
                ));
            }
        }
    }

    protected static function addBoolean($form, $key, $options, $default = null)
    {
        if ($default === null) {
            return $form->addElement('OptionalYesNo', $key, $options);
        } else {
            $form->addElement('YesNo', $key, $options);
            return $form->getElement($key)->setValue($default);
        }
    }

    protected static function optionalBoolean($form, $key, $label, $description)
    {
        return static::addBoolean($form, $key, array(
            'label'       => $label,
            'description' => $description
        ));
    }
}
