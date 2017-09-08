git staSET sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION,PIPES_AS_CONCAT,ANSI_QUOTES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER';

CREATE TABLE object (
  id INT(10) UNSIGNED NOT NULL,
  textual_id VARCHAR(32) NOT NULL,
  object_name VARCHAR(255) NOT NULL,
  object_type ENUM(
    'ClusterComputeResource',
    'Datacenter',
    'Datastore',
    'DatastoreHostMount',
    'Folder',
    'HostMountInfo',
    'HostSystem',
    'ResourcePool',
    'StoragePod',
    'VirtualMachine'
  ) NOT NULL,
  overall_status ENUM(
     'gray',
     'green',
     'yellow',
     'red'
  ) NOT NULL,
  level TINYINT UNSIGNED NOT NULL,
  parent_id INT(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY(id),
  UNIQUE KEY textual_id (textual_id),
  INDEX object_type (object_type),
  INDEX object_name (object_name),
  CONSTRAINT object_parent
    FOREIGN KEY parent (parent_id)
    REFERENCES object (id)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE host_system (
  id INT(10) UNSIGNED NOT NULL,
  product_api_version VARCHAR(32) NOT NULL, -- 6.0
  product_full_name VARCHAR(64) NOT NULL,   -- VMware ESXi 6.0.0 build-5572656
  bios_version VARCHAR(32) NOT NULL, -- P89
  bios_release_date DATETIME DEFAULT NULL, -- 2017-02-17T00:00:00Z
  sysinfo_vendor VARCHAR(64) NOT NULL, -- HP
  sysinfo_model VARCHAR(64) NOT NULL,  -- ProLiant DL380 Gen9
  sysinfo_uuid VARCHAR(64) NOT NULL,   -- 30133937-3365-54a3-3544-30374a334d53
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
    'poweredOn'
  ) NOT NULL,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE virtual_machine (
  id INT(10) UNSIGNED NOT NULL,
  annotation TEXT DEFAULT NULL,
  hardware_memorymb INT UNSIGNED NOT NULL,
  hardware_numcpu TINYINT UNSIGNED NOT NULL,
  template ENUM('y', 'n') NOT NULL,
  instance_uuid VARCHAR(64) NOT NULL,   -- 5004890e-8edd-fe5f-d116-d5704b2043e4
  bios_uuid VARCHAR(64) NOT NULL,       -- 42042ce7-1c4f-b339-2293-40357f1d6860
  version VARCHAR(32) NOT NULL,         -- vmx-11
  guest_state ENUM (
    'notRunning',
    'resetting',
    'running',
    'shuttingDown',
    'standby',
    'unknown'
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
  resource_pool_id INT(10) UNSIGNED DEFAULT NULL,
  runtime_host_id INT(10) UNSIGNED DEFAULT NULL,
  runtime_last_boot_time DATETIME DEFAULT NULL,
  runtime_last_suspend_time DATETIME DEFAULT NULL,
  runtime_power_state ENUM (
      'poweredOff',
      'poweredOn'
      -- 'suspend' -- ??
  ) NOT NULL,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE datastore (
  id INT(10) UNSIGNED NOT NULL,
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
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE vm_datastore_usage (
  vm_id INT(10) UNSIGNED NOT NULL,
  datastore_id INT(10) UNSIGNED NOT NULL,
  committed BIGINT(20) UNSIGNED DEFAULT NULL,
  uncommitted BIGINT(20) UNSIGNED DEFAULT NULL,
  unshared BIGINT(20) UNSIGNED DEFAULT NULL,
  PRIMARY KEY(vm_id, datastore_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Not yet:
-- CREATE TABLE vm_triggered_alarm (
--   id BIGINT(20) UNSIGNED NOT NULL,
--   object_id INT(10) UNSIGNED NOT NULL,
-- );

-- CREATE TABLE vm_alarm_history (
--   vm_id BIGINT(20) UNSIGNED AUTO_INCREMENT NOT NULL,
-- );
