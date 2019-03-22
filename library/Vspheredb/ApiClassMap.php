<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Module\Vspheredb\MappedClass\ObjectContent;

class ApiClassMap
{
    protected static $map;

    public static function createInstanceForObjectContent(ObjectContent $content)
    {
        $map = static::getMap();
        $type = $content->obj->type;
        if (isset($map[$type])) {
            $class = $map[$type];
            $obj = new $class;
            foreach ($content->propSet as $dynamicProperty) {
                $obj->{$dynamicProperty->name} = $dynamicProperty->val;
            }

            return $obj;
        } else {
            throw new \RuntimeException(sprintf(
                'Type "%s" has no class mapping',
                $type
            ));
        }
    }

    public static function getMap()
    {
        if (self::$map === null) {
            self::$map = static::prepareMap();
        }

        return self::$map;
    }

    public static function prepareMap()
    {
        $base = __NAMESPACE__ . "\\MappedClass";

        // Hint: put more specific classes on top, otherwise a more generic one might match
        $map = [
            'RetrieveResult'             => "$base\\RetrieveResult",
            'RetrievePropertiesResponse' => "$base\\RetrievePropertiesResponse",
            'DynamicProperty'      => "$base\\DynamicProperty",
            'ObjectContent'        => "$base\\ObjectContent",
            'MissingProperty'      => "$base\\MissingProperty",
            'InvalidProperty'      => "$base\\InvalidProperty",
            'SystemError'          => "$base\\SystemError",
            'NotAuthenticated'     => "$base\\NotAuthenticated",
            'NoPermission'         => "$base\\NoPermission",
            'SecurityError'        => "$base\\SecurityError",
            'LocalizedMethodFault' => "$base\\LocalizedMethodFault",

            // 'ManagedObjectNotFoundFault' => "$base\\ManagedObjectNotFoundFault",
            // 'AlarmEvent'              => "$base\\AlarmEvent",
            'AlarmAcknowledgedEvent'  => "$base\\AlarmAcknowledgedEvent",
            'AlarmClearedEvent'       => "$base\\AlarmClearedEvent",
            'AlarmCreatedEvent'       => "$base\\AlarmCreatedEvent",
            'AlarmReconfiguredEvent'  => "$base\\AlarmReconfiguredEvent",
            'AlarmRemovedEvent'       => "$base\\AlarmRemovedEvent",
            'AlarmStatusChangedEvent' => "$base\\AlarmStatusChangedEvent",

            // AlarmActionTriggeredEvent
            // AlarmEmailCompletedEvent
            // AlarmEmailFailedEvent
            // AlarmScriptCompleteEvent
            // AlarmScriptFailedEvent
            // AlarmSnmpCompletedEvent
            // AlarmSnmpFailedEvent

            'UserLoginSessionEvent'            => "$base\\UserLoginSessionEvent",
            'SessionTerminatedEvent'           => "$base\\SessionTerminatedEvent",
            'NoAccessUserEvent'                => "$base\\NoAccessUserEvent",
            'BadUsernameSessionEvent'          => "$base\\BadUsernameSessionEvent",
            'GlobalMessageChangedEvent'        => "$base\\GlobalMessageChangedEvent",
            'UserLogoutSessionEvent'           => "$base\\UserLogoutSessionEvent",
            'AlreadyAuthenticatedSessionEvent' => "$base\\AlreadyAuthenticatedSessionEvent",

            // VmMessageEvent
            // TaskEvent
            // EventEx
            // AlarmStatusChangedEvent
            // UserLoginSessionEvent
            // DrsRuleViolationEvent
            // DrsSoftRuleViolationEvent
            // NonVIWorkloadDetectedOnDatastoreEvent
            // VmAcquiredTicketEvent
            // CustomFieldValueChangedEvent

            'VmFailedMigrateEvent'    => "$base\\VmFailedMigrateEvent",
            'MigrationEvent'          => "$base\\MigrationEvent",
            'VmBeingMigratedEvent'    => "$base\\VmBeingMigratedEvent",
            'VmBeingHotMigratedEvent' => "$base\\VmBeingHotMigratedEvent",
            'VmEmigratingEvent'       => "$base\\VmEmigratingEvent",
            'VmMigratedEvent'         => "$base\\VmMigratedEvent",

            'VmBeingCreatedEvent'  => "$base\\VmBeingCreatedEvent",
            'VmCreatedEvent'       => "$base\\VmCreatedEvent",
            'VmPoweredOnEvent'     => "$base\\VmPoweredOnEvent",
            'VmPoweredOffEvent'    => "$base\\VmPoweredOffEvent",
            'VmResettingEvent'     => "$base\\VmResettingEvent",
            'VmSuspendedEvent'     => "$base\\VmSuspendedEvent",
            'VmReconfiguredEvent'  => "$base\\VmReconfiguredEvent",
            'VmStartingEvent'      => "$base\\VmStartingEvent",
            'VmStoppingEvent'      => "$base\\VmStoppingEvent",
            // Not seen yet:
            'VmBeingDeployedEvent' => "$base\\VmBeingDeployedEvent",

            'VmBeingClonedEvent'         => "$base\\VmBeingClonedEvent",
            'VmBeingClonedNoFolderEvent' => "$base\\VmBeingClonedNoFolderEvent",
            'VmClonedEvent'              => "$base\\VmClonedEvent",
            'VmCloneFailedEvent'         => "$base\\VmCloneFailedEvent",

            'ElementDescription'     => "$base\\ElementDescription",
            'PerfCounterInfo'        => "$base\\PerfCounterInfo",
            'PerfInterval'           => "$base\\PerfInterval",
            'PerfEntityMetricCSV'    => "$base\\PerfEntityMetricCSV",
            'PerfMetricId'           => "$base\\PerfMetricId",
            'PerfMetricSeriesCSV'    => "$base\\PerfMetricSeriesCSV",
            'PerformanceDescription' => "$base\\PerformanceDescription",
            'PerformanceManager'     => "$base\\PerformanceManager",
            'PerfQuerySpec'          => "$base\\PerfQuerySpec",
        ];

        $base = __NAMESPACE__ . "\\VmwareDataType";

        $map += [
            'ManagedObjectReference' => "$base\\ManagedObjectReference",
            'NumericRange'           => "$base\\NumericRange",
        ];

        return $map;
    }
}
