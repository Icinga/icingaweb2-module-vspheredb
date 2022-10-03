UPDATE host_pci_device
  SET host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(host_uuid), 1, 8),
    SUBSTR(HEX(host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(host_uuid), 19, 2),
    SUBSTR(HEX(host_uuid), 21, 12)
  ))
  WHERE LENGTH(host_uuid) = 20;

ALTER TABLE host_pci_device
  MODIFY COLUMN host_uuid VARBINARY(16) NOT NULL;

UPDATE host_sensor
  SET host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(host_uuid), 1, 8),
    SUBSTR(HEX(host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(host_uuid), 19, 2),
    SUBSTR(HEX(host_uuid), 21, 12)
  ))
  WHERE LENGTH(host_uuid) = 20;

ALTER TABLE host_sensor
  MODIFY COLUMN host_uuid VARBINARY(16) NOT NULL;

UPDATE host_physical_nic
  SET host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(host_uuid), 1, 8),
    SUBSTR(HEX(host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(host_uuid), 19, 2),
    SUBSTR(HEX(host_uuid), 21, 12)
  ))
  WHERE LENGTH(host_uuid) = 20;

ALTER TABLE host_physical_nic
   MODIFY COLUMN host_uuid VARBINARY(16) NOT NULL;

UPDATE host_virtual_nic
  SET host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(host_uuid), 1, 8),
    SUBSTR(HEX(host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(host_uuid), 19, 2),
    SUBSTR(HEX(host_uuid), 21, 12)
  ))
  WHERE LENGTH(host_uuid) = 20;

ALTER TABLE host_virtual_nic
  MODIFY COLUMN host_uuid VARBINARY(16) NOT NULL;

UPDATE host_hba
  SET host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(host_uuid), 1, 8),
    SUBSTR(HEX(host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(host_uuid), 19, 2),
    SUBSTR(HEX(host_uuid), 21, 12)
  ))
  WHERE LENGTH(host_uuid) = 20;

ALTER TABLE host_hba
  MODIFY COLUMN host_uuid VARBINARY(16) NOT NULL;

UPDATE host_list_member
  SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

ALTER TABLE host_list_member
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE host_quick_stats
  SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

ALTER TABLE host_quick_stats
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (44, NOW());
