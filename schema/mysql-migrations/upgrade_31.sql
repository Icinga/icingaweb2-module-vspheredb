ALTER TABLE object
  MODIFY COLUMN moref VARCHAR(128) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (31, NOW());
