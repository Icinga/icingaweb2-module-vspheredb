CREATE TABLE storage_pod (
  uuid VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  pod_name VARCHAR(255) DEFAULT NULL,
  free_space BIGINT UNSIGNED NOT NULL,
  capacity BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (14, NOW());
