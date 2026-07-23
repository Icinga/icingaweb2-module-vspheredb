CREATE TABLE daemon_config (
  `key` ENUM('log_level') PRIMARY KEY,
  value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (64, NOW());
