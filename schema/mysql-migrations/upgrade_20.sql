TRUNCATE vspheredb_daemonlog;

INSERT INTO vspheredb_schema_migration
(schema_version, migration_time)
VALUES (20, NOW());
