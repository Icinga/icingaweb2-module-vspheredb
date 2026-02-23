-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE virtual_machine
  MODIFY COLUMN guest_tools_running_status ENUM (
    'guestToolsNotRunning',
    'guestToolsRunning',
    'guestToolsExecutingScripts'
  ) NULL DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (30, NOW());
