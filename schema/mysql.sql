SET sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION,PIPES_AS_CONCAT,ANSI_QUOTES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER';

-- CREATE TABLE trust_store (
--   id INT UNSIGNED NOT NULL AUTO_INCREMENT,
--   certificate
-- );

-- CREATE TABLE trust_store_ca (
--   trust_store_id
-- );

CREATE TABLE vcenter (
  instance_uuid VARBINARY(16) NOT NULL,
  vcenter_name VARCHAR(64) NOT NULL,
  trust_store_id INT UNSIGNED DEFAULT NULL, -- TODO: not null?
  name VARCHAR(64) NOT NULL, -- name	"VMware vCenter Server"
  version VARCHAR(10) NOT NULL, -- version	"6.0.0"
  os_type VARCHAR(32) NOT NULL, -- osType	"linux-x64"
  api_type VARCHAR(64) NOT NULL, -- apiType	"VirtualCenter"
  api_version VARCHAR(10) NOT NULL, -- apiVersion	"6.0"
  build VARCHAR(32) DEFAULT NULL, -- build "5318203"
  -- fullName -> "<api_type> <version> build-<build>"
  vendor VARCHAR(64) NOT NULL, -- vendor	"VMware, Inc."
  product_line VARCHAR(32) DEFAULT NULL, -- productLineId	string	"vpx"
  license_product_name VARCHAR(64) DEFAULT NULL, -- licenseProductName	"VMware VirtualCenter Server"
  license_product_version VARCHAR(10) DEFAULT NULL, -- licenseProductVersion"6.0"
  locale_build VARCHAR(32) DEFAULT NULL, -- localeBuild	"000"
  locale_version VARCHAR(10) DEFAULT NULL, -- localeVersion	"INTL"
  PRIMARY KEY (instance_uuid),
  UNIQUE INDEX vcenter_name (vcenter_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vcenter_server (
  host VARCHAR(255) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  scheme ENUM ('http', 'https') NOT NULL,
  username VARCHAR(64) NOT NULL,
  password VARCHAR(64) NOT NULL,
  proxy_type ENUM('HTTP', 'SOCKS5') DEFAULT NULL,
  proxy_address VARCHAR(255) DEFAULT NULL,
  proxy_user VARCHAR(64) DEFAULT NULL,
  proxy_pass VARCHAR(64) DEFAULT NULL,
  ssl_verify_peer ENUM ('y', 'n') NOT NULL,
  ssl_verify_host ENUM ('y', 'n') NOT NULL,
  PRIMARY KEY (host),
  CONSTRAINT server_vcenter
    FOREIGN KEY server_vcenter_uuid (vcenter_uuid)
    REFERENCES vcenter (vcenter_uuid)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vcenter_sync (
  vcenter_uuid VARBINARY(16) NOT NULL,
  fqdn VARCHAR(255) NOT NULL,
  username VARCHAR(64) NOT NULL,
  pid INT UNSIGNED NOT NULL,
  php_version VARCHAR(64) NOT NULL,
  ts_last_refresh BIGINT(20) NOT NULL,
  PRIMARY KEY (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vcenter_session (
  vcenter_uuid VARBINARY(16) NOT NULL,
  session_id VARBINARY(20) NOT NULL, -- hex2bin(sid)
  session_cookie_string VARCHAR(255) NOT NULL,
  session_cookie_name VARCHAR(64) NOT NULL,
  scope VARCHAR(32) NOT NULL,
  -- vmware_soap_session="72bb45defccdf97b1945e686228279af0c3746a9"; Path=/; HttpOnly; Secure;
  ts_created BIGINT(20) NOT NULL,
  ts_last_check BIGINT(20) NOT NULL,
  PRIMARY KEY (session_id),
  CONSTRAINT server_vcenter
    FOREIGN KEY server_vcenter_uuid (vcenter_uuid)
    REFERENCES vcenter (vcenter_uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vcenter_event_history_collector (
  session_id VARBINARY(20) NOT NULL,
  -- session[52dd54f1-28a1-4b84-6bd4-fc45fd9f3b78]52fc6d14-1c07-ffcd-107c-7132b2d263b0"
  ref_string VARCHAR(128) NOT NULL,
  ts_created BIGINT(20) NOT NULL,
  CONSTRAINT server_session
    FOREIGN KEY ehc_session (session_id)
    REFERENCES vcenter_session (session_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE object (
  uuid VARBINARY(20) NOT NULL, -- sha1(vcenter_uuid + moref)
  vcenter_uuid VARBINARY(16) NOT NULL,
  moref VARCHAR(32) NOT NULL, -- textual id
  object_name VARCHAR(255) NOT NULL,
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
    'ResourcePool',
    'StoragePod',
    'VirtualApp',
    'VirtualMachine',
    'VmwareDistributedVirtualSwitch'
  ) NOT NULL,
  overall_status ENUM(
     'gray',
     'green',
     'yellow',
     'red'
  ) NOT NULL,
  level TINYINT UNSIGNED NOT NULL,
  parent_uuid VARBINARY(20) DEFAULT NULL,
  PRIMARY KEY(uuid),
  UNIQUE KEY vcenter_moref (vcenter_uuid, moref),
  INDEX object_type (object_type),
  INDEX object_name (object_name(64)),
  CONSTRAINT object_parent
    FOREIGN KEY parent (parent_uuid)
    REFERENCES object (uuid)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT,
  CONSTRAINT object_vcenter
    FOREIGN KEY object_vcenter_uuid (vcenter_uuid)
    REFERENCES vcenter (instance_uuid)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_system (
  uuid VARBINARY(20) NOT NULL,
  host_name VARCHAR(255) DEFAULT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  product_api_version VARCHAR(32) NOT NULL, -- 6.0
  product_full_name VARCHAR(64) NOT NULL,   -- VMware ESXi 6.0.0 build-5572656
  bios_version VARCHAR(32) DEFAULT NULL, -- P89
  bios_release_date DATETIME DEFAULT NULL, -- 2017-02-17T00:00:00Z
  sysinfo_vendor VARCHAR(64) NOT NULL, -- HP
  sysinfo_model VARCHAR(64) NOT NULL,  -- ProLiant DL380 Gen9
  sysinfo_uuid VARCHAR(64) NOT NULL,   -- 30133937-3365-54a3-3544-30374a334d53
  service_tag VARCHAR(32) DEFAULT NULL, -- DQ6EXJ3
  hardware_cpu_model VARCHAR(64) NOT NULL, -- Intel(R) Xeon(R) CPU E5-2699A v4 @ 2.40GHz
  hardware_cpu_mhz INT UNSIGNED NOT NULL,
  hardware_cpu_packages SMALLINT UNSIGNED NOT NULL,
  hardware_cpu_cores SMALLINT UNSIGNED NOT NULL,
  hardware_cpu_threads SMALLINT UNSIGNED NOT NULL,
  hardware_memory_size_mb INT(10) UNSIGNED NOT NULL,
  hardware_num_hba SMALLINT UNSIGNED NOT NULL,
  hardware_num_nic SMALLINT UNSIGNED NOT NULL,
  runtime_power_state ENUM (
    'poweredOff',
    'poweredOn',
    'standby',
    'unknown'
  ) NOT NULL,
  PRIMARY KEY(uuid),
  UNIQUE INDEX sysinfo_uuid (sysinfo_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_pci_device (
  id VARCHAR(16) NOT NULL, -- bus:slot.function . But: 0000:00:00.0
  host_uuid  VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  bus BINARY(1) NOT NULL, -- byte
  slot BINARY(1) NOT NULL, -- byte
  function BINARY(1) NOT NULL, -- byte
  class_id SMALLINT NOT NULL, -- short
  device_id SMALLINT NOT NULL, -- short
  device_name VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  sub_device_id SMALLINT NOT NULL, -- short
  vendor_id SMALLINT NOT NULL, -- short
  vendor_name VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  sub_vendor_id SMALLINT NOT NULL, -- short
  parent_bridge VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY(host_uuid, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_sensor (
  name VARCHAR(128) NOT NULL,
  host_uuid  VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  health_state ENUM(
    'green',
    'yellow',
    'unknown',
    'red'
  ) NOT NULL,
  current_reading INT NOT NULL,
  unit_modifier SMALLINT NOT NULL,
  base_units VARCHAR(32) DEFAULT NULL,
  rate_units VARCHAR(32) DEFAULT NULL,
  sensor_type VARCHAR(32) NOT NULL,
  -- Seen so far:
  -- fan, power, system, temperature, voltage, other,
  -- Battery, Cable/Interconnect, Chassis, Management Subsystem Health,
  -- Memory, Processors, Software Components, Storage, System, Watchdog
  PRIMARY KEY(host_uuid, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE virtual_machine (
  uuid  VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  hardware_memorymb INT UNSIGNED NOT NULL,
  hardware_numcpu TINYINT UNSIGNED NOT NULL,
  template ENUM('y', 'n') NOT NULL, -- TODO: drop and skip templates? Or separate table?
  instance_uuid VARCHAR(64) NOT NULL,   -- 5004890e-8edd-fe5f-d116-d5704b2043e4
  bios_uuid VARCHAR(64) NOT NULL,       -- 42042ce7-1c4f-b339-2293-40357f1d6860
  version VARCHAR(32) NOT NULL,         -- vmx-11
  online_standby ENUM('y', 'n') NOT NULL,
  paused ENUM('y', 'n') NOT NULL,
  cpu_hot_add_enabled ENUM('y', 'n') NOT NULL,
  memory_hot_add_enabled ENUM('y', 'n') NOT NULL,
  connection_state ENUM (
    'connected',    -- server has access to the vm
    'disconnected', -- disconnected from the virtual machine, since its host is disconnected
    'inaccessible', -- vm config unaccessible
    'invalid',      -- vm config is invalid
    'orphaned'      -- vm no longer exists on host (but in vCenter)
  ) NOT NULL,
  guest_state ENUM (
    'notRunning',
    'resetting',
    'running',
    'shuttingDown',
    'standby',
    'unknown'
  ) NOT NULL,
  guest_tools_status ENUM (
    'toolsNotInstalled',
    'toolsNotRunning',
    'toolsOld',
    'toolsOk'
  ) NOT NULL,
  guest_tools_running_status ENUM (
    'guestToolsNotRunning',
    'guestToolsRunning',
    'guestToolsExecutingScripts' -- VMware Tools is starting.
  ) NOT NULL,
  guest_id VARCHAR(64) DEFAULT NULL,        -- rhel7_64Guest
  guest_full_name VARCHAR(64) DEFAULT NULL, -- Red Hat Enterprise Linux 7 (64-bit)
  guest_host_name VARCHAR(255) DEFAULT NULL,
  guest_ip_address VARCHAR(50) DEFAULT NULL,
  resource_pool_uuid VARBINARY(20) DEFAULT NULL,
  runtime_host_uuid VARBINARY(20) DEFAULT NULL,
  runtime_last_boot_time DATETIME DEFAULT NULL, -- TODO: to BIGINT?
  runtime_last_suspend_time DATETIME DEFAULT NULL, -- TODO: to BIGINT?
  runtime_power_state ENUM (
      'poweredOff',
      'poweredOn',
      'suspended'
  ) NOT NULL,
  boot_network_protocol ENUM('ipv4', 'ipv6') DEFAULT NULL,
  boot_order VARCHAR(128) DEFAULT NULL,
  annotation TEXT DEFAULT NULL,
  PRIMARY KEY(uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE distributed_virtual_switch (
  uuid VARBINARY(20) NOT NULL,
  num_hosts INT(10) NOT NULL,
  num_ports INT(10) NOT NULL,
  max_ports INT(10) NOT NULL,
  hostmembers_checksum VARBINARY(20) DEFAULT NULL,
  portgroups_checksum VARBINARY(20) DEFAULT NULL,
  vms_checksum VARBINARY(20) DEFAULT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  description TEXT DEFAULT NULL,
  PRIMARY KEY(uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE distributed_virtual_portgroup (
  uuid VARBINARY(20) NOT NULL,
  portgroup_type ENUM (
    'earlyBinding', -- assigned when reconfigured
    'ephemeral',    -- assigned when powered on
    'lateBinding'   -- Deprecated as of vSphere API 5.0.
  ) NOT NULL,
  vlan INT(10) DEFAULT NULL,
  vlan_ranges VARCHAR(255) DEFAULT NULL,
  num_ports INT(10) NOT NULL,
  distributed_virtual_switch_uuid VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE datastore (
  uuid VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  maintenance_mode ENUM(
      'normal',
      'enteringMaintenance',
      'inMaintenance'
  ) NOT NULL,
  is_accessible ENUM('y', 'n') NOT NULL,
  capacity BIGINT(20) UNSIGNED DEFAULT NULL,
  free_space BIGINT(20) UNSIGNED DEFAULT NULL,
  uncommitted BIGINT(20) UNSIGNED DEFAULT NULL,
  multiple_host_access ENUM('y', 'n') DEFAULT NULL,
  -- datastore_type ENUM(
  --     'vmfs', -- VMFS??
  --     'nfs',
  --     'cifs'
  -- ) NOT NULL,
  PRIMARY KEY(uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_snapshot (
  uuid VARBINARY(20) NOT NULL,
  parent_uuid VARBINARY(20) DEFAULT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  vm_uuid VARBINARY(20) NOT NULL,
  id INT UNSIGNED NOT NULL,
  moref VARCHAR(32) NOT NULL, -- textual id, we have no object entry
  name VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  ts_create BIGINT NOT NULL,
  state ENUM (
    'poweredOff',
    'poweredOn',
    'suspended'
  ) NOT NULL, -- VM power state when the snapshot was taken
  quiesced ENUM('y', 'n') NOT NULL,
  PRIMARY KEY (uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_datastore_usage (
  vm_uuid VARBINARY(20) NOT NULL,
  datastore_uuid  VARBINARY(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  committed BIGINT(20) UNSIGNED DEFAULT NULL,
  uncommitted BIGINT(20) UNSIGNED DEFAULT NULL,
  unshared BIGINT(20) UNSIGNED DEFAULT NULL,
  PRIMARY KEY(vm_uuid, datastore_uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_hardware (
  vm_uuid VARBINARY(20) NOT NULL,
  hardware_key INT(10) UNSIGNED NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  bus_number INT(10) UNSIGNED DEFAULT NULL,
  unit_number INT(10) UNSIGNED DEFAULT NULL, -- unit number of this device on its controller
  controller_key INT(10) UNSIGNED DEFAULT NULL,
  label VARCHAR(64) NOT NULL,
  summary VARCHAR(128) NOT NULL,
  PRIMARY KEY(vm_uuid, hardware_key),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_disk (
  vm_uuid VARBINARY(20) NOT NULL,
  hardware_key INT(10) UNSIGNED DEFAULT NULL,
  disk_uuid VARBINARY(16) DEFAULT NULL, -- backing->uuid: 6000C272-5a6b-ca2f-1706-4d2493ba11f0
  datastore_uuid VARBINARY(20) DEFAULT NULL, -- backing->datastore->_
  file_name VARCHAR(255) DEFAULT NULL, -- backing->fileName: [DSNAME] <name>/<name>.vmdk
  capacity BIGINT(20) UNSIGNED DEFAULT NULL, -- capacityInBytes
  disk_mode ENUM(
    'persistent', -- Changes are immediately and permanently written to the virtual disk.
    'nonpersistent', -- Changes to virtual disk are made to a redo log and discarded at power off.
    'undoable', -- Changes are made to a redo log, but you are given the option to commit or undo.
    'independent_persistent', -- Same as persistent, but not affected by snapshots.
    'independent_nonpersistent', -- Same as nonpersistent, but not affected by snapshots.
    'append' -- Changes are appended to the redo log; you revoke changes by removing the undo log.
  ) NOT NULL, -- backing->diskMode
  split ENUM ('y', 'n') DEFAULT NULL, --  Flag to indicate the type of virtual disk file: split or monolithic.
                                  -- If true, the virtual disk is stored in multiple files, each 2GB.
  write_through ENUM ('y', 'n') DEFAULT NULL,
  thin_provisioned ENUM ('y', 'n') DEFAULT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(vm_uuid, hardware_key),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_disk_usage (
  vm_uuid VARBINARY(20) NOT NULL,
  disk_path VARCHAR(128) NOT NULL,
  capacity BIGINT(20) NOT NULL,
  free_space BIGINT(20) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(vm_uuid, disk_path),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_network_adapter (
  vm_uuid VARBINARY(20) NOT NULL,
  hardware_key INT(10) UNSIGNED DEFAULT NULL,
  portgroup_uuid VARBINARY(20) DEFAULT NULL, -- port->portgroupKey (moid, dvportgroup-1288720)
  port_key INT(10) UNSIGNED DEFAULT NULL, -- port->portKey
  mac_address VARCHAR(17) DEFAULT NULL, -- binary(6)? new xxeuid?
  address_type ENUM(
    'manual',    -- Statically assigned MAC address
    'generated', -- Automatically generated MAC address
    'assigned'   -- MAC address assigned by VirtualCenter
  ) NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(vm_uuid, hardware_key),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_quick_stats (
  uuid VARBINARY(20) NOT NULL,
  distributed_cpu_fairness INT(10) NOT NULL,
  distributed_memory_fairness INT(10) NOT NULL,
  overall_cpu_usage INT(10) UNSIGNED NOT NULL,
  overall_memory_usage_mb INT(10) UNSIGNED NOT NULL,
  uptime INT(10) UNSIGNED NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_quick_stats (
  uuid VARBINARY(20) NOT NULL,
  ballooned_memory_mb INT(10) UNSIGNED NOT NULL,
  compressed_memory_kb BIGINT(20) UNSIGNED NOT NULL,
  consumed_overhead_memory_mb INT(10) UNSIGNED NOT NULL,
  distributed_cpu_entitlement INT(10) UNSIGNED NOT NULL, -- mhz
  distributed_memory_entitlement_mb INT(10) UNSIGNED NOT NULL,
  ft_latency_status ENUM('gray', 'green', 'red', 'yellow') NOT NULL,
  ft_log_bandwidth INT(10) DEFAULT NULL, -- Hint -1 -> NULL
  ft_secondary_latency INT(10) DEFAULT NULL, -- Hint -1 -> NULL
  guest_heartbeat_status ENUM('gray', 'green', 'red', 'yellow') NOT NULL,
  guest_memory_usage_mb INT(10) UNSIGNED NOT NULL,
  host_memory_usage_mb INT(10) UNSIGNED NOT NULL,
  overall_cpu_demand INT(10) UNSIGNED NOT NULL, -- mhz
  overall_cpu_usage INT(10) UNSIGNED NOT NULL, -- mhz
  private_memory_mb INT(10) UNSIGNED NOT NULL,
  shared_memory_mb INT(10) UNSIGNED NOT NULL,
  ssd_swapped_memory_kb BIGINT(10) UNSIGNED NOT NULL,
  static_cpu_entitlement INT(10) UNSIGNED NOT NULL, -- mhz
  static_memory_entitlement_mb INT(10) UNSIGNED NOT NULL,
  swapped_memory_mb INT(10) UNSIGNED NOT NULL,
  uptime INT(10) UNSIGNED NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY(uuid),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vmotion_history (
  id BIGINT(20) UNSIGNED AUTO_INCREMENT NOT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  ts_event_ms BIGINT(20) NOT NULL,
  event_type ENUM (
    'VmBeingMigratedEvent',
    'VmBeingHotMigratedEvent',
    'VmEmigratingEvent',
    'VmFailedMigrateEvent',
    'VmMigratedEvent'
  ) NOT NULL,
  event_key BIGINT(20) UNSIGNED NOT NULL,
  event_chain_id BIGINT(20) UNSIGNED NOT NULL,
  is_template ENUM('y', 'n') NOT NULL,
  vm_uuid VARBINARY(20) NOT NULL,
  datacenter_uuid VARBINARY(20) DEFAULT NULL,
  datastore_uuid VARBINARY(20) DEFAULT NULL,
  compute_resource_uuid VARBINARY(20) DEFAULT NULL,
  dvs_uuid VARBINARY(20) DEFAULT NULL,
  host_uuid VARBINARY(20) DEFAULT NULL,
  destination_host_uuid VARBINARY(20) DEFAULT NULL,
  destination_datacenter_uuid VARBINARY(20) DEFAULT NULL,
  destination_datastore_uuid VARBINARY(20) DEFAULT NULL,
  message TEXT DEFAULT NULL,
  user_name VARCHAR(128) DEFAULT NULL,
  fault_message TEXT DEFAULT NULL,
  fault_reason TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX time_idx (ts_event_ms),
  INDEX search_vm_idx (vm_uuid),
  INDEX search_host_idx (host_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE monitoring_connection (
  vcenter_uuid VARBINARY(16) NOT NULL,
  source_type ENUM (
      'ido'
  ) NOT NULL,
  source_resource_name VARCHAR(64) DEFAULT NULL, -- null means default resource
  host_property VARCHAR(128) DEFAULT NULL,
  monitoring_host_property VARCHAR(128) DEFAULT NULL,
  vm_property VARCHAR(128) DEFAULT NULL,
  monitoring_vm_host_property VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (vcenter_uuid),
  CONSTRAINT monitoring_vcenter
    FOREIGN KEY monitoring_vcenter_uuid (vcenter_uuid)
    REFERENCES vcenter (instance_uuid)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_monitoring_hoststate (
  host_uuid VARBINARY(20) NOT NULL,
  ido_connection_id INT(10) UNSIGNED NOT NULL,
  icinga_object_id BIGINT(20) NOT NULL,
  current_state ENUM(
    'UP',
    'DOWN',
    'UNREACHABLE',
    'MISSING'
  ) NOT NULL,
  PRIMARY KEY (host_uuid),
  INDEX sync_idx (ido_connection_id),
  CONSTRAINT host_monitoring_host
    FOREIGN KEY host_uuid (host_uuid)
    REFERENCES host_system (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE vm_monitoring_hoststate (
  vm_uuid VARBINARY(20) NOT NULL,
  ido_connection_id INT(10) UNSIGNED NOT NULL,
  icinga_object_id BIGINT(20) NOT NULL,
  current_state ENUM(
    'UP',
    'DOWN',
    'UNREACHABLE',
    'MISSING'
  ) NOT NULL,
  PRIMARY KEY (vm_uuid),
  INDEX sync_idx (ido_connection_id),
  CONSTRAINT vm_monitoring_host
    FOREIGN KEY vm_uuid (vm_uuid)
    REFERENCES virtual_machine (uuid)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE performance_unit (
  vcenter_uuid VARBINARY(16) NOT NULL,
  name VARCHAR(32) NOT NULL,
  label VARCHAR(16) NOT NULL,
  summary VARCHAR(64) NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE performance_group (
  vcenter_uuid VARBINARY(16) NOT NULL,
  name VARCHAR(32) NOT NULL,
  label VARCHAR(48) NOT NULL,
  summary VARCHAR(64) NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE performance_collection_interval (
  vcenter_uuid VARBINARY(16) NOT NULL,
  name VARCHAR(32) NOT NULL,
  label VARCHAR(48) NOT NULL,
  summary VARCHAR(64) NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE performance_counter (
  vcenter_uuid VARBINARY(16) NOT NULL,
  counter_key INT UNSIGNED NOT NULL,
  name VARCHAR(32) NOT NULL COLLATE utf8_bin,
  label VARCHAR(96) NOT NULL,
  group_name VARCHAR(32) NOT NULL,
  unit_name VARCHAR(32) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  stats_type ENUM( -- statsType
    'absolute',
    'delta',
    'rate'
  ) NOT NULL,
  rollup_type ENUM(  -- rollupType
    'average',
    'maximum',
    'minimum',
    'latest',
    'summation',
    'none'
  ) NOT NULL,
  level TINYINT UNSIGNED NOT NULL, -- level 1-4
  per_device_level TINYINT UNSIGNED NOT NULL, -- perDeviceLevel 1-4
  -- collection_interval INT UNSIGNED NOT NULL, -- 300, 86400... -> nur pro el?
  PRIMARY KEY (vcenter_uuid, counter_key),
  -- UNIQUE INDEX combined (vcenter_uuid, group_name, name, unit_name),
  CONSTRAINT performance_counter_vcenter
    FOREIGN KEY vcenter (vcenter_uuid)
    REFERENCES vcenter (instance_uuid)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT,
  CONSTRAINT performance_counter_group
    FOREIGN KEY performance_group (group_name)
    REFERENCES performance_group (name)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT,
  CONSTRAINT performance_counter_unit
    FOREIGN KEY performance_unit (unit_name)
    REFERENCES performance_unit (name)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE counter_300x5 (
  object_uuid VARBINARY(20) NOT NULL,
  counter_key INT UNSIGNED NOT NULL,
  instance VARCHAR(64) NOT NULL,
  ts_last BIGINT NOT NULL,
  value_last BIGINT NOT NULL,
  value_minus1 BIGINT DEFAULT NULL,
  value_minus2 BIGINT DEFAULT NULL,
  value_minus3 BIGINT DEFAULT NULL,
  value_minus4 BIGINT DEFAULT NULL,
  vcenter_uuid VARBINARY(16) NOT NULL,
  PRIMARY KEY (object_uuid, counter_key, instance),
  INDEX vcenter_uuid (vcenter_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

-- Not yet:
-- CREATE TABLE vm_triggered_alarm (
--   id BIGINT(20) UNSIGNED NOT NULL,
--   object_id INT(10) UNSIGNED NOT NULL,
-- );

-- CREATE TABLE vm_alarm_history (
--   vm_id BIGINT(20) UNSIGNED AUTO_INCREMENT NOT NULL,
-- );
