-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE virtual_machine ADD COLUMN
  guest_tools_version VARCHAR(32) DEFAULT NULL AFTER guest_tools_running_status;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (24, NOW());
