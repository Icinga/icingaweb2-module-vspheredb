ALTER TABLE vm_event_history
  MODIFY COLUMN event_type ENUM (
    'VmBeingMigratedEvent',
    'VmBeingHotMigratedEvent',
    'VmEmigratingEvent',
    'VmFailedMigrateEvent',
    'VmMigratedEvent',
    'DrsVmMigratedEvent',
    'VmBeingCreatedEvent',
    'VmCreatedEvent',
    'VmStartingEvent',
    'VmPoweredOnEvent',
    'VmPoweredOffEvent',
    'VmResettingEvent',
    'VmSuspendedEvent',
    'VmStoppingEvent',
    'VmBeingDeployedEvent',
    'VmReconfiguredEvent',
    'VmBeingClonedEvent',
    'VmBeingClonedNoFolderEvent',
    'VmClonedEvent',
    'VmCloneFailedEvent'
  ) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (9, NOW());
