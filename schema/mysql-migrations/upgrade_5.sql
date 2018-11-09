ALTER TABLE host_system
  MODIFY COLUMN runtime_power_state ENUM (
    'poweredOff',
    'poweredOn',
    'standBy',
    'unknown'
  ) NOT NULL;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (5, NOW());
