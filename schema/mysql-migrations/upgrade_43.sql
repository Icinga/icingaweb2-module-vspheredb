DROP TABLE IF EXISTS host_monitoring_hoststate;

UPDATE host_system
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

ALTER TABLE host_system
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (43, NOW());
