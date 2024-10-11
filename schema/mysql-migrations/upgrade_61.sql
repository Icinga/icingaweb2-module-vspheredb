ALTER TABLE virtual_machine
  MODIFY COLUMN hardware_numcpu SMALLINT UNSIGNED NULL DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (61, NOW());
