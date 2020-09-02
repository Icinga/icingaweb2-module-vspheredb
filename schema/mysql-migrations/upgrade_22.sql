CREATE TABLE host_physical_nic (
  host_uuid VARBINARY(20) NOT NULL,
  nic_key VARCHAR(64) NOT NULL,
  auto_negotiate_supported ENUM ('y', 'n') DEFAULT NULL,
  device VARCHAR(128) NOT NULL,
  driver VARCHAR(128) DEFAULT NULL,
  link_speed_mb INT(10) UNSIGNED DEFAULT NULL,
  link_duplex ENUM ('y', 'n') DEFAULT NULL,
  mac_address VARCHAR(17) DEFAULT NULL, -- binary(6)? new xxeuid?
  pci VARCHAR(12) DEFAULT NULL, -- 0000:38:00.1
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(host_uuid, nic_key),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_virtual_nic (
  host_uuid VARBINARY(20) NOT NULL,
  nic_key VARCHAR(64) NOT NULL,
  net_stack_instance_key VARCHAR(128) DEFAULT NULL,
  port VARCHAR(128) DEFAULT NULL,
  portgroup VARCHAR(128) DEFAULT NULL,
  mac_address VARCHAR(17) DEFAULT NULL,
  mtu INT(10) DEFAULT NULL,
  ipv4_address VARCHAR(15) DEFAULT NULL,
  ipv4_subnet_mask VARCHAR(15) DEFAULT NULL,
  ipv6_address VARCHAR(47) DEFAULT NULL,
  ipv6_prefic_length INT(10) DEFAULT NULL,
  ipv6_dad_state ENUM (
    'deprecated',
    'duplicate',
    'inaccessible',
    'invalid',
    'preferred',
    'tentative',
    'unknown'
  ) DEFAULT NULL,
  ipv6_origin ENUM (
    'dhcp',
    'linklayer',
    'manual',
    'other',
    'random'
  ) DEFAULT NULL,
  -- distributedVirtualPort:
  dv_connection_cookie INT(10) DEFAULT NULL,
  dv_portgroup_key VARCHAR(128) DEFAULT NULL, -- dvportgroup-34067
  dv_port_key VARCHAR(128) DEFAULT NULL, -- 7
  dv_switch_uuid VARCHAR(47) DEFAULT NULL, --  50 1e be 8f 5e 26 a5 66-4e c9 30 14 ac 58 a0 1c
  device VARCHAR(128) DEFAULT NULL,
  tso_enabled ENUM ('y', 'n') DEFAULT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(host_uuid, nic_key),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (22, NOW());
