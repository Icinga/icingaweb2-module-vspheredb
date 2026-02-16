<?php

namespace Icinga\Module\Vspheredb\Monitoring\Health;

use gipfl\ZfDb\Adapter\Adapter;
use gipfl\ZfDb\Select;
use gipfl\ZfDbStore\NotFoundError;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Ramsey\Uuid\Uuid;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Select;

class VCenterInfo
{
    // Hint: these should become readonly properties

    /** @var ?string */
    public ?string $uuid = null;

    /** @var ?int */
    public ?int $id = null;

    /** @var ?string */
    public ?string $name = null;

    /** @var ?string */
    public ?string $software = null;

    /** @var ?string */
    public ?string $softwareName = null;

    /** @var ?string */
    public ?string $softwareVersion = null;

    /**
     * @param object $row
     *
     * @return VCenterInfo
     */
    public static function fromDbRow(object $row): VCenterInfo
    {
        $self = new static();
        $self->id = (int) $row->id;
        $self->uuid = Uuid::fromBytes(DbUtil::binaryResult($row->uuid))->toString();
        $self->software = sprintf(
            '%s (%s)',
            preg_replace('/^VMware /', '', $row->software_name),
            $row->software_version
        );
        $self->softwareName = $row->software_name;
        $self->softwareVersion = $row->software_version;
        $self->name = $row->name;

        return $self;
    }

    /**
     * @param Zend_Db_Adapter_Abstract|Adapter $db
     *
     * @return Select|Zend_Db_Select
     */
    public static function prepareQuery(Zend_Db_Adapter_Abstract|Adapter $db): Select|Zend_Db_Select
    {
        $columns = [
            'uuid'             => 'vc.instance_uuid',
            'id'               => 'vc.id',
            'name'             => 'vc.name',
            'software_name'    => 'vc.api_name',
            'software_version' => 'vc.version'
        ];

        return $db->select()->from(['vc' => 'vcenter'], $columns)->order('name');
    }

    /**
     * @param Zend_Db_Adapter_Abstract|Adapter $db
     *
     * @return VCenterInfo[]
     */
    public static function fetchAll(Zend_Db_Adapter_Abstract|Adapter $db): array
    {
        $result = [];
        /** @var object{id: int} $row */
        foreach ($db->fetchAll(static::prepareQuery($db)) as $row) {
            $result[$row->id] = VCenterInfo::fromDbRow($row);
        }

        return $result;
    }

    /**
     * @param int                              $id
     * @param Zend_Db_Adapter_Abstract|Adapter $db
     *
     * @return VCenterInfo
     *
     * @throws NotFoundError
     */
    public static function fetchOne(int $id, Zend_Db_Adapter_Abstract|Adapter $db): VCenterInfo
    {
        if ($row = $db->fetchRow(static::prepareQuery($db)->where('id = ?', $id))) {
            return VCenterInfo::fromDbRow($row);
        }

        throw new NotFoundError("Could not load a vCenter with id=$id");
    }
}
