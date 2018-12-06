ALTER TABLE vm_datastore_usage
  ADD COLUMN ts_updated BIGINT(20) UNSIGNED DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (7, NOW());
