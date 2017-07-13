<a id="Installation"></a>Installation
=====================================

Requirements
------------

* Icinga Web 2 (&gt;= 2.4.1)
* PHP (&gt;= 5.3 or 7.x)
* php-soap
* php-posix

Once you got Icinga Web 2 up and running, all required dependencies should
already be there. All, but `php-soap` and `php-posix`. They are available on
all major Linux distributions and can be installed with your package manager
(yum, apt...). Same goes also for non-Linux systems. Please do not forget to
restart your web server service afterwards.

Installation from .tar.gz
-------------------------

Download the latest version and extract it to a folder named `vsphere`
in one of your Icinga Web 2 module path directories.

You might want to use a script as follows for this task:
```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/Icinga/icingaweb2-module-vsphere"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/vsphere"
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
REPO_URL="https://github.com/Icinga/icingaweb2-module-vsphere"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/vsphere"
MODULE_VERSION="1.0.0"
git clone "${REPO_URL}" "${TARGET_DIR}"
```

You can now directly use our current GIT master or check out a specific version.

Enable the newly installed module
---------------------------------

Enable the `vsphere` module either on the CLI by running...

```sh
icingacli module enable vsphere
```

...or go to your Icinga Web 2 frontend, choose `Configuration` -&gt; `Modules`
-&gt; `vsphere` module - and `enable` it:

![Enable the vSphere module](screenshot/01_installation/001_enable-module.png)
