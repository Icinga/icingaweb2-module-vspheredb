-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE vm_network_adapter
  MODIFY COLUMN port_key VARCHAR(64) DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
    (schema_version, migration_time)
VALUES (4, NOW());
