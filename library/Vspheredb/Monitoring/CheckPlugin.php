<?php

namespace Icinga\Module\Vspheredb\Monitoring;

class CheckPlugin
{
    /** @var array */
    protected $messages = [];

    /** @var string|null */
    protected $message;

    /**
     * @param $callable
     */
    protected function run($callable)
    {
        /** @var Command $this */
        $this->loop()->futureTick(function () use ($callable) {
            $result = null;
            if (\is_callable($callable)) {
                try {
                    $result = $callable();
                } catch (Exception $e) {
                    $this->addProblem('UNKNOWN', $this->stripNonUtf8Characters($e->getMessage()));
                } catch (\Throwable $e) {
                    $this->addProblem('UNKNOWN', $this->stripNonUtf8Characters($e->getMessage()));
                }
            } else {
                $this->addProblem('UNKNOWN', 'CheckPluginHelper requires a "callable"');
            }

            if ($result instanceof PromiseInterface) {
                $result->then(function () {
                    echo "as\n";
                }, function (Exception $e) {
                    var_dump('whut');
                    $this->addProblem('UNKNOWN', $e->getMessage());
                })->always(function () {
                    var_dump('Shut after res');
                    $this->shutdown();
                });
            } else {
                $this->shutdown();
            }
        });
        $this->eventuallyStartMainLoop();
    }

    /**
     * @param string $string
     * @return string
     */
    protected function stripNonUtf8Characters($string)
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', $string);
    }

    /**
     * @param null $state
     * @return mixed
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
     */
    protected function addProblem($state, $message)
    {
        $this->raiseState($state);
        $stateName = $this->getStateName($state);
        $this->addMessage(sprintf(
            '%s %s',
            $this->getOutputScreen()->colorize("[$stateName]", $this->stateColors[$stateName]),
            $message
        ));

        return $this;
    }

    protected function getOutputScreen()
    {
        if ($this->outputScreen === null) {
            $this->outputScreen = Screen::factory();
        }

        return $this->outputScreen;
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
     */
    protected function wantNumericState($state)
    {
        if (is_int($state) || ctype_digit($state)) {
            if (array_key_exists($state, $this->stateNameMap)) {
                return (int) $state;
            } else {
                throw new InvalidArgumentException(sprintf('%d is not a valid numeric state', $state));
            }
        } else {
            if (array_key_exists($state, $this->nameStateMap)) {
                return $this->nameStateMap[$state];
            } else {
                throw new InvalidArgumentException(sprintf('%s is not a valid state name', $state));
            }
        }
    }

    protected function getMessages()
    {
        return $this->messages;
    }

    protected function shutdown()
    {
        $messages = $this->getMessages();
        if (! empty($messages)) {
            echo implode("\n", $messages) . "\n";
        }
        $this->loop()->addTimer(0.01, function () {
            $this->loop()->stop();
            exit($this->getState());
        });
    }
}
