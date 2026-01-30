<?php

namespace Icinga\Module\Vspheredb\Hint;

use gipfl\Translation\TranslationHelper;

class ConnectionStateDetails
{
    use TranslationHelper;

    /** @var ?ConnectionStateDetails */
    protected static ?ConnectionStateDetails $instance = null;

    /**
     * @param string $state
     *
     * @return string|null
     */
    public static function getFor(string $state): ?string
    {
        return static::instance()->getConnectionStateDetails($state);
    }

    /**
     * @param string $state
     *
     * @return string|null
     */
    protected function getConnectionStateDetails(string $state): ?string
    {
        return match ($state) {
            'connected'    => $this->translate(
                'The server has access to the virtual machine'
            ),
            'disconnected' => $this->translate(
                'The server is currently disconnected from the virtual machine,'
                . ' since its host is disconnected'
            ),
            'inaccessible' => $this->translate(
                'One or more of the virtual machine configuration files are'
                . ' inaccessible. For example, this can be due to transient disk'
                . ' failures. In this case, no configuration can be returned for'
                . ' a virtual machine'
            ),
            'invalid'      => $this->translate(
                'The virtual machine configuration format is invalid. Thus, it is'
                . ' accessible on disk, but corrupted in a way that does not allow'
                . ' the server to read the content. In this case, no configuration'
                . ' can be returned for a virtual machine.'
            ),
            'orphaned'     => $this->translate(
                'The virtual machine is no longer registered on the host it is'
                . ' associated with. For example, a virtual machine that is'
                . ' unregistered or deleted directly on a host managed by'
                . ' VirtualCenter shows up in this state.'
            ),
            default        => null
        };
    }

    /**
     * @return static
     */
    protected static function instance(): ConnectionStateDetails
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
