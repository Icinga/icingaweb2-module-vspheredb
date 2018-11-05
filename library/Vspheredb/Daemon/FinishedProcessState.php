<?php

namespace Icinga\Module\Vspheredb\Daemon;

class FinishedProcessState
{
    /** @var int|null */
    protected $exitCode;

    /** @var int|null */
    protected $termSignal;

    public function __construct($exitCode, $termSignal)
    {
        $this->exitCode = $exitCode;
        $this->termSignal = $termSignal;
    }

    public function succeeded()
    {
        return $this->exitCode === 0;
    }

    /**
     * @return int|null
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    public function getTermSignal()
    {
        return $this->termSignal;
    }

    public function getCombinedExitCode()
    {
        if ($this->exitCode === null) {
            if ($this->termSignal === null) {
                return 255;
            } else {
                return 255 + $this->termSignal;
            }
        } else {
            return $this->exitCode;
        }
    }

    public function getReason()
    {
        if ($this->exitCode === null) {
            if ($this->termSignal === null) {
                return 'Process died';
            } else {
                return 'Process got terminated with SIGNAL ' . $this->termSignal;
            }
        } else {
            if ($this->exitCode === 0) {
                return 'Process finished successfully';
            } else {
                return 'Process exited with exit code ' . $this->exitCode;
            }
        }
    }
}
