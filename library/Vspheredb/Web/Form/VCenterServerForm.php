<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use ipl\Html\FormDecorator\DdDtDecorator;
use ipl\Html\FormElement\SubmitElement;

class VCenterServerForm extends Form
{
    protected $listUrl = 'vspheredb/vcenter/servers';

    /** @var VCenterServer */
    protected $object;

    public function assemble()
    {
        $this->setDefaultElementDecorator(new DdDtDecorator());
        if (! class_exists('SoapClient')) {
            $this->addMessage($this->translate(
                'The PHP SOAP extension (php-soap) is not installed/enabled'
            ));

            return;
        }

        $this->addElement('text', 'host', [
            'label' => $this->translate('vCenter (or ESX) host'),
            'description' => $this->translate(
                'It is strongly suggested to access the API through your vCenter.'
                . ' Usually this is just a fully qualified domain name (and should'
                . ' match it\'s certificate. Alternatively, an IP address is fine.'
                . ' Please use <host>:<port> in case you\'re not using default'
                . ' HTTP(s) ports'
            ),
            'class' => 'autofocus',
            'required' => true,
        ]);

        $this->addElement('select', 'scheme', [
            'label' => $this->translate('Protocol'),
            'description' => $this->translate(
                'Whether to use encryption when talking to your vCenter'
            ),
            'multiOptions' => [
                'https' => $this->translate('HTTPS (strongly recommended)'),
                'http'  => $this->translate('HTTP (this is plaintext!)'),
            ],
            'class' => 'autosubmit',
            'value' => 'https',
            'required' => true,
        ]);

        $ssl = $this->getValue('scheme', 'https') === 'http';

        if ($ssl) {
            $this->addElement('boolean', 'ssl_verify_peer', [
                'label'       => $this->translate('Verify Peer'),
                'description' => $this->translate(
                    'Whether we should check that our peer\'s certificate has'
                    . ' been signed by a trusted CA. This is strongly recommended.'
                ),
                'required'    => true,
                'value'       => 'y',
            ]);
            $this->addElement('boolean', 'ssl_verify_host', [
                'label'       => $this->translate('Verify Host'),
                'description' => $this->translate(
                    'Whether we should check that the certificate matches the'
                    . 'configured host'
                ),
                'value'       => 'y',
                'required'    => true,
            ]);
        }

        $this->addElement('text', 'username', [
            'label'       => $this->translate('Username'),
            'description' => $this->translate(
                'Will be used for SOAP authentication against your vCenter'
            ),
            'required'    => true,
        ]);

        if ($this->isNew()) {
            $this->addElement('password', 'password', [
                'label' => $this->translate('Password'),
                'required' => true,
            ]);
        } else {
            $this->addElement('password', 'password', [
                'label' => $this->translate('Password'),
                'placeholder' => $this->translate('(keep as stored)'),
            ]);
        }

        $this->addElement('select', 'proxy_type', [
            'label' => $this->translate('Proxy'),
            'description' => $this->translate(
                'In case your vCenter is only reachable through a proxy, please'
                . ' choose it\'s protocol right here'
            ),
            'multiOptions' => $this->optionalEnum([
                'HTTP'   => $this->translate('HTTP proxy'),
                'SOCKS5' => $this->translate('SOCKS5 proxy'),
            ]),
            'class' => 'autosubmit'
        ]);

        $proxyType = $this->getValue('proxy_type');

        if ($proxyType) {
            $this->addElement('text', 'proxy_address', [
                'label' => $this->translate('Proxy Address'),
                'description' => $this->translate(
                    'Hostname, IP or <host>:<port>'
                ),
                'required' => true,
            ]);
            if ($proxyType === 'HTTP') {
                $this->addElement('text', 'proxy_user', [
                    'label' => $this->translate('Proxy Username'),
                    'description' => $this->translate(
                        'In case your proxy requires authentication, please'
                        . ' configure this here'
                    ),
                ]);

                $passRequired = \strlen($this->getValue('proxy_user')) > 0;

                $this->addElement('password', 'proxy_pass', [
                    'label' => $this->translate('Proxy Password'),
                    'required' => $passRequired
                ]);
            }
        }

        $buttons = [];
        $buttons[] = new SubmitElement('submit', [
            'label' => $this->isNew() ? $this->translate('Create') : $this->translate('Store')
        ]);
        if (! $this->isNew()) {
            $buttons[] = new SubmitElement('btn_delete', [
                'label' => $this->translate('Delte')
            ]);
        }
        foreach ($buttons as $button) {
            $this->registerElement($button);
        }
        $this->add($buttons);
    }

    public function isNew()
    {
        return $this->object === null || ! $this->object->hasBeenLoadedFromDb();
    }

    public function getValues()
    {
        $values = parent::getValues();
        $values['enabled'] = 'y';
        if (! $this->isNew() && strlen($values['password']) === 0) {
            unset($values['password']);
        }

        return $values;
    }

    public function setObject(VCenterServer $object)
    {
        $this->object = $object;
        $this->populate($object->getProperties());

        return $this;
    }

    public function getObject()
    {
        return $this->object;
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
