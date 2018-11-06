ALTER TABLE vm_network_adapter
  MODIFY COLUMN port_key VARCHAR(64) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (4, NOW());
