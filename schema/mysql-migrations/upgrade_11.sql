-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE virtual_machine
  MODIFY COLUMN instance_uuid VARCHAR(64) DEFAULT NULL,
  MODIFY COLUMN bios_uuid VARCHAR(64) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
VALUES (11, NOW());
