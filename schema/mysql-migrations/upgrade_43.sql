ALTER TABLE host_monitoring_hoststate
  DROP FOREIGN KEY host_monitoring_host;

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

UPDATE host_monitoring_hoststate
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

ALTER TABLE host_monitoring_hoststate
  MODIFY COLUMN host_uuid VARBINARY(16) NOT NULL;

ALTER TABLE host_monitoring_hoststate
  ADD CONSTRAINT host_monitoring_host
    FOREIGN KEY host_uuid (host_uuid)
    REFERENCES host_system (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (43, NOW());
