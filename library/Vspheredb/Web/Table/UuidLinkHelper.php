<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Util;
use ipl\Html\DeferredText;

trait UuidLinkHelper
{
    protected array $requiredUuids = [];

    protected ?array $fetchedUuids = null;

    /**
     * @param ?string $uuid
     *
     * @return DeferredText
     */
    public function linkToUuid(?string $uuid): DeferredText
    {
        $this->requiredUuids[$uuid ?? ''] = $uuid;
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
                Util::uuidParams($uuid),
                ['class' => [
                    'ManagedObject',
                    $this->getUuidProperty($uuid, 'object_type'),
                    $this->getUuidProperty($uuid, 'overall_status')
                ]]
            );
        });

        return $result->setEscaped();
    }

    protected function getUuidBaseUrl($uuid): ?string
    {
        return match ($this->getUuidProperty($uuid, 'object_type')) {
            'HostSystem'     => 'vspheredb/host',
            'VirtualMachine' => 'vspheredb/vm',
            'Datastore'      => 'vspheredb/datastore',
            default          => null
        };
    }

    /**
     * @param ?string $uuid
     * @param string $property
     *
     * @return string
     */
    protected function getUuidProperty(?string $uuid, string $property): string
    {
        if ($uuid === null) {
            return '[NULL]';
        }

        if ($this->fetchedUuids === null) {
            $this->fetchUuidObjectDetails();
        }

        if (array_key_exists($uuid, $this->fetchedUuids)) {
            return $this->fetchedUuids[$uuid]->$property;
        }

        return '[UNKNOWN]' . $uuid;
    }

    protected function fetchUuidObjectDetails(): void
    {
        if (! method_exists($this, 'db')) {
            $this->fetchedUuids = [];

            return;
        }

        /** @var Zend_Db_Adapter_Abstract $db */
        $db = $this->db();

        if (empty($this->requiredUuids)) {
            $this->fetchedUuids = [];

            return;
        }

        $objects = $db->fetchAll(
            $db->select()
                ->from('object', ['uuid', 'object_name', 'object_type', 'overall_status'])
                ->where('uuid IN (?)', array_values($this->requiredUuids))
        );

        /** @var object{uuid: string} $object */
        foreach ($objects as $object) {
            $this->fetchedUuids[$object->uuid] = $object;
        }
    }
}
