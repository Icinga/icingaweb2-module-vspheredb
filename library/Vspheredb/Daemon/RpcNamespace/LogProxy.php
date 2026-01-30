<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Psr\Log\LoggerInterface;

class LogProxy
{
    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var string */
    protected string $prefix = '';

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $prefix
     *
     * @return $this
     */
    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function logNotification(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $this->prefix . $message, $context);
    }
}
