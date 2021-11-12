<?php

namespace Icinga\Module\Vspheredb\SyncRelated;

class SyncStats
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
}
