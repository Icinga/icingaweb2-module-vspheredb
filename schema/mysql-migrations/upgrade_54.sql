-- SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

DROP TABLE IF EXISTS host_monitoring_hoststate;

DROP TABLE IF EXISTS vm_monitoring_hoststate;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (54, NOW());
