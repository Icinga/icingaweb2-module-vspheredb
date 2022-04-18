DROP TABLE IF EXISTS monitoring_connection;

CREATE TABLE monitoring_connection (
  id INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
  priority SMALLINT(5) UNSIGNED NOT NULL,
  vcenter_uuid VARBINARY(16) DEFAULT NULL,
  source_type ENUM (
      'ido',
      'icinga2-api',
      'icingadb'
  ) NOT NULL,
  source_resource_name VARCHAR(64) DEFAULT NULL, -- null means default resource
  host_property VARCHAR(128) DEFAULT NULL,
  monitoring_host_property VARCHAR(128) DEFAULT NULL,
  vm_property VARCHAR(128) DEFAULT NULL,
  monitoring_vm_host_property VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT monitoring_vcenter
    FOREIGN KEY monitoring_vcenter_uuid (vcenter_uuid)
    REFERENCES vcenter (instance_uuid)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (38, NOW());
