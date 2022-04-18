CREATE TABLE monitoring_rule_set (
  object_uuid VARBINARY(20) NOT NULL, -- DataCenter or Folder
  object_folder ENUM('root', 'vm', 'host', 'datastore') NOT NULL,
  settings TEXT NOT NULL, -- json-encoded param
  PRIMARY KEY (object_uuid, object_folder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (37, NOW());
