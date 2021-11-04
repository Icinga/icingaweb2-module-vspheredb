<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Log\Filter\LogLevelFilter;
use gipfl\Log\Logger;
use gipfl\Log\LogLevel;
use gipfl\Protocol\JsonRpc\Handler\RpcContext;
use gipfl\Protocol\JsonRpc\Handler\RpcUserInfo;
use gipfl\Protocol\JsonRpc\Request;

class RpcContextLogger extends RpcContext
{
    /** @var Logger */
    protected $logger;

    public function __construct(Logger $logger, RpcUserInfo $userInfo)
    {
        $this->logger = $logger;
        parent::__construct($userInfo);
    }

    public function getNamespace()
    {
        return 'logger';
    }

    public function isAccessible()
    {
        return true;
    }

    /**
     * @param Request $request
     */
    public function getLogLevelRequest(Request $request)
    {
        return LogLevel::mapNumericToName($this->getCurrentNumericLogLevel());
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

    /**
     * @rpcParam string $level
     * @param Request $request
     */
    public function setLogLevelRequest(Request $request)
    {
        $level = $request->getParam('level');
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
}
