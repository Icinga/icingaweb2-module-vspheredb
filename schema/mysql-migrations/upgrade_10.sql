ALTER TABLE host_system
  MODIFY COLUMN service_tag VARCHAR(64) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (10, NOW());
