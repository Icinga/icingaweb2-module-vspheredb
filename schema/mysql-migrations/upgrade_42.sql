ALTER TABLE object DROP FOREIGN KEY object_parent;

UPDATE object SET uuid = UNHEX(CONCAT(
    SUBSTR(HEX(uuid), 1, 8),
    SUBSTR(HEX(uuid), 9, 4),
    '5',
    SUBSTR(HEX(uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(uuid), 19, 2),
    SUBSTR(HEX(uuid), 21, 12)
  ))
  WHERE LENGTH(uuid) = 20;

UPDATE object SET parent_uuid = UNHEX(CONCAT(
    SUBSTR(HEX(parent_uuid), 1, 8),
    SUBSTR(HEX(parent_uuid), 9, 4),
    '5',
    SUBSTR(HEX(parent_uuid), 14, 3),
    HEX((CONV(SUBSTR(HEX(parent_uuid), 17, 2), 16, 10) & 0x3f) | 0x80),
    SUBSTR(HEX(parent_uuid), 19, 2),
    SUBSTR(HEX(parent_uuid), 21, 12)
  ))
  WHERE LENGTH(parent_uuid) = 20;

ALTER TABLE object
  MODIFY COLUMN uuid VARBINARY(16) NOT NULL,
  MODIFY COLUMN parent_uuid VARBINARY(16) DEFAULT NULL;

ALTER TABLE object ADD CONSTRAINT object_parent
    FOREIGN KEY parent (parent_uuid)
    REFERENCES object (uuid)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (42, NOW());
