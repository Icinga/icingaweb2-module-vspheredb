<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Vm;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Addon\BackupTool;
use Icinga\Module\Vspheredb\Addon\IbmSpectrumProtect;
use Icinga\Module\Vspheredb\Addon\NetBackup;
use Icinga\Module\Vspheredb\Addon\VRangerBackup;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class BackupToolInfo extends HtmlDocument
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
    }

    protected function assemble()
    {
        $vm = $this->vm;
        $this->add(new SubTitle($this->translate('Backup-Tools'), 'download'));
        $tools = $this->getBackupTools();
        $seenBackupTools = 0;
        foreach ($tools as $tool) {
            if ($tool->wants($vm)) {
                $seenBackupTools++;
                $tool->handle($vm);
                $this->add(Html::tag('h3', null, $tool->getName()));
                $this->add($tool->getInfoRenderer());
            }
        }
        if ($seenBackupTools === 0) {
            $this->add(Html::tag(
                'p',
                null,
                $this->translate('No known backup tool has been used for this VM')
            ));
        }
    }

    /**
     * TODO: Use a hook once the API stabilized
     * @return BackupTool[]
     */
    protected function getBackupTools()
    {
        return [
            new IbmSpectrumProtect(),
            new NetBackup(),
            new VRangerBackup(),
        ];
    }
}
