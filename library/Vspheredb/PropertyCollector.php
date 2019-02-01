<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\AuthenticationException;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\MappedClass\ObjectContent;
use Icinga\Module\Vspheredb\MappedClass\RetrieveResult;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;

class PropertyCollector
{
    /** @var Api */
    protected $api;

    protected $obj;

    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->propertyCollector;
    }

    public function collectProperties($specSet)
    {
        $specSet = array(
            '_this'   => $this->obj,
            'specSet' => $specSet
        );

        try {
            return $this->makeNiceResult(
                $this->api->soapCall('RetrieveProperties', $specSet)
            );
        } catch (AuthenticationException $e) {
            $this->api->logout();
            $this->api->login();
            return $this->makeNiceResult(
                $this->api->soapCall('RetrieveProperties', $specSet)
            );
        }
    }

    /**
     * @param $specSet
     * @param null $options
     * @return RetrieveResult
     * @throws AuthenticationException
     */
    public function collectPropertiesEx($specSet, $options = null)
    {
        $specSet = array(
            '_this'   => $this->obj,
            'specSet' => $specSet,
            'options' => $options
        );

        return $this->api->soapCall('RetrievePropertiesEx', $specSet)->returnval;
    }

    public function collectObjectProperties(PropertySet $propSet, SelectSet $selectSet)
    {
        $result = $this->collectProperties(
            $this->api->makePropertyFilterSpec($propSet, $selectSet)
        );

        return $result;
    }

    // Might be obsolete:
    protected function flattenReference($ref)
    {
        $res = [];
        foreach ($ref as $r) {
            $res[] = $r->_;
        }

        return $res;
    }

    /**
     * TOOD: I'd love to replace this
     *
     * @param $result
     * @return array
     * @throws AuthenticationException
     * @throws IcingaException
     */
    protected function makeNiceResult($result)
    {
        if (! property_exists($result, 'returnval')) {
            throw new IcingaException('Got no returnval');
        }

        $nice = [];
        /** @var ObjectContent $row */
        foreach ($result->returnval as $row) {
            // var_dump($row); exit;
            $data = [
                'id'   => $row->obj->_,
                'type' => $row->obj->type
            ];

            if ($row->propSet) {
                foreach ($row->propSet as $prop) {
                    $data[$prop->name] = $prop->val;
                }

                $nice[$data['id']] = (object) $data;
            } elseif (empty($row->missingSet)) {
                continue;
            } else {
                $paths = [];
                $permissions = [];
                foreach ($row->missingSet as $missing) {
                    if ($missing->isNotAuthenticated()) {
                        throw new AuthenticationException('Not authenticated');
                    }
                    if ($missing->isNoPermission()) {
                        $paths[$missing->path] = true;
                        /** @var \Icinga\Module\Vspheredb\MappedClass\NoPermission $fault */
                        $fault = $missing->fault->fault;
                        $permissions[$fault->privilegeId] = true;
                    }
                }
                if (! empty($permissions)) {
                    throw new \RuntimeException(sprintf(
                        'Missing permissions (%s), missing properties: %s',
                        implode(', ', $permissions),
                        implode(', ', $paths)
                    ));
                }

                throw new \RuntimeException(sprintf(
                    'Missing properties: %s',
                    implode(', ', $paths)
                ));
            }
        }

        return $nice;
    }
}
