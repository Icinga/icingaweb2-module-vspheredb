-- SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
-- SPDX-License-Identifier: GPL-3.0-or-later

ALTER TABLE vcenter_server
  ADD COLUMN enabled ENUM ('y', 'n') NOT NULL DEFAULT 'y'
  AFTER ssl_verify_host;

ALTER TABLE vcenter_server
  MODIFY COLUMN enabled ENUM ('y', 'n') NOT NULL;
