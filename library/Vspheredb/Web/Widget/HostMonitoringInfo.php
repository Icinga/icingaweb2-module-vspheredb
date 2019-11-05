<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Exception;
use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\MonitoringConnection;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class HostMonitoringInfo extends HtmlDocument
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    /** @var VCenter */
    protected $vCenter;

    /** @var mixed */
    protected $info;

    /**
     * HostVirtualizationInfoTable constructor.
     * @param HostSystem $host
     * @throws NotFoundError
     */
    public function __construct(HostSystem $host)
    {
        $this->host = $host;
        $this->vCenter = VCenter::load($host->get('vcenter_uuid'), $host->getConnection());
    }

    protected function assemble()
    {
        if ($info = $this->getInfo()) {
            $this->add($info);
        }
    }

    public function hasInfo()
    {
        return $this->getInfo() !== false;
    }

    protected function getInfo()
    {
        if ($this->info === null) {
            $this->info = $this->prepareInfo();
        }

        return $this->info;
    }

    /**
     * @return array|false
     */
    protected function prepareInfo()
    {
        $host = $this->host;
        $name = $host->get('host_name');
        $statusRenderer = new IcingaHostStatusRenderer();

        try {
            $monitoring = MonitoringConnection::eventuallyLoadForVCenter($this->vCenter);
            if ($monitoring && $monitoring->hasHost($name)) {
                $monitoringState = $monitoring->getHostState($name);
                return [
                    // TODO: is_acknowledged, is_in_downtime
                    $statusRenderer($monitoringState->current_state),
                    ' ',
                    $monitoringState->output,
                    ' ',
                    Link::create(
                        $this->translate('more'),
                        'monitoring/host/show',
                        ['host' => $name],
                        ['class' => 'icon-right-small']
                    )
                ];
            } else {
                return false;
            }
        } catch (Exception $e) {
            return [
                Html::tag('p', ['class' => 'error'], sprintf(
                    $this->translate('Unable to check monitoring state: %s'),
                    $e->getMessage()
                ))
            ];
        }
    }
}
