-- SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later


ALTER TABLE host_system
  MODIFY sysinfo_vendor VARCHAR(64) NULL DEFAULT NULL,
  MODIFY sysinfo_model VARCHAR(64) NULL DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
(schema_version, migration_time)
VALUES (17, NOW());
