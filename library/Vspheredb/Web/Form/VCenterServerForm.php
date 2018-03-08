<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Icinga\Module\Director\Web\Form\DirectorObjectForm;
use Icinga\Module\Vspheredb\Db;

class VCenterServerForm extends DirectorObjectForm
{
    protected $className = 'Icinga\\Module\\Vspheredb\\DbObject\\VCenterServer';

    public function setup()
    {
        if (! class_exists('SoapClient')) {
            $this->addError($this->translate(
                'The PHP SOAP extension (php-soap) is not installed/enabled'
            ));

            return;
        }

        $this->addElement('select', 'scheme', array(
            'label' => $this->translate('Protocol'),
            'description' => $this->translate(
                'Whether to use encryption when talking to your vCenter'
            ),
            'multiOptions' => array(
                'HTTPS' => $this->translate('HTTPS (strongly recommended)'),
                'HTTP'  => $this->translate('HTTP (this is plaintext!)'),
            ),
            'class' => 'autosubmit',
            'value' => 'HTTPS',
            'required' => true,
        ));

        // TODO: sent or object Value
        $ssl = ! ($this->getSentOrObjectValue('scheme', 'HTTPS') === 'HTTP');

        if ($ssl) {
            $this->addBoolean('ssl_verify_peer', array(
                'label'       => $this->translate('Verify Peer'),
                'description' => $this->translate(
                    'Whether we should check that our peer\'s certificate has'
                    . ' been signed by a trusted CA. This is strongly recommended.'
                )
            ), 'y');
            $this->addBoolean('ssl_verify_host', array(
                'label'       => $this->translate('Verify Host'),
                'description' => $this->translate(
                    'Whether we should check that the certificate matches the'
                    . 'configured host'
                )
            ), 'y');
        }

        $this->addElement('text', 'host', array(
            'label' => $this->translate('vCenter (or ESX) host'),
            'description' => $this->translate(
                'It is strongly suggested to access the API through your vCenter.'
                . ' Usually this is just a fully qualified domain name (and should'
                . ' match it\'s certificate. Alternatively, an IP address is fine.'
                . ' Please use <host>:<port> in case you\'re not using default'
                . ' HTTP(s) ports'
            ),
            'required' => true,
        ));

        $this->addElement('text', 'username', array(
            'label' => $this->translate('Username'),
            'description' => $this->translate(
                'Will be used for SOAP authentication against your vCenter'
            ),
            'required' => true,
        ));

        if ($this->isNew()) {
            $this->addElement('password', 'password', array(
                'label' => $this->translate('Password'),
                'required' => true,
            ));
        } else {
            $this->addElement('password', 'password', array(
                'label' => $this->translate('Password'),
                'placeholder' => $this->translate('(keep as stored)'),
            ));
        }

        $this->addElement('select', 'proxy_type', array(
            'label' => $this->translate('Proxy'),
            'description' => $this->translate(
                'In case your vCenter is only reachable through a proxy, please'
                . ' choose it\'s protocol right here'
            ),
            'multiOptions' => $this->optionalEnum(array(
                'HTTP'   => $this->translate('HTTP proxy'),
                'SOCKS5' => $this->translate('SOCKS5 proxy'),
            )),
            'class' => 'autosubmit'
        ));

        $proxyType = $this->getSentOrObjectValue('proxy_type');

        if ($proxyType) {
            $this->addElement('text', 'proxy_address', array(
                'label' => $this->translate('Proxy Address'),
                'description' => $this->translate(
                    'Hostname, IP or <host>:<port>'
                ),
                'required' => true,
            ));
            if ($proxyType === 'HTTP') {
                $this->addElement('text', 'proxy_user', array(
                    'label' => $this->translate('Proxy Username'),
                    'description' => $this->translate(
                        'In case your proxy requires authentication, please'
                        . ' configure this here'
                    ),
                ));

                $passRequired = strlen($this->getSentOrObjectValue('proxy_user')) > 0;

                $this->addElement('password', 'proxy_pass', array(
                    'label' => $this->translate('Proxy Password'),
                    'required' => $passRequired
                ));
            }
        }
    }

    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        if (! $this->isNew() && strlen($values['password']) === 0) {
            unset($values['password']);
        }

        return $values;
    }

    public function onSuccess()
    {
        parent::onSuccess(); // TODO: Change the autogenerated stub
    }

    public function setVsphereDb(Db $db)
    {
        if ($this->object !== null) {
            $this->object->setConnection($db);
        }

        $this->db = $db;

        return $this;
    }
}
