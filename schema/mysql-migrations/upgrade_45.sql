DROP TABLE IF EXISTS vm_monitoring_hoststate;

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

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (45, NOW());
