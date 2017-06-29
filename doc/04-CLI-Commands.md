<a name="CLI-Commands"></a>CLI Commands
=======================================

Fetch all available Virtual Machines
------------------------------------

This command is mostly for test/debug reasons and gives you an output of all
**Virtual Machines** with a default set of properties:

### Usage

    icingacli vsphere fetch virtualmachines [options]


### Options  
 
| Option                | Description                                     |
|-----------------------|-------------------------------------------------|
| `--<vhost> <host>`    | IP, host or URL to for your vCenter or ESX host |
| `--<username> <user>` | When authenticating, this username will be used |
| `--<password> <pass>` | The related password                            |
| `--benchmark`         | Show benchmark summary                          |
