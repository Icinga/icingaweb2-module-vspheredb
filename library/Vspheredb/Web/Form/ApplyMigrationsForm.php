<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Exception;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\Db\Migrations;
use Icinga\Web\Notification;
use ipl\Html\Html;

class ApplyMigrationsForm extends Form
{
    /** @var  Migrations */
    protected $migrations;

    public function __construct(Migrations $migrations)
    {
        $this->migrations = $migrations;
    }

    public function assemble()
    {
        $this->prepareWebForm();
        if ($this->migrations->hasSchema()) {
            $count = $this->migrations->countPendingMigrations();
            if ($count === 1) {
                $label = $this->translate('Apply a pending schema migration');
            } else {
                $label = sprintf(
                    $this->translate('Apply %d pending schema migrations'),
                    $count
                );
            }
        } else {
            $this->add(Hint::warning($this->translate('There is no vSphereDB schema in this database')));
            $label = $this->translate('Create schema');
        }
        $this->addElement('submit', 'submit', [
            'label' => $label
        ]);
    }

    public function onSuccess()
    {
        $this->migrations->applyPendingMigrations();
        Notification::success($this->translate(
            'Pending database schema migrations have successfully been applied'
        ));
    }
}
