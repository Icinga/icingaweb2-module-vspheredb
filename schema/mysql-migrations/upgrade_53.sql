
SET @stmt = (SELECT IF(
(SELECT EXISTS(
    SELECT * FROM information_schema.STATISTICS
    WHERE
        table_schema   = DATABASE()
        AND table_name = 'vm_event_history'
        AND index_name = 'vcenter_event_key_idx'
)),
'SELECT 1',
'ALTER TABLE vm_event_history ADD INDEX vcenter_event_key_idx (vcenter_uuid, event_key)'
));
PREPARE stmt FROM @stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET @stmt = NULL;

ALTER TABLE alarm_history
  ADD INDEX vcenter_event_key_idx (vcenter_uuid, event_key);

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (53, NOW());
