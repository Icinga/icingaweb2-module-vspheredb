<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\BaseHtmlElement;
use Icinga\Module\Vspheredb\Format;

class MemoryUsage extends UsageBar
{
    /** @var int */
    protected $usedHost;

    protected $colors = [
        'used' => 'rgba(0, 149, 191, 0.75)',
        'host' => 'rgba(160, 200, 211, 0.75)',
    ];

    protected $formatter = [Format::class, 'mBytes'];

    public function __construct($usedMb, $capacityMb, $usedHostMb = null)
    {
        parent::__construct($usedMb, $capacityMb);
        $this->usedHost = $usedHostMb;
    }

    protected function getLabelUsed()
    {
        if ($this->usedHost === null) {
            return parent::getLabelUsed();
        } else {
            return sprintf(
                '%s: %s (%s: %s)',
                $this->translate('Active'),
                $this->format($this->used),
                $this->translate('Host'),
                $this->format($this->usedHost)
            );
        }
    }

    protected function assembleBar(BaseHtmlElement $bar)
    {
        parent::assembleBar($bar);
        if ($this->usedHost !== null && $this->capacity !== null) {
            $diffHostPercent = ($this->usedHost - $this->used) / $this->capacity;
            $availablePercent = ($this->capacity - $this->used) / $this->capacity;
            $diffHostPercent = min($diffHostPercent, $availablePercent);

            $title = sprintf(
                $this->translate('Host Memory: used %s of %s'),
                $this->format($this->usedHost),
                $this->format($this->capacity)
            );
            $bar->add($this->makeSegment($diffHostPercent, $title, 'host'));
        }
    }
}
