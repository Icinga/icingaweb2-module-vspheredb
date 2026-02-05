<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Enum;

use InvalidArgumentException;
use RuntimeException;

enum CheckPluginState: int
{
    case OK = 0;
    case WARNING = 1;
    case CRITICAL = 2;
    case UNKNOWN = 3;

    public function sortValue(): int
    {
        return match ($this) {
            self::OK       => 0,
            self::WARNING  => 1,
            self::CRITICAL => 3,
            self::UNKNOWN  => 2
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OK       => 'green',
            self::WARNING  => 'brown',
            self::CRITICAL => 'red',
            self::UNKNOWN  => 'purple'
        };
    }

    public static function fromName(string $name): self
    {
        foreach (CheckPluginState::cases() as $case) {
            if ($case->name === strtoupper($name)) {
                return $case;
            }
        }

        throw new InvalidArgumentException("$name is not a valid state name");
    }

    /**
     * @param CheckPluginState $state
     *
     * @return self
     */
    public function raise(CheckPluginState $state): self
    {
        if ($state->sortValue() > self::sortValue()) {
            return $state;
        }

        return $this;
    }

    public function isProblem(): bool
    {
        return $this !== CheckPluginState::OK;
    }

    public static function compare(CheckPluginState $left, CheckPluginState $right): int
    {
        return $left->sortValue() <=> $right->sortValue();
    }

    public static function getBest(CheckPluginState ...$states): CheckPluginState
    {
        $formerState = array_shift($states);
        if ($formerState === null) {
            throw new RuntimeException('Comparing an empty state list is not possible');
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
            throw new RuntimeException('Comparing an empty state list is not possible');
        }
        while ($state = array_shift($states)) {
            if (self::compare($formerState, $state) === -1) {
                $formerState = $state;
            }
        }

        return $formerState;
    }

    public function getExitCode(): int
    {
        return $this->value;
    }
}
