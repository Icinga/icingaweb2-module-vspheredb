DROP TABLE vcenter_event_history_collector;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (60, NOW());
