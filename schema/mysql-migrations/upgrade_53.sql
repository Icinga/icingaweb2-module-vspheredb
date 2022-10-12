
ALTER TABLE vm_event_history
  ADD INDEX vcenter_event_key_idx (vcenter_uuid, event_key);

ALTER TABLE alarm_history
  ADD INDEX vcenter_event_key_idx (vcenter_uuid, event_key);

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (53, NOW());
