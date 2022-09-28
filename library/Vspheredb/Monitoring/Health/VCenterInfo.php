<?php

namespace Icinga\Module\Vspheredb\Monitoring\Health;

use gipfl\ZfDb\Adapter\Adapter;
use gipfl\ZfDb\Select;
use gipfl\ZfDbStore\NotFoundError;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class VCenterInfo
{
    // Hint: these should become readonly properties

    /** @var UuidInterface */
    public $uuid;
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $software;
    /** @var string */
    public $softwareName;
    /** @var string */
    public $softwareVersion;

    public static function fromDbRow($row): VCenterInfo
    {
        $self = new static;
        $self->id = (int) $row->id;
        $self->uuid = Uuid::fromBytes(DbUtil::binaryResult($row->uuid))->toString();
        $self->software = \sprintf(
            '%s (%s)',
            \preg_replace('/^VMware /', '', $row->software_name),
            $row->software_version
        );
        $self->softwareName = $row->software_name;
        $self->softwareVersion = $row->software_version;
        $self->name = $row->name;

        return $self;
    }

    /**
     * @var Adapter|\Zend_Db_Adapter_Abstract $db
     * @return Select|\Zend_Db_Select
     */
    public static function prepareQuery($db)
    {
        $columns = [
            'uuid'             => 'vc.instance_uuid',
            'id'               => 'vc.id',
            'name'             => 'vc.name',
            'software_name'    => 'vc.api_name',
            'software_version' => 'vc.version',
        ];

        return $db->select()->from(['vc' => 'vcenter'], $columns)->order('name');
    }

    /**
     * @var Adapter|\Zend_Db_Adapter_Abstract $db
     * @return VCenterInfo[]
     */
    public static function fetchAll($db): array
    {
        $result = [];
        foreach ($db->fetchAll(static::prepareQuery($db)) as $row) {
            $result[$row->id] = VCenterInfo::fromDbRow($row);
        }

        return $result;
    }

    /**
     * @param int $id
     * @return VCenterInfo
     * @throws NotFoundError
     * @var Adapter|\Zend_Db_Adapter_Abstract $db
     */
    public static function fetchOne(int $id, $db): VCenterInfo
    {
        if ($row = $db->fetchRow(static::prepareQuery($db)->where('id = ?', $id))) {
            return VCenterInfo::fromDbRow($row);
        }

        throw new NotFoundError("Could not load a vCenter with id=$id");
    }
}
