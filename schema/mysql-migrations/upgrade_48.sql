UPDATE alarm_history
  SET entity_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(entity_uuid), 1, 8),
    SUBSTR(HEX(entity_uuid), 9, 4),
    '5',
    SUBSTR(HEX(entity_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(entity_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(entity_uuid), 19, 2),
    SUBSTR(HEX(entity_uuid), 21, 12)
  ))
  WHERE LENGTH(entity_uuid) = 20;

UPDATE alarm_history
  SET source_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(source_uuid), 1, 8),
    SUBSTR(HEX(source_uuid), 9, 4),
    '5',
    SUBSTR(HEX(source_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(source_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(source_uuid), 19, 2),
    SUBSTR(HEX(source_uuid), 21, 12)
  ))
  WHERE LENGTH(source_uuid) = 20;

ALTER TABLE alarm_history
  MODIFY COLUMN entity_uuid VARBINARY(16) DEFAULT NULL,
  MODIFY COLUMN source_uuid VARBINARY(16) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (48, NOW());
