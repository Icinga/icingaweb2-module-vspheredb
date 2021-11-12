<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Log\Filter\LogLevelFilter;
use gipfl\Log\Logger;
use gipfl\Log\LogLevel;

class RpcNamespaceLogger
{
    /** @var Logger */
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getLogLevelRequest()
    {
        return LogLevel::mapNumericToName($this->getCurrentNumericLogLevel());
    }

    /**
     * @param string $level
     * @return bool
     */
    public function setLogLevelRequest($level)
    {
        $formerLevel = $this->getCurrentNumericLogLevel();
        $numericLevel = LogLevel::mapNameToNumeric($level);
        if ($formerLevel === $numericLevel) {
            return false;
        }
        $newFilter = new LogLevelFilter($level);
        if (! $newFilter->wants('notice', '')) {
            $this->logger->notice("Will change the log level to '$level'");
        }
        $this->logger->addFilter($newFilter);
        $remove = [];
        foreach ($this->logger->getFilters() as $filter) {
            if ($filter instanceof LogLevelFilter && $filter !== $newFilter) {
                $remove[] = $filter;
            }
        }
        foreach ($remove as $filter) {
            $this->logger->removeFilter($filter);
        }
        if ($newFilter->wants('notice', '')) {
            $this->logger->notice("Changed log level to '$level'");
        }

        return true;
    }

    protected function getCurrentNumericLogLevel()
    {
        $level = LogLevel::LEVEL_DEBUG;
        foreach ($this->logger->getFilters() as $filter) {
            if ($filter instanceof LogLevelFilter) {
                $filterLevel = LogLevel::mapNameToNumeric($filter->getLevel());
                if ($filterLevel < $level) {
                    $level = $filterLevel;
                }
            }
        }

        return $level;
    }
}
