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

class DbLogger implements LogWriterWithContext, EventEmitterInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    public const ERROR_PREFIX = 'DbLogger failed: ';

    public const ERROR_PREFIX_LENGTH = 17;

    protected $instance;

    protected $db;

    protected $queue;

    protected $fqdn;

    protected $pid;

    protected $lastTs = null;

    public function __construct($instanceUuid, $fqdn, $pid)
    {
        $this->instance = $instanceUuid;
        $this->fqdn = $fqdn;
        $this->pid = $pid;
        $this->queue = new SplStack();
    }

    public function setDb(?Db $db = null)
    {
        if ($db === null) {
            $this->db = null;
        } else {
            $this->db = $db->getDbAdapter();
            $this->flushQueue();
        }
    }

    public function write($level, $message, $context = [])
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

    protected function reallyWrite($timestamp, $level, $message, $context = [])
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

    protected function flushQueue()
    {
        while (! $this->queue->isEmpty()) {
            $log = $this->queue->pop();
            $this->reallyWrite($log['timestamp'], $log['level'], $log['message'], $log['context']);
        }
    }
}
