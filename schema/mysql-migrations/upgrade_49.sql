UPDATE vm_event_history
  SET datacenter_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(datacenter_uuid), 1, 8),
    SUBSTR(HEX(datacenter_uuid), 9, 4),
    '5',
    SUBSTR(HEX(datacenter_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(datacenter_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(datacenter_uuid), 19, 2),
    SUBSTR(HEX(datacenter_uuid), 21, 12)
  ))
  WHERE LENGTH(datacenter_uuid) = 20;

UPDATE vm_event_history
  SET compute_resource_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(compute_resource_uuid), 1, 8),
    SUBSTR(HEX(compute_resource_uuid), 9, 4),
    '5',
    SUBSTR(HEX(compute_resource_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(compute_resource_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(compute_resource_uuid), 19, 2),
    SUBSTR(HEX(compute_resource_uuid), 21, 12)
  ))
  WHERE LENGTH(compute_resource_uuid) = 20;

UPDATE vm_event_history
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

UPDATE vm_event_history
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

UPDATE vm_event_history
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

UPDATE vm_event_history
  SET dvs_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(dvs_uuid), 1, 8),
    SUBSTR(HEX(dvs_uuid), 9, 4),
    '5',
    SUBSTR(HEX(dvs_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(dvs_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(dvs_uuid), 19, 2),
    SUBSTR(HEX(dvs_uuid), 21, 12)
  ))
  WHERE LENGTH(dvs_uuid) = 20;

UPDATE vm_event_history
  SET destination_host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(destination_host_uuid), 1, 8),
    SUBSTR(HEX(destination_host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(destination_host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(destination_host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(destination_host_uuid), 19, 2),
    SUBSTR(HEX(destination_host_uuid), 21, 12)
  ))
  WHERE LENGTH(destination_host_uuid) = 20;

UPDATE vm_event_history
  SET destination_datacenter_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(destination_datacenter_uuid), 1, 8),
    SUBSTR(HEX(destination_datacenter_uuid), 9, 4),
    '5',
    SUBSTR(HEX(destination_datacenter_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(destination_datacenter_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(destination_datacenter_uuid), 19, 2),
    SUBSTR(HEX(destination_datacenter_uuid), 21, 12)
  ))
  WHERE LENGTH(destination_datacenter_uuid) = 20;

UPDATE vm_event_history
  SET destination_datastore_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(destination_datastore_uuid), 1, 8),
    SUBSTR(HEX(destination_datastore_uuid), 9, 4),
    '5',
    SUBSTR(HEX(destination_datastore_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(destination_datastore_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(destination_datastore_uuid), 19, 2),
    SUBSTR(HEX(destination_datastore_uuid), 21, 12)
  ))
  WHERE LENGTH(destination_datastore_uuid) = 20;

ALTER TABLE vm_event_history
  MODIFY COLUMN datacenter_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN compute_resource_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN host_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN vm_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN datastore_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN dvs_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN destination_host_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN destination_datacenter_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN destination_datastore_uuid VARBINARY(16) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (49, NOW());
