<?php

namespace Icinga\Module\Vspheredb;

use Error;
use Exception;
use Icinga\Exception\ProgrammingError;

trait CheckPluginHelper
{
    /** @var int */
    protected $state = 0;

    protected $sortingState;

    protected $sortingStateMap = [0, 1, 3, 2];

    /** @var array */
    protected $nameStateMap = [
        'OK'       => 0,
        'WARNING'  => 1,
        'CRITICAL' => 2,
        'UNKNOWN'  => 3,
    ];

    /** @var array */
    protected $stateNameMap = [
        'OK',
        'WARNING',
        'CRITICAL',
        'UNKNOWN',
    ];

    /** @var array */
    protected $messages = [];

    /** @var string|null */
    protected $message;

    /**
     * @param $callable
     */
    protected function run($callable)
    {
        if (is_callable($callable)) {
            try {
                $callable();
            } catch (Exception $e) {
                $this->addProblem('UNKNOWN', $e->getMessage());

            } catch (Error $e) {
                $this->addProblem('UNKNOWN', $e->getMessage());
            }
        } else {
            $this->addProblem('UNKNOWN', 'CheckPluginHelper requires a "callable"');
        }

        $this->shutdown();
    }

    /**
     * @param null $state
     * @return mixed
     * @throws ProgrammingError
     */
    protected function getStateName($state = null)
    {
        if ($state === null) {
            return $this->stateNameMap[$this->state];
        } else {
            return $this->stateNameMap[$this->wantNumericState($state)];
        }
    }

    /**
     * @param int|string $state
     * @param string $message
     * @return $this
     * @throws ProgrammingError
     */
    protected function addProblem($state, $message)
    {
        $this->raiseState($state);
        $stateName = $this->getStateName($state);
        $this->addMessage("[$stateName] $message");

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    protected function addMessage($message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    protected function prependMessage($message)
    {
        array_unshift($this->messages, $message);

        return $this;
    }

    /**
     * @param int|string $state
     * @return $this
     * @throws ProgrammingError
     */
    protected function raiseState($state)
    {
        $state = $this->wantNumericState($state);
        if ($this->sortingStateMap[$state] > $this->sortingStateMap[$this->state]) {
            $this->state = $state;
        }

        return $this;
    }

    /**
     * @return int
     */
    protected function getState()
    {
        return $this->state;
    }

    /**
     * @param $state
     * @return int
     * @throws ProgrammingError
     */
    protected function wantNumericState($state)
    {
        if (is_int($state) || ctype_digit($state)) {
            if (array_key_exists($state, $this->stateNameMap)) {
                return (int) $state;
            } else {
                throw new ProgrammingError('%d is not a valid numeric state', $state);
            }
        } else {
            if (array_key_exists($state, $this->nameStateMap)) {
                return $this->nameStateMap[$state];
            } else {
                throw new ProgrammingError('%s is not a valid state name', $state);
            }
        }
    }

    protected function getMessages()
    {
        return $this->messages;
    }

    protected function shutdown()
    {
        echo implode("\n", $this->getMessages()) . "\n";
        exit($this->getState());
    }
}
