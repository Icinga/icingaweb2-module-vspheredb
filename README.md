Icinga Module for vSphere®
==========================

The easiest way to monitor a VMware vSphere environment. Configure a connection
to your *VMware vCenter®* or *VMware ESXi™* host and you're ready to go. This
module provides a lot of context, deep insight and great oversight. Fast
drill-down possibilities, valuable hints and reports.

You'll immediately see all your Host Systems, Virtual Machines, Data Stores and
much more pop up in your Icinga Web 2 frontend. This alone is already very helpful,
but there is more. This module:

* provides an **Import Source** for the Icinga Director
* hooks into the Monitoring module and shows **related information** next to
  your monitored Hosts
* provides **Reports**, helping to track down anomalies or configuration errors
* replicates the most interesting parts of your **Event- and Alarm History**

We currently support all VMware versions from 5.5 to 7.0, and did I mention that
all this is 100% free Open Source Software? Finally convinced? Then let's [get started](doc/01-Installation.md)!

When **Upgrading** please read our [Changelog](doc/84-Changelog.md).

Motivation
----------

Why yet another tool to monitor your virtualization platform, one might ask.
There are plenty of VMware-related Check-Plugins available for Icinga since a
long time. So why all the effort for writing yet another piece of software?

First of all, many existing Plugins are facing similar problems with Session
handling and conflicts related to vendor libraries, system libraries or a
combination of both. Also, they tend to be pretty expensive, as they are forced
to rediscover large parts of your vCenter or ESXi host on every single check
execution.

This module differs substantially as it:

* does not depend on vendor (VMware) libraries
* replicates your discovered objects in it's own database

Those two main design decisions allow us to show and monitor many more details
while putting much less burden on your virtualization platform.


![VMware vSphere Datastores](doc/screenshot/00_preview/00_preview_vmware-vsphere-datastores.png)
