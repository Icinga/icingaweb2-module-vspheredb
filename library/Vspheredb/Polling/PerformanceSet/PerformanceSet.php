<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

interface PerformanceSet
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getObjectType();

    /**
     * @return string
     */
    public function getCountersGroup();

    /**
     * @return string[]
     */
    public function getCounters();
}
