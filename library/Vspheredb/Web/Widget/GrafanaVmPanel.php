<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

use function RingCentral\Psr7\build_query;

/**
 * This is just an experiment. Disabled, as it is pretty slow
 */
class GrafanaVmPanel extends HtmlDocument
{
    /** @var ManagedObject */
    protected ManagedObject $object;

    /** @var int[] */
    protected array $panels;

    /** @var ?string */
    protected ?string $interface;

    /** @var ?string */
    protected ?string $disk;

    /**
     * @param ManagedObject $object
     * @param array $panels
     * @param ?string $interface
     * @param ?string $disk
     */
    public function __construct(ManagedObject $object, array $panels, ?string $interface = 'All', ?string $disk = 'All')
    {
        $this->object = $object;
        $this->panels = $panels;
        $this->interface = $interface;
        $this->disk = $disk;
    }

    protected function assemble(): void
    {
        $width = floor(100 / count($this->panels));
        foreach ($this->panels as $id) {
            $this->add(Html::tag('iframe', [
                'src' => $this->panelUrl($id),
                'width' => $width . '%',
                'height' => 200,
                'frameborder' => 0
            ]));
        }
    }

    protected function panelUrl(int $panelId): string
    {
        // &from=1636834148559&to=1636838149732
        $orgId = 1;
        $dsName = 'vSphereDB';
        $dashboard = 'Icinga-vSphereDB-VirtualMachineDetails';
        $url = sprintf('https://grafana.example.com:3000/d-solo/%s/virtual-machine-details', $dashboard);

        $params = [
            'orgId' => $orgId,
            'var-ds_name' => $dsName,
            'var-vm' => $this->object->get('object_name'),
            'var-interface' => $this->interface,
            'var-virtual_disk' => $this->disk,
            'theme' => 'light',
            'panelId' => $panelId,
            'from' => 1636834044566,
            'to'   => 1636843374647
        ];

        return $url . '?' . build_query($params);
    }
}
