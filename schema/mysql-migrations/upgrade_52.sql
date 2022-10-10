ALTER TABLE object
  ADD COLUMN tags TEXT DEFAULT NULL;

-- noinspection SqlWithoutWhere
UPDATE object SET tags = '[]';

ALTER TABLE object
  MODIFY COLUMN tags TEXT NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (52, NOW());
