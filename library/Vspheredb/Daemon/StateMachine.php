<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
     *
     * @return $this
     */
    public function onTransition(string|array $fromState, string $toState, callable $callback): static
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

    /**
     * @param string $fromState
     * @param string $toState
     *
     * @return $this
     */
    public function allowTransition(string $fromState, string $toState): static
    {
        if (! isset($this->allowedTransitions[$fromState][$toState])) {
            $this->allowedTransitions[$fromState][$toState] = [];
        }

        return $this;
    }

    /**
     * @param string $state
     * @param callable $callback
     *
     * @return $this
     */
    public function onState(string $state, callable $callback): static
    {
        if (! isset($this->onState[$state])) {
            $this->onState[$state] = [];
        }

        $this->onState[$state][] = $callback;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        if ($this->currentState === null) {
            throw new RuntimeException('StateMachine has not been initialized');
        }

        return $this->currentState;
    }

    /**
     * @param string $state
     *
     * @return void
     */
    public function setState(string $state): void
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

    /**
     * @param string $fromState
     * @param string $toState
     *
     * @return void
     */
    private function runStateTransition(string $fromState, string $toState): void
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

    /**
     * @param string $fromState
     * @param string $toState
     *
     * @return bool
     */
    public function canTransit(string $fromState, string $toState): bool
    {
        return isset($this->allowedTransitions[$fromState][$toState]);
    }
}
