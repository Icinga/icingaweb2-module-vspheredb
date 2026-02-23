-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE virtual_machine
  MODIFY COLUMN guest_full_name VARCHAR(255) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
  (schema_version, migration_time)
  VALUES (27, NOW());
