CREATE INDEX vcenter_time_idx ON alarm_history (vcenter_uuid, ts_event_ms);
CREATE INDEX vcenter_time_idx ON vm_event_history (vcenter_uuid, ts_event_ms);

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (36, NOW());
