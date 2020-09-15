CREATE TABLE compute_resource (
  uuid VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  effective_cpu_mhz INT UNSIGNED NOT NULL,
  effective_memory_size_mb BIGINT(20) UNSIGNED NOT NULL,
  cpu_cores SMALLINT UNSIGNED NOT NULL,
  cpu_threads SMALLINT UNSIGNED NOT NULL,
  effective_hosts MEDIUMINT UNSIGNED NOT NULL,
  hosts MEDIUMINT UNSIGNED NOT NULL,
  total_memory_size_mb BIGINT(10) UNSIGNED NOT NULL,
  total_cpu_mhz INT UNSIGNED NOT NULL,
  PRIMARY KEY(uuid),
  UNIQUE INDEX vcenter_uuid_uuid (vcenter_uuid, uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (28, NOW());
