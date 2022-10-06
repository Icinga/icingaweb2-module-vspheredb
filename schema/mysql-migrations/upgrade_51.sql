ALTER TABLE host_sensor
  MODIFY COLUMN current_reading BIGINT NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (51, NOW());
