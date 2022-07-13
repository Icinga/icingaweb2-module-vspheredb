CREATE TABLE host_hba (
  host_uuid VARBINARY(20) NOT NULL,
  hba_key VARCHAR(64) NOT NULL,
  device VARCHAR(128) NOT NULL,
  bus VARCHAR(128) DEFAULT NULL,
  driver VARCHAR(128) DEFAULT NULL,
  model VARCHAR(128) DEFAULT NULL,
  pci VARCHAR(12) DEFAULT NULL, -- 0000:38:00.1
  status ENUM('online', 'offline', 'unbound', 'unknown') NOT NULL,
  storage_protocol ENUM ('scsi', 'nvme'),
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(host_uuid, hba_key),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (39, NOW());
