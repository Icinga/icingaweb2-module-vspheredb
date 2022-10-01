UPDATE vm_snapshot
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

UPDATE vm_snapshot
  SET parent_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(parent_uuid), 1, 8),
    SUBSTR(HEX(parent_uuid), 9, 4),
    '5',
    SUBSTR(HEX(parent_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(parent_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(parent_uuid), 19, 2),
    SUBSTR(HEX(parent_uuid), 21, 12)
  ))
  WHERE LENGTH(parent_uuid) = 20;

UPDATE vm_snapshot
  SET vm_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(vm_uuid), 1, 8),
    SUBSTR(HEX(vm_uuid), 9, 4),
    '5',
    SUBSTR(HEX(vm_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(vm_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(vm_uuid), 19, 2),
    SUBSTR(HEX(vm_uuid), 21, 12)
  ))
  WHERE LENGTH(vm_uuid) = 20;

ALTER TABLE vm_snapshot
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL,
  MODIFY COLUMN parent_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL;

UPDATE vm_datastore_usage
  SET vm_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(vm_uuid), 1, 8),
    SUBSTR(HEX(vm_uuid), 9, 4),
    '5',
    SUBSTR(HEX(vm_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(vm_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(vm_uuid), 19, 2),
    SUBSTR(HEX(vm_uuid), 21, 12)
  ))
  WHERE LENGTH(vm_uuid) = 20;

UPDATE vm_datastore_usage
  SET datastore_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(datastore_uuid), 1, 8),
    SUBSTR(HEX(datastore_uuid), 9, 4),
    '5',
    SUBSTR(HEX(datastore_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(datastore_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(datastore_uuid), 19, 2),
    SUBSTR(HEX(datastore_uuid), 21, 12)
  ))
  WHERE LENGTH(datastore_uuid) = 20;

ALTER TABLE vm_datastore_usage
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL,
  MODIFY COLUMN datastore_uuid VARBINARY(16) NOT NULL;

UPDATE vm_hardware
  SET vm_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(vm_uuid), 1, 8),
    SUBSTR(HEX(vm_uuid), 9, 4),
    '5',
    SUBSTR(HEX(vm_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(vm_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(vm_uuid), 19, 2),
    SUBSTR(HEX(vm_uuid), 21, 12)
  ))
  WHERE LENGTH(vm_uuid) = 20;

ALTER TABLE vm_hardware
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL;

UPDATE vm_disk
  SET vm_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(vm_uuid), 1, 8),
    SUBSTR(HEX(vm_uuid), 9, 4),
    '5',
    SUBSTR(HEX(vm_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(vm_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(vm_uuid), 19, 2),
    SUBSTR(HEX(vm_uuid), 21, 12)
  ))
  WHERE LENGTH(vm_uuid) = 20;

UPDATE vm_disk
  SET datastore_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(datastore_uuid), 1, 8),
    SUBSTR(HEX(datastore_uuid), 9, 4),
    '5',
    SUBSTR(HEX(datastore_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(datastore_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(datastore_uuid), 19, 2),
    SUBSTR(HEX(datastore_uuid), 21, 12)
  ))
  WHERE LENGTH(datastore_uuid) = 20;

ALTER TABLE vm_disk
  MODIFY COLUMN datastore_uuid VARBINARY(16) NOT NULL,
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL;

UPDATE vm_disk_usage
  SET vm_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(vm_uuid), 1, 8),
    SUBSTR(HEX(vm_uuid), 9, 4),
    '5',
    SUBSTR(HEX(vm_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(vm_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(vm_uuid), 19, 2),
    SUBSTR(HEX(vm_uuid), 21, 12)
  ))
  WHERE LENGTH(vm_uuid) = 20;

ALTER TABLE vm_disk_usage
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL;

UPDATE vm_network_adapter
  SET vm_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(vm_uuid), 1, 8),
    SUBSTR(HEX(vm_uuid), 9, 4),
    '5',
    SUBSTR(HEX(vm_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(vm_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(vm_uuid), 19, 2),
    SUBSTR(HEX(vm_uuid), 21, 12)
  ))
  WHERE LENGTH(vm_uuid) = 20;

UPDATE vm_network_adapter
  SET portgroup_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(portgroup_uuid), 1, 8),
    SUBSTR(HEX(portgroup_uuid), 9, 4),
    '5',
    SUBSTR(HEX(portgroup_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(portgroup_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(portgroup_uuid), 19, 2),
    SUBSTR(HEX(portgroup_uuid), 21, 12)
  ))
  WHERE LENGTH(portgroup_uuid) = 20;

ALTER TABLE vm_network_adapter
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL,
  MODIFY COLUMN portgroup_uuid VARBINARY(16) DEFAULT NULL;

UPDATE vm_quick_stats
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

ALTER TABLE vm_quick_stats
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (46, NOW());
