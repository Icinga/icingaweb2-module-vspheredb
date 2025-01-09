-- The addition of a primary key on (instance_uuid, ts_create) will not be applied for now.
-- This is due to the possibility of existing duplicates in the table, which could result in
-- errors if the key were added to already populated tables. The comment helps track this change
-- for people who might not be on the latest release but instead are using the master branch,
-- and might have already imported the current state of the table before the key is added.

-- ALTER TABLE vspheredb_daemonlog
--   ADD PRIMARY KEY (instance_uuid, ts_create);

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (59, NOW());
