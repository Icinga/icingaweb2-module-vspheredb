ALTER TABLE monitoring_connection
  DROP INDEX priority;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (16, NOW());
