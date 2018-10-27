ALTER TABLE monitoring_connection
  ADD COLUMN priority SMALLINT(5) UNSIGNED NOT NULL AFTER vcenter_uuid,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (vcenter_uuid, priority);
