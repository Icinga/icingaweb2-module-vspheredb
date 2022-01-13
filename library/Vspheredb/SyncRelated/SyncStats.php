<?php

namespace Icinga\Module\Vspheredb\SyncRelated;

use gipfl\Json\JsonSerialization;

class SyncStats implements JsonSerialization
{
    protected $created = 0;
    protected $modified = 0;
    protected $deleted = 0;
    protected $totalFromApi = 0;
    protected $totalFromDb = 0;
    protected $label;

    public function __construct($label)
    {
        $this->label = $label;
    }

    public function setFromApi($count)
    {
        $this->totalFromApi = $count;
    }

    public function setFromDb($count)
    {
        $this->totalFromDb = $count;
    }

    public function incCreated($count = 1)
    {
        $this->created += $count;
    }

    public function incModified($count = 1)
    {
        $this->modified += $count;
    }

    public function incDeleted($count = 1)
    {
        $this->deleted += $count;
    }

    public function hasChanges()
    {
        return $this->created > 0 || $this->modified > 0 || $this->deleted > 0;
    }

    public function getLogMessage()
    {
        return sprintf(
            "%s: %d new, %d modified, %d deleted (got %d from DB, %d from API)",
            $this->label,
            $this->created,
            $this->modified,
            $this->deleted,
            $this->totalFromDb,
            $this->totalFromApi
        );
    }

    public static function fromSerialization($any)
    {
        $self = new static($any->label);
        $self->created      = $any->created;
        $self->modified     = $any->modified;
        $self->deleted      = $any->deleted;
        $self->totalFromApi = $any->totalFromApi;
        $self->totalFromDb  = $any->totalFromDb;

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) [
            'label'        => $this->label,
            'created'      => $this->created,
            'modified'     => $this->modified,
            'deleted'      => $this->deleted,
            'totalFromApi' => $this->totalFromApi,
            'totalFromDb'  => $this->totalFromDb,
        ];
    }
}
