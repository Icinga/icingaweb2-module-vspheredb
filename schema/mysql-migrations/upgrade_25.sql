ALTER TABLE host_pci_device CHANGE
  `function` device_function BINARY(1) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (25, NOW());
