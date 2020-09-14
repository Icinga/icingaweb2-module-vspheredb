ALTER TABLE object MODIFY COLUMN
  object_type ENUM(
    'ComputeResource',
    'ClusterComputeResource',
    'Datacenter',
    'Datastore',
    'DatastoreHostMount',
    'DistributedVirtualPortgroup',
    'DistributedVirtualSwitch',
    'Folder',
    'HostMountInfo',
    'HostSystem',
    'Network',
    'OpaqueNetwork',
    'ResourcePool',
    'StoragePod',
    'VirtualApp',
    'VirtualMachine',
    'VmwareDistributedVirtualSwitch'
  ) NOT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (26, NOW());
