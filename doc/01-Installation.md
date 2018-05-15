<a id="Installation"></a>Installation
=====================================

Requirements
------------

* Icinga Web 2 (&gt;= 2.4.1)
* Icinga Web 2 module [reactbundle](https://github.com/Icinga/icingaweb2-module-reactbundle) (>= 0.3.0)
* Icinga Director (&gt; v1.5.0 current master)
* PHP (&gt;= 5.4 or 7.x)
* php-soap
* php-posix

> **Hint**: this module will hook into Icinga Director, but shouldn't depend on it
> at all. Currently it is based on some libraries provided by the Director, that's
> why you need to have a very recent version installed. We will ship those libraries
> separately in the near future to get rid of this dependency.

Once you got Icinga Web 2 up and running, all required dependencies should
already be there. All, but `php-soap` and `php-posix`. They are available on
all major Linux distributions and can be installed with your package manager
(yum, apt...). Same goes also for non-Linux systems. Please do not forget to
restart your web server service afterwards.

Installation from .tar.gz
-------------------------

Download the ~~latest version~~ (not yet) and extract it to a folder named
`vspheredb` in one of your Icinga Web 2 module path directories.

You might want to use a script as follows for this task:
```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/Icinga/icingaweb2-module-vspheredb"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/vspheredb"
MODULE_VERSION="1.0.0"
URL="${REPO_URL}/archive/v${MODULE_VERSION}.tar.gz"
install -d -m 0755 "${TARGET_DIR}"
wget -q -O - "$URL" | tar xfz - -C "${TARGET_DIR}" --strip-components 1
```

Installation from GIT repository
--------------------------------

Another convenient method is the installation directly from our GIT repository.
Just clone the repository to one of your Icinga Web 2 module path directories.
It will be immediately ready for use:

```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/Icinga/icingaweb2-module-vspheredb"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/vspheredb"
git clone "${REPO_URL}" "${TARGET_DIR}"
```

You can now directly use our current GIT master or check out a specific version.

Database
--------

### Create an empty database on MariaDB (or MySQL)

HINT: You should replace `some-password` with a secure custom password.

    mysql -e "CREATE DATABASE vspheredb CHARACTER SET 'utf8mb4';
       GRANT ALL ON vspheredb.* TO vspheredb@localhost IDENTIFIED BY 'some-password';"

### Create the vSphereDB module schema

    mysql vspheredb < schema/mysql.sql

### Create a related Icinga Web 2 Database resource

In your web frontend please go to `Configuration / Application / Resources`
and create a new database resource pointing to your newly created database.
Please make sure that you choose `utf8mb4` as an encoding.

Alternatively, you could also manally add a resource definition to your
resources.ini:

#### /etc/icingaweb2/resources.ini

```ini
[vSphereDB]
type = "db"
db = "mysql"
host = "localhost"
; port = 3306
dbname = "vspheredb"
username = "vspheredb"
password = "***"
charset = "utf8mb4"
```

Tell vSphereDB about it's database
----------------------------------

In the module's config.ini (usually `/etc/icingaweb2/module/vspheredb/config.ini`)
you need to reference above DB connection:

```ini
[db]
resource = "vSphereDB"
```

Enable the newly installed module
---------------------------------

Enable the `vspheredb` module either on the CLI by running...

```sh
icingacli module enable vspheredb
```

...or go to your Icinga Web 2 frontend, choose `Configuration` -&gt; `Modules`
-&gt; `vspheredb` module - and `enable` it:

![Enable the vSphere module](screenshot/01_installation/001_enable-module.png)


Connect to your vCenter
-----------------------

The GUI should lead you to a table allowing you to configure connections for
multiple vCenters. Once done, please initialize your connection on the CLI:

    icingacli vspheredb vcenter initialize --serverId 1

Hint: CLI commands expect IDs for now, you can figure them out by having a
look at the links in the frontend. Working with IDs is no fun, so this will
change in the final version.

Once that worked out, your vSphereDB Dashboard should finally show an empty
summary. Now  let's try to sync our vCenter, we're doing so at debug level in
the foreground to get an idea of what happens:

    icingacli vspheredb daemon run --vCenterId 1 --debug --trace

If what you see looks good to you, it's time to enable the background daemon.

Enabling and running the background daemon
------------------------------------------

For now, you need to run one daemon per vCenter. This will change in the final
version.

Once you played around with this modules and everything works fine when running
on commandline, time has come to enable a background daemon synchronizing your
vCenter to our vSphereDb.

    cp contrib/systemd/icinga-vspheredb@.service  /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable icinga-vspheredb@1
    systemctl start icinga-vspheredb@1

That's it, your daemon should now be running. Feel free to configure as many
vCenter Servers as you want, each of them with a distinct systemd service
instance.
