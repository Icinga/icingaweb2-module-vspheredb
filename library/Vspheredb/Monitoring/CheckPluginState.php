<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use InvalidArgumentException;

class CheckPluginState
{
    public const OK = 0;
    public const WARNING = 1;
    public const CRITICAL = 2;
    public const UNKNOWN = 3;

    public const NAME_OK = 'OK';
    public const NAME_WARNING = 'WARNING';
    public const NAME_CRITICAL = 'CRITICAL';
    public const NAME_UNKNOWN = 'UNKNOWN';

    public const SORT_MAP = [
        self::OK       => 0,
        self::WARNING  => 1,
        self::UNKNOWN  => 2,
        self::CRITICAL => 3
    ];

    public const NAME_STATE_MAP = [
        self::NAME_OK       => self::OK,
        self::NAME_WARNING  => self::WARNING,
        self::NAME_CRITICAL => self::CRITICAL,
        self::NAME_UNKNOWN  => self::UNKNOWN,
    ];
    public const STATE_NAME_MAP = [
        self::OK       => self::NAME_OK,
        self::WARNING  => self::NAME_WARNING,
        self::CRITICAL => self::NAME_CRITICAL,
        self::UNKNOWN  => self::NAME_UNKNOWN,
    ];

    public const STATE_COLORS = [
        self::OK      => 'green',
        self::WARNING => 'brown',
        self::CRITICAL => 'red',
        self::UNKNOWN => 'purple',
    ];

    /** @var int */
    protected $state = 0;

    public function __construct($state = self::OK)
    {
        $this->setState($state);
    }

    public function setState($state)
    {
        $this->state = self::wantNumericState($state);
    }

    /**
     * @param CheckPluginState|int|string $state
     */
    public function raiseState($state)
    {
        if ($state instanceof CheckPluginState) {
            $state = $state->getState();
        } else {
            $state = self::wantNumericState($state);
        }
        if (self::SORT_MAP[$state] > self::SORT_MAP[$this->getState()]) {
            $this->state = $state;
        }
    }

    public function isProblem(): bool
    {
        return $this->getState() !== 0;
    }

    public static function compare(CheckPluginState $left, CheckPluginState $right): int
    {
        $left = self::SORT_MAP[$left->getState()];
        $right = self::SORT_MAP[$right->getState()];
        return $left === $right ? 0 : ($left < $right ? -1 : 1);
    }

    public static function getBest(CheckPluginState ...$states): CheckPluginState
    {
        $formerState = array_shift($states);
        if ($formerState === null) {
            throw new \RuntimeException('Comparing an empty state list is not possible');
        }
        while ($state = array_shift($states)) {
            if (self::compare($formerState, $state) === 1) {
                $formerState = $state;
            }
        }

        return $formerState;
    }

    public static function getWorst(CheckPluginState ...$states): CheckPluginState
    {
        $formerState = array_shift($states);
        if ($formerState === null) {
            throw new \RuntimeException('Comparing an empty state list is not possible');
        }
        while ($state = array_shift($states)) {
            if (self::compare($formerState, $state) === -1) {
                $formerState = $state;
            }
        }

        return $formerState;
    }

    public function getName(): string
    {
        return self::STATE_NAME_MAP[self::wantNumericState($this->getState())];
    }

    public function getExitCode(): int
    {
        return $this->state;
    }

    public function getColor(): string
    {
        return self::STATE_COLORS[$this->state];
    }

    protected function getState(): int
    {
        return $this->state;
    }

    /**
     * @param string|int $state
     * @return int
     */
    protected static function wantNumericState($state): int
    {
        if (is_int($state) || ctype_digit($state)) {
            if (array_key_exists($state, self::STATE_NAME_MAP)) {
                return (int) $state;
            } else {
                throw new InvalidArgumentException(sprintf('%d is not a valid numeric state', $state));
            }
        } else {
            $state = strtoupper($state);
            if (array_key_exists($state, self::NAME_STATE_MAP)) {
                return self::NAME_STATE_MAP[$state];
            } else {
                throw new InvalidArgumentException(sprintf('%s is not a valid state name', $state));
            }
        }
    }
}
