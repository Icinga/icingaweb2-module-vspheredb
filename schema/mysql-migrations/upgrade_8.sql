ALTER TABLE datastore
  ADD COLUMN ts_last_forced_refresh BIGINT(20) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (8, NOW());
