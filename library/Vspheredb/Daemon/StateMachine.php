<?php

namespace Icinga\Module\Vspheredb\Daemon;

use RuntimeException;

trait StateMachine
{
    /** @var string */
    private $currentState;

    /** @var array [fromState][toState] = [callback, ...] */
    private $allowedTransitions = [];

    /** @var array [state] = [callback, ...] */
    private $onState = [];

    public function initializeStateMachine($initialState)
    {
        if ($this->currentState !== null) {
            throw new RuntimeException('StateMachine has already been initialized');
        }
        $this->currentState = $initialState;
    }

    /**
     * @param string|array $fromState
     * @param string $toState
     * @param callable $callback
     * @return $this
     */
    public function onTransition($fromState, $toState, $callback)
    {
        if (is_array($fromState)) {
            foreach ($fromState as $state) {
                $this->onTransition($state, $toState, $callback);
            }
        } else {
            $this->allowTransition($fromState, $toState);
            $this->allowedTransitions[$fromState][$toState][] = $callback;
        }

        return $this;
    }

    public function allowTransition($fromState, $toState)
    {
        if (! isset($this->allowedTransitions[$fromState][$toState])) {
            $this->allowedTransitions[$fromState][$toState] = [];
        }

        return $this;
    }

    /**
     * @param $state
     * @param $callback
     * @return $this
     */
    public function onState($state, $callback)
    {
        if (! isset($this->onState[$state])) {
            $this->onState[$state] = [];
        }

        $this->onState[$state][] = $callback;

        return $this;
    }

    public function getState()
    {
        if ($this->currentState === null) {
            throw new RuntimeException('StateMachine has not been initialized');
        }

        return $this->currentState;
    }

    public function setState($state)
    {
        $fromState = $this->getState();
        if ($fromState === $state && $state === self::STATE_FAILING) {
            // Still failing. Should not be triggered twice, but might happen
            return;
        }

        if ($this->canTransit($fromState, $state)) {
            $this->currentState = $state;
            $this->runStateTransition($fromState, $state);
        } else {
            throw new RuntimeException(sprintf(
                'A transition from %s to %s is not allowed',
                $fromState,
                $state
            ));
        }
    }

    private function runStateTransition($fromState, $toState)
    {
        if (isset($this->allowedTransitions[$fromState][$toState])) {
            foreach ($this->allowedTransitions[$fromState][$toState] as $callback) {
                $callback();
            }
        }
        if (isset($this->onState[$toState])) {
            foreach ($this->onState[$toState] as $callback) {
                $callback();
            }
        }
    }

    public function canTransit($fromState, $toState)
    {
        return isset($this->allowedTransitions[$fromState][$toState]);
    }
}
