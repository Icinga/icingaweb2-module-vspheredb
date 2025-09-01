<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\HtmlDocument;
use ipl\Html\Table;

class VmRouteConfigTable extends HtmlDocument
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $object;

    public function __construct(VirtualMachine $object)
    {
        $this->object = $object;
    }

    protected function assemble()
    {
        $object = $this->object;
        $this->prepend(new SubTitle($this->translate('Guest Routing Table'), 'sitemap'));
        $stacks = $object->guestIpStack();
        if ($stacks === null) {
            $this->add($this->translate('Got no Guest IP Stack information'));
        } else {
            $table = new Table();
            foreach ($stacks as $stack) {
                $table->add(Table::row([
                    $this->translate('Network'),
                    $this->translate('Gateway')
                ], ['class' => 'text-left'], 'th'));
                if (! isset($stack->ipRouteConfig->ipRoute)) {
                    continue;
                }
                foreach ($stack->ipRouteConfig->ipRoute as $route) {
                    $gateway = [];
                    if (isset($route->gateway->ipAddress)) {
                        $gateway[] = $route->gateway->ipAddress;
                    }
                    if (isset($route->gateway->device)) {
                        // 0 -> hardware_key minus 4000?
                        $gateway[] = $this->translate('Device') . ' ' . $route->gateway->device;
                    }
                    if (empty($gateway)) {
                        $gateway[] = '-';
                    }
                    $table->add(Table::row([
                        $route->network . '/' . $route->prefixLength,
                        implode(', ', $gateway),
                    ]));
                }
            }
            $this->add($table);
        }
    }
}
