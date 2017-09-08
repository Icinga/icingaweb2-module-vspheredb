Icinga Web 2 module for vSphere
===============================

Warning
-------

This is a fork of the [vsphere](https://github.com/Icinga/icingaweb2-module-vsphere)
module. It currently breaks everything and should not be used at all. Work on
this will be continued soon, and it should then be a replacement of the former
one, but with much more functionality.

![VMware vSphere Datastores](doc/screenshot/00_preview/00_preview_vmware-vsphere-datastores.png)


Old Readme
----------

In case you want to have an automated import of your Virtual Machines and/or
Physical Host from **VMware Sphere** (vCenter) into your Icinga monitoring
system this module might be what you have been looking for.

At the time of this writing, the main purpose of this module is being a
Import Source provider for the [Icinga Director](https://github.com/Icinga/icingaweb2-module-director):

[![Import from VMware vSphere](doc/screenshot/00_preview/000_preview-vmware-vsphere-center-configuration-for-icinga-director.png)](doc/03-Import-Source.md)


Documentation
-------------

### Basics
* [Installation](doc/01-Installation.md)
* [Define an Import Source](doc/03-Import-Source.md)
* [Working on the CLI](doc/04-CLI-Commands.md)
* [Contributing](doc/81-Contributing.md)
* [Changelog](doc/84-Changelog.md)

Compatibility
-------------

This module has no dependency on any SDK library. It had been written from
scratch for vCenter 6.x and tested with different installations of vCenter 6.0.
Directly accessing ESXi 6.5 hosts also worked fine. We expect it to work fine
also with older versions, but had no access to such for tests so far. In case
you have, please let us know!

Performance
-----------

This module should perform well. Here are some numbers showing resources spent
when fetching 2600+ VMs. The initial login took 430ms, then 2.6 seconds have
been needed to fetch all the data, and it took additional 350ms to process this
data. There is still room for optimizations, we for example implemented a Cookie
(session) cache, but disabled it for now. For the current task (being an Import
Source) this should easily be fast enough:

    +---------------------------+----------+----------+------------+-------------+
    | Description               | Off (ms) | Dur (ms) | Mem (diff) | Mem (total) |
    +---------------------------+----------+----------+------------+-------------+
    | Bootstrap ready           |    0.005 |    0.005 | 486.82 KiB |  486.82 KiB |
    | Dispatching CLI command   |   11.752 |   11.747 | 526.72 KiB | 1013.54 KiB |
    | Preparing the API         |   14.836 |    3.084 | 413.77 KiB | 1427.30 KiB |
    | Logged in, ready to fetch |  444.801 |  429.965 |  -4.08 KiB |   11.80 MiB |
    | Got 4696738 bytes         | 3055.117 | 2610.316 |   9.00 MiB |   20.81 MiB |
    | Got 2633 VMs              | 3411.375 |  356.258 |  14.30 MiB |   35.11 MiB |
    | Mapped properties         | 3411.393 |    0.018 |   896.00 B |   35.11 MiB |
    | Logged out                | 3415.851 |    0.060 |  -4.27 KiB |   26.15 MiB |
    +---------------------------+----------+----------+------------+-------------+

Please always make sure to fire your requests against your vCenter. Directly
querying your ESXi hosts will work, but you should then expect to be way slower.

Future Directions
-----------------

We'd love to see this module grow. By giving it a little local DB schema and a
lightweight daemon it could synchronize configuration, state and performance
data in a resource-efficient way. Monitoring checks could then directly use that
data and/or passively react to events. Additionally, this data would allow for
some nice new visualizations for the Icinga Web 2 GUI.
