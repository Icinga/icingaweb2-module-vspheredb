<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Icinga\Module\Director\Web\Form\QuickForm;

class VCenterServerForm extends QuickForm
{
    public function setup()
    {
        if (! class_exists('SoapClient')) {
            $this->addError($this->translate(
                'The PHP SOAP extension (php-soap) is not installed/enabled'
            ));

            return;
        }

        $this->addElement('select', 'object_type', array(
            'label' => $this->translate('Object Type'),
            'description' => $this->translate(
                'The managed vSphere object type this Import Source should fetch'
            ),
            'multiOptions' => $this->optionalEnum(array(
                'VirtualMachine' => 'Virtual Machines',
                'HostSystem'     => 'Host Systems',
            )),
            'required' => true,
        ));

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

        $ssl = ! ($this->getSentOrObjectSetting('scheme', 'HTTPS') === 'HTTP');

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

        $this->addElement('password', 'password', array(
            'label' => $this->translate('Password'),
            'required' => true,
        ));

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

        $proxyType = $this->getSentOrObjectSetting('proxy_type');

        if ($proxyType) {
            $this->addElement('text', 'proxy', array(
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

                $passRequired = strlen($this->getSentOrObjectSetting('proxy_user')) > 0;

                $this->addElement('password', 'proxy_pass', array(
                    'label' => $this->translate('Proxy Password'),
                    'required' => $passRequired
                ));
            }
        }
    }

    // Are these needed?
    protected function addBoolean($key, $options, $default = null)
    {
        if ($default === null) {
            return $this->addElement('OptionalYesNo', $key, $options);
        } else {
            $this->addElement('YesNo', $key, $options);
            return $this->getElement($key)->setValue($default);
        }
    }

    protected function optionalBoolean($key, $label, $description)
    {
        return $this->addBoolean($key, [
            'label'       => $label,
            'description' => $description
        ]);
    }
}
