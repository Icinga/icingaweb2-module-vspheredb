<a id="REST_API">REST API</a>
=============================

In case you want to fetch data via REST API, the following endpoints have been
implemented:

    vspheredb/vms/export
    vspheredb/hosts/export
    vspheredb/datastores/export

URL Parameters
--------------

| Parameter       | Description                                                     |
|-----------------|-----------------------------------------------------------------|
| vcenter=<uuid>  | UUID, e.g. de037ec0-20b7-4f63-a341-124f9977f15e                 |
| parent=<uuid>   | Parent (folder) UUID, e.g. de037ec0-20b7-4f63-a341-124f9977f15e |
| showDescendants | Boolean, default true: whether to show all descendants of the   |
|                 | specified parent, or just directly attached objects             |
|                 | Syntax: `&showDescendants`, `&!showDescendants`                 |
|                 | Also supported: `&showDescendants=1`, `&showDescendants=0`      |

This exportes a fixed set of properties, corresponding to those which are also
being exported to the Icinga Director. State-related properties do not make part
of this property set.

Main table URLs
---------------

If above export doesn't fit your needs, you could also check our main Host,
Virtual Machine or Datastore tables. They allow for custom columns, and provide
a related "Download" action, which is accessible via REST too:

    vspheredb/vms
    vspheredb/hosts
    vspheredb/datastores

To get a REST/JSON response for those endpoints, please use `Accept: application/json`
in your request header. In addition to the columns for our `vspheredb/*/export` URLs,
they also support the following:

| Parameter               | Description                                               |
|-------------------------|-----------------------------------------------------------|
| columns=<col1>[,<colX>] | comma-separated list of column names. Please check our UI |
|                         | for a list of allowed values. They might change over time |
