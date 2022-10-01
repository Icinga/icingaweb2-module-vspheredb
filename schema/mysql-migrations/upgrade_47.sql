UPDATE vm_list_member
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

ALTER TABLE vm_list_member
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE compute_resource
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

ALTER TABLE compute_resource
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE storage_pod SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

ALTER TABLE storage_pod
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE distributed_virtual_switch SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

ALTER TABLE distributed_virtual_switch
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE distributed_virtual_portgroup SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

ALTER TABLE distributed_virtual_portgroup
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE datastore SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

ALTER TABLE datastore
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

UPDATE monitoring_rule_set SET object_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(object_uuid), 1, 8),
    SUBSTR(HEX(object_uuid), 9, 4),
    '5',
    SUBSTR(HEX(object_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(object_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(object_uuid), 19, 2),
    SUBSTR(HEX(object_uuid), 21, 12)
  ))
  WHERE LENGTH(object_uuid) = 20;

ALTER TABLE monitoring_rule_set
  MODIFY COLUMN object_uuid VARBINARY(16) NOT NULL;

UPDATE counter_300x5 SET object_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(object_uuid), 1, 8),
    SUBSTR(HEX(object_uuid), 9, 4),
    '5',
    SUBSTR(HEX(object_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(object_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(object_uuid), 19, 2),
    SUBSTR(HEX(object_uuid), 21, 12)
  ))
  WHERE LENGTH(object_uuid) = 20;

ALTER TABLE counter_300x5
  MODIFY COLUMN object_uuid VARBINARY(16) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (47, NOW());
