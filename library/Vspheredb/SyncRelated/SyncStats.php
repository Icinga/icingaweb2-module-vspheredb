<?php

namespace Icinga\Module\Vspheredb\SyncRelated;

use gipfl\Json\JsonSerialization;
use ReturnTypeWillChange;

class SyncStats implements JsonSerialization
{
    protected int $created = 0;

    protected int $modified = 0;

    protected int $deleted = 0;

    protected int $totalFromApi = 0;

    protected int $totalFromDb = 0;

    protected string $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function setFromApi($count): void
    {
        $this->totalFromApi = $count;
    }

    public function setFromDb($count): void
    {
        $this->totalFromDb = $count;
    }

    public function incCreated($count = 1): void
    {
        $this->created += $count;
    }

    public function incModified($count = 1): void
    {
        $this->modified += $count;
    }

    public function incDeleted($count = 1): void
    {
        $this->deleted += $count;
    }

    public function hasChanges(): bool
    {
        return $this->created > 0 || $this->modified > 0 || $this->deleted > 0;
    }

    public function getLogMessage(): string
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

    public static function fromSerialization($any): static
    {
        $self = new static($any->label);
        $self->created      = $any->created;
        $self->modified     = $any->modified;
        $self->deleted      = $any->deleted;
        $self->totalFromApi = $any->totalFromApi;
        $self->totalFromDb  = $any->totalFromDb;

        return $self;
    }

    #[ReturnTypeWillChange]
    /**
     * @return object
     */
    public function jsonSerialize(): object
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
