ALTER TABLE host_system
  ADD COLUMN das_host_state ENUM(
    'connectedToMaster',
    'election',
    'fdmUnreachable',
    'hostDown',
    'initializationError',
    'master',
    'networkIsolated',
    'networkPartitionedFromMaster',
    'uninitializationError',
    'uninitialized'
  ) DEFAULT NULL AFTER runtime_power_state;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (29, NOW());
