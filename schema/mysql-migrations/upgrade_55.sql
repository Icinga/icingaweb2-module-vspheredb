CREATE TABLE host_monitoring_hoststate (
  host_uuid VARBINARY(16) NOT NULL,
  ido_connection_id INT(10) UNSIGNED NOT NULL,
  icinga_object_id BIGINT(20) NOT NULL,
  current_state ENUM(
    'UP',
    'DOWN',
    'UNREACHABLE',
    'MISSING'
  ) NOT NULL,
  PRIMARY KEY (host_uuid),
  INDEX sync_idx (ido_connection_id),
  CONSTRAINT host_monitoring_host
    FOREIGN KEY host_uuid (host_uuid)
    REFERENCES host_system (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_monitoring_hoststate (
  vm_uuid VARBINARY(16) NOT NULL,
  ido_connection_id INT(10) UNSIGNED NOT NULL,
  icinga_object_id BIGINT(20) NOT NULL,
  current_state ENUM(
    'UP',
    'DOWN',
    'UNREACHABLE',
    'MISSING'
  ) NOT NULL,
  PRIMARY KEY (vm_uuid),
  INDEX sync_idx (ido_connection_id),
  CONSTRAINT vm_monitoring_host
    FOREIGN KEY vm_uuid (vm_uuid)
    REFERENCES virtual_machine (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (55, NOW());
