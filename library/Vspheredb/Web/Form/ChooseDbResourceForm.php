<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Exception;
use gipfl\IcingaWeb2\Link;
use ipl\Html\Html;
use Icinga\Application\Config;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\Migrations;

class ChooseDbResourceForm extends BaseForm
{
    private $config;

    private $storeConfigLabel;

    private $createDbLabel;

    private $migrateDbLabel;

    /**
     * @throws \Zend_Form_Exception
     */
    public function setup()
    {
        $this->storeConfigLabel = $this->translate('Store configuration');

        $this->addResourceConfigElements();

        if (!$this->config()->get('db', 'resource')
            || ($this->config()->get('db', 'resource') !== $this->getResourceName())) {
            return;
        }
    }

    /**
     * @throws \Zend_Form_Exception
     */
    protected function onSetup()
    {
        if ($this->hasBeenSubmitted()) {
            // Do not hinder the form from being stored
            return;
        }
        if ($resourceName = $this->getResourceName()) {
            try {
                $resourceConfig = ResourceFactory::getResourceConfig($resourceName);
                if (! isset($resourceConfig->charset)
                    || $resourceConfig->charset !== 'utf8mb4'
                ) {
                    $this->getElement('resource')
                        ->addError('Please change the encoding for the database to utf8mb4');
                }

                $resource = $this->getResource();
                $db = $resource->getDbAdapter();
            } catch (Exception $e) {
                $this->getElement('resource')
                    ->addError('Resource failed: ' . $e->getMessage());

                return;
            }

            try {
                $db->fetchOne('SELECT 1');
            } catch (Exception $e) {
                $this->getElement('resource')
                    ->addError('Could not connect to database: ' . $e->getMessage());

                $this->addHint($this->translate(
                    'Please make sure that your database exists and your user has'
                    . ' been granted enough permissions'
                ));
            }
        }
    }

    /**
     * @throws \Zend_Form_Exception
     */
    protected function addResourceConfigElements()
    {
        $config = $this->config();
        $resources = $this->enumResources();

        if (!$this->getResourceName()) {
            $this->addHint($this->translate(
                'No database resource has been configured yet. Please choose a'
                . ' resource to complete your config'
            ), array('name' => 'HINT_no_resource'));
        }

        $this->addElement('select', 'resource', array(
            'required'      => true,
            'label'         => $this->translate('DB Resource'),
            'multiOptions'  => $this->optionalEnum($resources),
            'class'         => 'autosubmit',
            'value'         => $config->get('db', 'resource')
        ));

        if (empty($resources)) {
            $this->addHint(Html::sprintf(
                $this->translate('Please click %s to create new MySQL/MariaDB resource'),
                Link::create(
                    $this->translate('here'),
                    'config/resource',
                    null,
                    ['data-base-target' => '_main']
                )
            ));
        }

        $this->setSubmitLabel($this->storeConfigLabel);
    }

    /**
     * @return bool
     * @throws \Zend_Form_Exception
     */
    protected function storeResourceConfig()
    {
        $config = $this->config();
        $value = $this->getValue('resource');

        $config->setSection('db', ['resource' => $value]);

        try {
            $config->saveIni();
            $this->setSuccessMessage($this->translate('Configuration has been stored'));

            return true;
        } catch (Exception $e) {
            $this->getElement('resource')->addError(
                sprintf(
                    $this->translate(
                        'Unable to store the configuration to "%s". Please check'
                        . ' file permissions or manually store the content shown below'
                    ),
                    $config->getConfigFile()
                )
            );
            $this->addHtml(
                Html::tag('pre', null, $config),
                ['name' => 'HINT_config_store']
            );

            $this->getDisplayGroup('config')->addElements([
                $this->getElement('HINT_config_store')
            ]);
            $this->removeElement('HINT_ready');

            return false;
        }
    }

    /**
     * @throws \Zend_Form_Exception
     */
    public function onSuccess()
    {
        if ($this->getSubmitLabel() === $this->storeConfigLabel) {
            if ($this->storeResourceConfig()) {
                parent::onSuccess();
            } else {
                return;
            }
        }

        if ($this->getSubmitLabel() === $this->createDbLabel
            || $this->getSubmitLabel() === $this->migrateDbLabel) {
            $this->migrations()->applyPendingMigrations();
            parent::onSuccess();
        }

        parent::onSuccess();
    }

    protected function getResourceName()
    {
        if ($this->hasBeenSent()) {
            $resource = $this->getSentValue('resource');
            $resources = $this->enumResources();
            if (in_array($resource, $resources)) {
                return $resource;
            } else {
                return null;
            }
        } else {
            return $this->config()->get('db', 'resource');
        }
    }

    public function getDb()
    {
        return Db::fromResourceName($this->getResourceName());
    }

    protected function getResource()
    {
        return ResourceFactory::create($this->getResourceName());
    }

    /**
     * @return Migrations
     */
    protected function migrations()
    {
        return new Migrations($this->getDb());
    }

    public function setModuleConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    protected function config()
    {
        if ($this->config === null) {
            $this->config = Config::module('vspheredb');
        }

        return $this->config;
    }

    protected function enumResources()
    {
        // return [];
        $resources = [];
        $allowed = ['mysql'];

        foreach (ResourceFactory::getResourceConfigs() as $name => $resource) {
            if ($resource->get('type') === 'db' && in_array($resource->get('db'), $allowed)) {
                $resources[$name] = $name;
            }
        }

        ksort($resources);

        return $resources;
    }
}
