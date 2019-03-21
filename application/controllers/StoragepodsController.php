<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\Table\Objects\StoragePodTable;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Module\Vspheredb\Web\Widget\Summaries;

class StoragepodsController extends ObjectsController
{
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Storage Pods'));
        $this->setAutorefreshInterval(15);
        $table = new StoragePodTable($this->db());
        (new AdditionalTableActions($table, Auth::getInstance(), $this->url()))
            ->appendTo($this->actions());
        $this->showTable($table, 'vspheredb/storagepods', $this->translate('Storage Pods'));
        $table->handleSortUrl($this->url());
        $summaries = new Summaries($table, $this->db(), $this->url());
        $this->content()->prepend($summaries);
    }
}
