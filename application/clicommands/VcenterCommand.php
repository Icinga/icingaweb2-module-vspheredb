<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Db;
use React\EventLoop\Loop;
use Throwable;

class VcenterCommand extends Command
{
    /**
     * Deprecated
     */
    public function initializeAction()
    {
        $this->fail("Command has been deprecated, please check our documentation");
    }

    /**
     * List orphaned vCenter IDs
     *
     * USAGE
     *
     * icingacli vspheredb orphaned
     */
    public function orphanedAction(): void
    {
        $db = Db::newConfiguredInstance();
        $adapter = $db->getDbAdapter();
        $vcenterIds = $adapter->fetchCol(
            $adapter->select()->distinct()->from('vcenter_server', ['vcenter_id'])->where('vcenter_id IS NOT NULL')
        );
        $q = $adapter->select()->from('vcenter', ['instance_uuid', 'id']);
        if (! empty($vcenterIds)) {
            $q->where('id NOT IN (?)', $vcenterIds);
        }
        $orphanedInstanceUuids = $adapter->fetchPairs($q);
        if (empty($orphanedInstanceUuids)) {
            echo "No orphaned vCenter IDs were found\n";
            exit(0);
        }
        echo sprintf(
            "Found %d orphaned vCenter IDs:\n%s\n",
            count($orphanedInstanceUuids),
            implode("\n", $orphanedInstanceUuids)
//            implode("\n", array_map('bin2hex', array_keys($orphanedInstanceUuids)))
        );
    }

    /**
     * Cleanup orphaned vCenter data from the database
     *
     * USAGE
     *
     * icingacli vspheredb cleanup --vCenter <ID>
     */
    public function cleanupAction(): void
    {
        $db = Db::newConfiguredInstance();
        $vCenterId = (int) $this->requiredParam('vCenter');
        $cleanup = new Db\VCenterCleanup($db, $vCenterId);
        $cleanup->run()->then(function () use ($db, $vCenterId) {
            $adapter = $db->getDbAdapter();
            try {
                $adapter->delete('vcenter_server', $adapter->quoteInto('vcenter_id = ?', $vCenterId));
            } catch (Throwable $e) {
                $this->fail($e->getMessage());
            }
            echo "Successfully cleaned up vCenter with ID $vCenterId\n";
        }, function (Throwable $e) {
            $this->fail($e->getMessage());
        });
        Loop::run();
    }
}
