ALTER TABLE vm_monitoring_hoststate
  DROP FOREIGN KEY vm_monitoring_host;

UPDATE virtual_machine SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

UPDATE virtual_machine
  SET resource_pool_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(resource_pool_uuid), 1, 8),
    SUBSTR(HEX(resource_pool_uuid), 9, 4),
    '5',
    SUBSTR(HEX(resource_pool_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(resource_pool_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(resource_pool_uuid), 19, 2),
    SUBSTR(HEX(resource_pool_uuid), 21, 12)
  ))
  WHERE LENGTH(resource_pool_uuid) = 20;

UPDATE virtual_machine
  SET runtime_host_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(runtime_host_uuid), 1, 8),
    SUBSTR(HEX(runtime_host_uuid), 9, 4),
    '5',
    SUBSTR(HEX(runtime_host_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(runtime_host_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(runtime_host_uuid), 19, 2),
    SUBSTR(HEX(runtime_host_uuid), 21, 12)
  ))
  WHERE LENGTH(runtime_host_uuid) = 20;

ALTER TABLE virtual_machine
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL,
  MODIFY COLUMN resource_pool_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN runtime_host_uuid VARBINARY(16) DEFAULT NULL;

UPDATE vm_monitoring_hoststate
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

ALTER TABLE vm_monitoring_hoststate
  MODIFY COLUMN vm_uuid VARBINARY(16) NOT NULL;

ALTER TABLE vm_monitoring_hoststate
  ADD CONSTRAINT vm_monitoring_host
    FOREIGN KEY vm_uuid (vm_uuid)
    REFERENCES virtual_machine (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (45, NOW());
