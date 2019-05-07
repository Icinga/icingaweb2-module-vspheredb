<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Exception;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\Migrations;
use Icinga\Module\Vspheredb\Web\Form\ChooseDbResourceForm;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Form\ApplyMigrationsForm;
use Icinga\Module\Vspheredb\Web\Controller;
use ipl\Html\Html;

class ConfigurationController extends Controller
{
    public function indexAction()
    {
        $this->addTitle($this->translate('Main Configuration'));

        if (! $this->hasPermission('vspheredb/admin')) {
            $this->addSingleTab($this->translate('Configuration'));
            $this->content()->add(Html::tag('p', [
                'class' => 'error',
            ], $this->translate('"vspheredb/admin" permissions are required to continue')));

            return;
        }
        $form = ChooseDbResourceForm::load()->handleRequest();
        $this->content()->add(Html::tag('div', [
            'class' => 'icinga-module module-director'
        ], $form));
        if ($form->hasErrors()) {
            $this->addSingleTab($this->translate('Configuration'));

            return;
        }

        if ($this->Config()->get('db', 'resource')) {
            $db = $this->db();
            $this->tabs(new MainTabs($this->Auth(), $db))
                ->activate('configuration');

            if ($db === null) {
                return;
            }

            try {
                $this->showMigrations($db);
            } catch (Exception $e) {
                $this->addSingleTab($this->translate('Configuration'));

                $this->content()->add(Html::tag('p', [
                    'class' => 'error',
                ], $e->getMessage()));
            }
        } else {
            $this->tabs(new MainTabs($this->Auth()))->activate('configuration');
        }
    }

    protected function showMigrations(Db $db)
    {
        $migrations = new Migrations($db);

        if ($migrations->hasSchema()) {
            if ($migrations->hasPendingMigrations()) {
                $this->content()->add(Html::tag('p', [
                    'class' => 'warning'
                ], $this->translate(
                    'There are pending Database Schema Migrations. Please apply'
                    . ' them now!'
                )));
                $this->content()->add(
                    ApplyMigrationsForm::load()
                        ->setMigrations($migrations)
                        ->handleRequest()
                );
            }
        } else {
            if ($migrations->hasModuleRelatedTable()) {
                $this->content()->add(Html::tag('p', [
                    'class' => 'error'
                ], $this->translate(
                    'The chosen Database resource contains related tables,'
                    . ' but the schema is not complete. In case you tried'
                    . ' a pre-release version of this module please drop'
                    . ' this database and start with a fresh new one.'
                )));

                return;
            } elseif ($migrations->hasAnyTable()) {
                $this->content()->add(Html::tag('p', [
                    'class' => 'warning'
                ], $this->translate(
                    'The chosen Database resource already contains tables. You'
                    . ' might want to continue with this DB resource, but we'
                    . ' strongly suggest to use an empty dedicated DB for this'
                    . ' module.'
                )));
            }
            $this->content()->add(
                ApplyMigrationsForm::load()
                    ->setMigrations($migrations)
                    ->handleRequest()
            );
        }
    }
}
