CREATE INDEX host_name ON host_system (host_name);
CREATE INDEX template ON virtual_machine (template);
CREATE INDEX vcenter_uuid ON datastore (vcenter_uuid);
CREATE INDEX vcenter_uuid ON host_system (vcenter_uuid);
CREATE INDEX vcenter_uuid ON virtual_machine (vcenter_uuid);
CREATE INDEX vcenter_uuid ON object (vcenter_uuid);
CREATE INDEX vcenter_uuid ON host_sensor (vcenter_uuid);
CREATE INDEX vcenter_uuid ON host_pci_device (vcenter_uuid);

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (62, NOW());
