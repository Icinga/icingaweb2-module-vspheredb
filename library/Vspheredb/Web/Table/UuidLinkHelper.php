<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use ipl\Html\DeferredText;

trait UuidLinkHelper
{
    protected $requiredUuids = [];

    protected $fetchedUuids;

    /**
     * @param $uuid
     * @return DeferredText
     */
    public function linkToUuid($uuid)
    {
        $this->requiredUuids[$uuid] = $uuid;
        $result = new DeferredText(function () use ($uuid) {
            if ($uuid === null) {
                return null;
            }

            $url = $this->getUuidBaseUrl($uuid);
            if ($url === null) {
                return $this->getUuidProperty($uuid, 'object_name');
            }

            return Link::create(
                $this->getUuidProperty($uuid, 'object_name'),
                $url,
                ['uuid' => bin2hex($uuid)],
                ['class' => [
                    'ManagedObject',
                    $this->getUuidProperty($uuid, 'object_type'),
                    $this->getUuidProperty($uuid, 'overall_status')
                ]]
            );
        });

        return $result->setEscaped(true);
    }

    protected function getUuidBaseUrl($uuid)
    {
        $type = $this->getUuidProperty($uuid, 'object_type');

        switch ($type) {
            case 'HostSystem':
                return 'vspheredb/host';
            case 'VirtualMachine':
                return 'vspheredb/vm';
            case 'Datastore':
                return 'vspheredb/datastore';
            default:
                return null;
        }
    }

    protected function getUuidProperty($uuid, $property)
    {
        if ($uuid === null) {
            return '[NULL]';
        }

        if ($this->fetchedUuids === null) {
            $this->fetchUuidObjectDetails();
        }

        if (array_key_exists($uuid, $this->fetchedUuids)) {
            return $this->fetchedUuids[$uuid]->$property;
        } else {
            return '[UNKNOWN]' . $uuid;
        }
    }

    protected function fetchUuidObjectDetails()
    {
        if (method_exists($this, 'db')) {
            /** @var \Zend_Db_Adapter_Abstract $db */
            $db = $this->db();
        } else {
            $this->fetchedUuids = [];

            return;
        }
        if (empty($this->requiredUuids)) {
            $this->fetchedUuids = [];

            return;
        }

        $objects = $db->fetchAll(
            $db->select()
                ->from('object', ['uuid', 'object_name', 'object_type', 'overall_status'])
                ->where('uuid IN (?)', array_values($this->requiredUuids))
        );

        foreach ($objects as $object) {
            $this->fetchedUuids[$object->uuid] = $object;
        }
    }
}
