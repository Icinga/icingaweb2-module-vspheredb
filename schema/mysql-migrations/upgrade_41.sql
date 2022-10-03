DROP TABLE IF EXISTS host_monitoring_property;
DROP TABLE IF EXISTS vm_monitoring_property;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (41, NOW());
