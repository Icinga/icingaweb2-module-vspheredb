CREATE TABLE perfdata_consumer (
  uuid VARBINARY(16) NOT NULL,
  name VARCHAR(64) NOT NULL,
  implementation VARCHAR(128) NOT NULL, -- PHP class name
  settings TEXT NOT NULL, -- json-encoded implementation form settings
  enabled ENUM('y', 'n') NOT NULL,
  PRIMARY KEY (uuid),
  UNIQUE KEY perfdata_consumer_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (33, NOW());
