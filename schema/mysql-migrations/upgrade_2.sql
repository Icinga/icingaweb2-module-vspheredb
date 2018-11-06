CREATE TABLE vspheredb_daemon (
  instance_uuid VARBINARY(16) NOT NULL, -- random by daemon
  fqdn VARCHAR(255) NOT NULL,
  username VARCHAR(64) NOT NULL,
  pid INT UNSIGNED NOT NULL,
  php_version VARCHAR(64) NOT NULL,
  ts_last_refresh BIGINT(20) UNSIGNED NOT NULL,
  process_info MEDIUMTEXT NOT NULL,
  PRIMARY KEY (instance_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;


CREATE TABLE vspheredb_daemonlog (
  vcenter_uuid VARBINARY(16) NOT NULL,
  instance_uuid VARBINARY(16) NOT NULL,
  ts_create BIGINT(20) UNSIGNED NOT NULL,
  level ENUM(
    'debug',
    'info',
    'warning',
    'error'
  ) NOT NULL,
  message MEDIUMTEXT NOT NULL,
  INDEX (vcenter_uuid, ts_create)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (2, NOW());
