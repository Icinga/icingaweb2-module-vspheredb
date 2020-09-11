ALTER TABLE virtual_machine ADD COLUMN
  guest_tools_version VARCHAR(32) DEFAULT NULL AFTER guest_tools_running_status;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (24, NOW());
