ALTER TABLE vspheredb_daemonlog
  ADD PRIMARY KEY (instance_uuid, ts_create);

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (59, NOW());
