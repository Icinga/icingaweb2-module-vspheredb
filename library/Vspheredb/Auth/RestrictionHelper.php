<?php

namespace Icinga\Module\Vspheredb\Auth;

use gipfl\IcingaWeb2\Zf1\Db\FilterRenderer;
use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\Web\Table\TableWithVCenterFilter;
use Ramsey\Uuid\Uuid;

class RestrictionHelper
{
    /** @var Auth */
    protected $auth;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var string[]|null */
    protected $restrictedVCenterUuids = null;

    public function __construct(Auth $auth, Db $connection)
    {
        $this->db = $connection->getDbAdapter();
        $this->auth = $auth;
        $this->loadRestrictedVCenterList();
    }

    public function restrictTable(TableWithVCenterFilter $table)
    {
        if ($this->restrictedVCenterUuids) {
            $table->filterVCenterUuids($this->restrictedVCenterUuids);
        }
    }

    public function filterQuery($query, $vCenterColumn = 'vcenter_uuid')
    {
        $uuids = $this->restrictedVCenterUuids;
        if ($uuids === null) {
            return;
        }
        if (count($uuids) === 1) {
            $query->where("$vCenterColumn = ?", DbUtil::quoteBinaryCompat(array_shift($uuids), $this->db));
        } else {
            $query->where("$vCenterColumn IN (?)", DbUtil::quoteBinaryCompat($uuids, $this->db));
        }
    }

    public function assertAccessToVCenterUuidIsGranted($uuid)
    {
        if (! $this->allowsVCenter($uuid)) {
            throw new NotFoundError('Not found');
        }
    }

    public function allowsVCenter($uuid): bool
    {
        if ($this->restrictedVCenterUuids === null) {
            return true;
        }
        if (strlen($uuid) !== 16) {
            $uuid = Uuid::fromString($uuid)->getBytes();
        }

        return in_array($uuid, $this->restrictedVCenterUuids);
    }

    public function loadRestrictedVCenterList()
    {
        $uuids = null;
        $restrictions = $this->auth->getRestrictions('vspheredb/vcenters');
        if (!empty($restrictions)) {
            $uuids = [];
            foreach ($restrictions as $restriction) {
                $parts = preg_split('/\s*,\s*/', $restriction, -1, PREG_SPLIT_NO_EMPTY);
                $filter = implode('|', array_map(function ($part) {
                    return 'name=' . $part;
                }, $parts));
                $db = $this->db;
                $filer = Filter::fromQueryString($filter);
                $query = $db->select()->from('vcenter', 'instance_uuid');
                FilterRenderer::applyToQuery($filer, $query);
                $uuids = array_merge($uuids, $db->fetchCol($query));
            }
        }

        $this->restrictedVCenterUuids = $uuids;
    }
}
