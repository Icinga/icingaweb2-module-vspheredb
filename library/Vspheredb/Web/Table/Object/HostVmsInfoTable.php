<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\HostSystem;

class HostVmsInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    public function __construct(HostSystem $host)
    {
        $this->host = $host;
    }

    protected function getDb()
    {
        return $this->host->getConnection();
    }

    protected function assemble()
    {
        $host = $this->host;
        $uuid = $host->get('uuid');
        $this->addNameValuePairs([
            $this->translate('Vms') => Link::create(
                $host->countVms(),
                'vspheredb/host/vms',
                ['uuid' => bin2hex($uuid)]
            ),
        ]);
    }
}
