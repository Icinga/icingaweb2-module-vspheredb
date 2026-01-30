<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Log\LogWriterWithContext;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Util;
use Psr\Log\LoggerAwareTrait;
use Ramsey\Uuid\Uuid;
use SplStack;
use Zend_Db_Adapter_Abstract;

class DbLogger implements LogWriterWithContext, EventEmitterInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    public const ERROR_PREFIX = 'DbLogger failed: ';

    public const ERROR_PREFIX_LENGTH = 17;

    /** @var string */
    protected string $instance;

    /** @var ?Zend_Db_Adapter_Abstract */
    protected ?Zend_Db_Adapter_Abstract $db = null;

    /** @var SplStack */
    protected SplStack $queue;

    /** @var string */
    protected string $fqdn;

    /** @var int */
    protected int $pid;

    /** @var ?int */
    protected ?int $lastTs = null;

    /**
     * @param string $instanceUuid
     * @param string $fqdn
     * @param int    $pid
     */
    public function __construct(string $instanceUuid, string $fqdn, int $pid)
    {
        $this->instance = $instanceUuid;
        $this->fqdn = $fqdn;
        $this->pid = $pid;
        $this->queue = new SplStack();
    }

    /**
     * @param Db|null $db
     *
     * @return void
     */
    public function setDb(?Db $db = null): void
    {
        if ($db === null) {
            $this->db = null;
        } else {
            $this->db = $db->getDbAdapter();
            $this->flushQueue();
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function write($level, $message, $context = []): void
    {
        if (substr($message, 0, self::ERROR_PREFIX_LENGTH) === self::ERROR_PREFIX) {
            return;
        }
        $timestamp = Util::currentTimestamp();
        while ($timestamp <= $this->lastTs) {
            $timestamp++;
        }
        $this->lastTs = $timestamp;

        if ($this->db === null) {
            $this->queue->push([
                'timestamp' => $timestamp,
                'level'     => $level,
                'message'   => $message,
                'context'   => $context,
            ]);
            if ($this->queue->count() > 100) {
                $this->queue->pop();
            }

            return;
        }

        $this->reallyWrite($timestamp, $level, $message, $context);
    }

    /**
     * @param int    $timestamp
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function reallyWrite(int $timestamp, string $level, string $message, array $context = []): void
    {
        $params = [
            'instance_uuid' => $this->instance,
            'ts_create'     => $timestamp,
            'pid'           => $this->pid,
            'fqdn'         => $this->fqdn,
            'level'         => $level,
            'message'       => $message,
        ];
        if (isset($context['pid'])) {
            $params['pid'] = $context['pid'];
        }
        if (isset($context['fqdn'])) {
            $params['fqdn'] = $context['fqdn'];
        }
        if (isset($context['vcenter_uuid'])) {
            $uuid = $context['vcenter_uuid'];
            if (strlen($uuid) === 16) {
                $params['vcenter_uuid'] = $context['vcenter_uuid'];
            } else {
                $params['vcenter_uuid'] = Uuid::fromString($context['vcenter_uuid'])->getBytes();
            }
        }

        try {
            $this->db->insert('vspheredb_daemonlog', $params);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->debug(self::ERROR_PREFIX . $e->getMessage());
            }
            $this->emit('error', [$e]);
        }
    }

    /**
     * @return void
     */
    protected function flushQueue(): void
    {
        while (! $this->queue->isEmpty()) {
            $log = $this->queue->pop();
            $this->reallyWrite($log['timestamp'], $log['level'], $log['message'], $log['context']);
        }
    }
}
