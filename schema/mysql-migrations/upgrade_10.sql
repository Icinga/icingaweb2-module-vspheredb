-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE host_system
  MODIFY COLUMN service_tag VARCHAR(64) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (10, NOW());
