ALTER TABLE vcenter
  ADD COLUMN api_name VARCHAR(64) DEFAULT NULL AFTER os_type;

UPDATE vcenter SET api_name = name WHERE 1;

UPDATE vcenter c SET name = COALESCE(
  (
    SELECT host
      FROM vcenter_server s
     WHERE s.vcenter_id = c.id
     ORDER BY s.enabled DESC, s.id
     LIMIT 1
  ),
  CONCAT(api_name, ' - ', id)
) WHERE 1;

ALTER TABLE vcenter
  MODIFY COLUMN api_name VARCHAR(64) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (23, NOW());
