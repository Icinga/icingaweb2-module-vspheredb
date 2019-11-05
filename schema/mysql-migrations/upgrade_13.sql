ALTER TABLE virtual_machine
  MODIFY COLUMN hardware_memorymb INT UNSIGNED NULL DEFAULT NULL,
  MODIFY COLUMN hardware_numcpu TINYINT UNSIGNED NULL DEFAULT NULL,
  MODIFY COLUMN hardware_numcorespersocket TINYINT UNSIGNED NULL DEFAULT 1,
  MODIFY COLUMN template ENUM('y', 'n') NULL DEFAULT NULL,
  MODIFY COLUMN version VARCHAR(32) NULL DEFAULT NULL,
  MODIFY COLUMN online_standby ENUM('y', 'n') NULL DEFAULT NULL,
  MODIFY COLUMN cpu_hot_add_enabled ENUM('y', 'n') NULL DEFAULT NULL,
  MODIFY COLUMN memory_hot_add_enabled ENUM('y', 'n') NULL DEFAULT NULL,
  MODIFY COLUMN guest_state ENUM (
    'notRunning',
    'resetting',
    'running',
    'shuttingDown',
    'standby',
    'unknown'
    ) NULL DEFAULT NULL,
  MODIFY COLUMN guest_tools_running_status ENUM (
    'guestToolsNotRunning',
    'guestToolsRunning',
    'guestToolsExecutingScripts' -- VMware Tools is starting.
    ) NULL DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (13, NOW());
