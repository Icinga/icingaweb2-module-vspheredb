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
use Zend_Db_Adapter_Abstract;
use Zend_Db_Select;

class RestrictionHelper
{
    /** @var Auth */
    protected Auth $auth;

    /** @var Zend_Db_Adapter_Abstract */
    protected Zend_Db_Adapter_Abstract $db;

    /** @var string[]|null */
    protected ?array $restrictedVCenterUuids = null;

    /**
     * @param Auth $auth
     * @param Db   $connection
     */
    public function __construct(Auth $auth, Db $connection)
    {
        $this->db = $connection->getDbAdapter();
        $this->auth = $auth;
        $this->loadRestrictedVCenterList();
    }

    /**
     * @param TableWithVCenterFilter $table
     *
     * @return void
     */
    public function restrictTable(TableWithVCenterFilter $table): void
    {
        if ($this->restrictedVCenterUuids) {
            $table->filterVCenterUuids($this->restrictedVCenterUuids);
        }
    }

    /**
     * @param Zend_Db_Select $query
     * @param string         $vCenterColumn
     *
     * @return void
     */
    public function filterQuery(Zend_Db_Select $query, string $vCenterColumn = 'vcenter_uuid'): void
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

    /**
     * @param string $uuid
     *
     * @return void
     *
     * @throws NotFoundError
     */
    public function assertAccessToVCenterUuidIsGranted(string $uuid): void
    {
        if (! $this->allowsVCenter($uuid)) {
            throw new NotFoundError('Not found');
        }
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    public function allowsVCenter(string $uuid): bool
    {
        if ($this->restrictedVCenterUuids === null) {
            return true;
        }
        if (strlen($uuid) !== 16) {
            $uuid = Uuid::fromString($uuid)->getBytes();
        }

        return in_array($uuid, $this->restrictedVCenterUuids);
    }

    /**
     * @return void
     */
    public function loadRestrictedVCenterList(): void
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
