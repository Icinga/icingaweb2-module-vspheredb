<?php

namespace Icinga\Module\Vspheredb;

class ApiClassMap
{
    public static function getMap()
    {
        $base = __NAMESPACE__ . "\\MappedClass";

        $map = [
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
            'InvalidProperty'      => "$base\\InvalidProperty",

            'VmBeingClonedEvent'         => "$base\\VmBeingClonedEvent",
            'VmBeingClonedNoFolderEvent' => "$base\\VmBeingClonedNoFolderEvent",
            'VmClonedEvent'              => "$base\\VmClonedEvent",
            'VmCloneFailedEvent'         => "$base\\VmCloneFailedEvent",

            'ElementDescription'     => "$base\\ElementDescription",
            'PerfCounterInfo'        => "$base\\PerfCounterInfo",
            'PerfInterval'           => "$base\\PerfInterval",
            'PerformanceDescription' => "$base\\PerformanceDescription",
            'PerformanceManager'     => "$base\\PerformanceManager",
        ];

        $base = __NAMESPACE__ . "\\VmwareDataType";

        $map += [
            'ManagedObjectReference' => "$base\\ManagedObjectReference",
            'NumericRange'           => "$base\\NumericRange",
        ];

        return $map;
    }
}