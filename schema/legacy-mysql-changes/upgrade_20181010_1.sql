-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE virtual_machine
  ADD COLUMN hardware_numcorespersocket TINYINT UNSIGNED DEFAULT 1 NOT NULL
   AFTER hardware_numcpu;
