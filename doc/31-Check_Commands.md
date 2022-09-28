<a id="Check_Commands"></a>Check Commands
=========================================

This module collects a lot of data from your vCenter(s) and/or ESXi Host(s).
Having single Check commands with lots of parameters and thresholds wouldn't
be very efficient, that's why we opted for shared responsibility:

* **Check Commands** are available as the glue between this module and the Icinga
  Core
* **Monitoring Rule Definitions** can be configured in the UI, and have a direct
  influence on related Check Commands

Object Checks based on Monitoring Rules
---------------------------------------

Thresholds and parameters for the following Checks can be defined in a hierarchical
way. Please read our [Monitoring Rules documentation](32-Monitoring_Rules.md) for
related details.

### Check Host Health

    icingacli vspheredb check host [--name <name>]

Checks the given Host, according the configured rules, with the Host matching the
given name.

### Check Virtual Machine Health

    icingacli vspheredb check vm [--name <name>]

Checks the Virtual Machine with the given name. If none is found, the check tries
to load a VM with such a guest hostname.

### Check Datastore Health

    icingacli vspheredb check datastore [--name <name>]

Checks the given Datastore object.

Object Type Summary Checks
--------------------------

These Checks allow to query the overall VMware object state for all instances
of a specific object at once. This might not be very useful in larger environments,
but might help to get a quick overview in smaller ones.

### Check all Hosts

    icingacli vspheredb check hosts

### Check all Virtual Machines

    icingacli vspheredb check vms

### Check all Datastores

    icingacli vspheredb check datastores

Self-Monitoring Health Check
----------------------------

There is a generic health check, which talks to the vSphereDB daemon through
it's Unix socket, reports generic health information, complains in case the
daemon is not reachable or when one of the vCenters/ESXi host connections is
failing or in a dubios state:

    icingacli vspheredb check health

The following image shows a sample output:

![vSphereDB Health Check](screenshot/03_checks/0308-health_check.png)

Formatting slightly differs based on whether you're monitoring multiple
vCenters/ESXi hosts, or just a single one. In case the daemon is not running,
this will also be reported:

![Daemon not running - vSphereDB Health Check](screenshot/03_checks/0309-health_check-no_daemon.png)

This check also complains if your daemon is not able to refresh the database:

![vSphereDB daemon DB state Check](screenshot/03_checks/0311_monitoring_daemon_check_db.png)

Checking for a single vCenter/ESXi Host connection
--------------------------------------------------

In case you want to check whether the vCenter has a connection to a very specific
vCenter, you can do so via:

    icingacli vspheredb check vcenterconnection --vCenter <id>

![Check a single vSphereDB connection](screenshot/03_checks/0310_check-vcenterconnection.png)
