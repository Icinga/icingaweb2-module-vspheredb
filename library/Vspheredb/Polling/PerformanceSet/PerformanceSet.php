<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

interface PerformanceSet
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getObjectType(): string;

    /**
     * @return string
     */
    public function getCountersGroup(): string;

    /**
     * @return string[]
     */
    public function getCounters(): array;
}
