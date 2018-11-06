ALTER TABLE monitoring_connection
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (vcenter_uuid, priority),
  ADD COLUMN priority SMALLINT(5) UNSIGNED NOT NULL
    AFTER vcenter_uuid,
  MODIFY COLUMN source_type ENUM (
    'ido',
    'icinga2-api',
    'icingadb'
  ) NOT NULL;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (3, NOW());
