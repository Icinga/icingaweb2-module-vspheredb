<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Psr\Log\LoggerInterface;

class LogProxy
{
    /** @var LoggerInterface */
    protected $logger;

    protected $prefix;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function logNotification($level, $message, $context = [])
    {
        $this->logger->log($level, $this->prefix . $message, $context);
    }
}
