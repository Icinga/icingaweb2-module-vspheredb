<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;
use ipl\Html\BaseHtmlElement;

class MemoryUsage extends UsageBar
{
    /** @var int|float|null */
    protected int|float|null $usedHost;

    protected array $colors = [
        'used' => 'rgba(0, 149, 191, 0.75)',
        'host' => 'rgba(160, 200, 211, 0.75)'
    ];

    public function __construct(int|float|null $usedMb, int|float|null $capacityMb, int|float|null $usedHostMb = null)
    {
        parent::__construct($usedMb, $capacityMb);
        $this->usedHost = $usedHostMb;
        $this->formatter = Format::mBytes(...);
    }

    protected function getLabelUsed(): string
    {
        if ($this->usedHost === null) {
            return parent::getLabelUsed();
        }

        return sprintf(
            '%s: %s (%s: %s)',
            $this->translate('Active'),
            $this->format($this->used),
            $this->translate('Host'),
            $this->format($this->usedHost)
        );
    }

    protected function assembleBar(BaseHtmlElement $bar): void
    {
        parent::assembleBar($bar);
        if ($this->usedHost !== null && $this->capacity !== null) {
            $diffHostPercent = ($this->usedHost - $this->used) / $this->capacity;
            $availablePercent = ($this->capacity - $this->used) / $this->capacity;
            $diffHostPercent = min($diffHostPercent, $availablePercent);

            $title = sprintf(
                $this->translate('Host Memory: used %s of %s (%.2F%%)'),
                $this->format($this->usedHost),
                $this->format($this->capacity),
                $this->usedHost / $this->capacity * 100
            );
            $bar->add($this->makeSegment($diffHostPercent, $title, 'host'));
        }
    }
}
