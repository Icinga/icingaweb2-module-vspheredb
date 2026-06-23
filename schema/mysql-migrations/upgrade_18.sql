-- SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later


ALTER TABLE virtual_machine
    ADD COLUMN custom_values TEXT DEFAULT NULL AFTER boot_order;
ALTER TABLE host_system
    ADD COLUMN custom_values TEXT DEFAULT NULL;

INSERT INTO vspheredb_schema_migration
(schema_version, migration_time)
VALUES (18, NOW());
