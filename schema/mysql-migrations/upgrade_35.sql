ALTER TABLE performance_counter
  MODIFY COLUMN label VARCHAR(255) NOT NULL,
  MODIFY COLUMN summary TEXT NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (35, NOW());
