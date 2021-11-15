CREATE TABLE perfdata_subscription (
  uuid VARBINARY(16) NOT NULL,
  consumer_uuid VARBINARY(16) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  settings TEXT NOT NULL, -- json-encoded implementation form settings
  enabled ENUM('y', 'n') NOT NULL,
  PRIMARY KEY (uuid),
  CONSTRAINT perfdata_subscription_consumer
    FOREIGN KEY subscription_consumer_uuid (consumer_uuid)
    REFERENCES perfdata_consumer (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT perfdata_subscription_vcenter
    FOREIGN KEY subscription_vcenter_uuid (vcenter_uuid)
    REFERENCES vcenter (instance_uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (34, NOW());
