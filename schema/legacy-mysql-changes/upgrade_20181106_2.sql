CREATE TABLE vspheredb_schema_migration (
  schema_version SMALLINT UNSIGNED NOT NULL,
  migration_time DATETIME NOT NULL,
  PRIMARY KEY(schema_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (1, NOW());
