<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Closure;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Compat\StyleWithNonce;

class UsageBar extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'resource-usage'];

    protected array $colors = ['used' => 'rgba(0, 149, 191, 0.75)'];

    /** @var int|float|null */
    protected int|float|null $used;

    /** @var int|float|null */
    protected int|float|null $capacity;

    protected ?Closure $formatter = null;

    protected bool $showLabels = true;

    public function __construct(int|float|null $used, int|float|null $capacity)
    {
        $this->used = $used;
        $this->capacity = $capacity;
    }

    /**
     * @param float|int $percent
     * @param string $title
     * @param string $color
     *
     * @return array
     */
    protected function makeSegment(float|int $percent, string $title, string $color = 'used'): array
    {
        if (isset($this->colors[$color])) {
            $color = $this->colors[$color];
        }

        $usage = Html::tag('div', ['class' => 'usage', 'title' => $title]);

        $style = (new StyleWithNonce())
            ->setModule('vspheredb')
            ->addFor($usage, [
                'width'            => sprintf('%0.3F%%', $percent * 100),
                'background-color' => $color
            ]);

        return [$usage, $style];
    }

    public function setFormatter($callback): static
    {
        $this->formatter = $callback;

        return $this;
    }

    public function showLabels($show = true): static
    {
        $this->showLabels = (bool) $show;
        return $this;
    }

    protected function format($value)
    {
        if ($this->formatter === null) {
            return $value;
        }

        return ($this->formatter)($value);
    }

    protected function getTitleUsed(): string
    {
        return sprintf(
            $this->translate('Used: %s of %s (%.2F%%)'),
            $this->format($this->used),
            $this->format($this->capacity),
            $this->used / $this->capacity * 100
        );
    }

    protected function getLabelUsed(): string
    {
        return sprintf($this->translate('%s used'), $this->format($this->used));
    }

    protected function getLabelCapacity(): string
    {
        return $this->translate('Capacity') . ': ' . $this->format($this->capacity);
    }

    protected function assembleBar(BaseHtmlElement $bar): void
    {
        if ($this->capacity !== null && $this->capacity !== 0) {
            $bar->add($this->makeSegment($this->used / $this->capacity, $this->getTitleUsed()));
        }
    }

    protected function assemble(): void
    {
        $usage = Html::tag('div', ['class' => 'usage-bar', 'data-base-target' => '_next']);
        $this->assembleBar($usage);
        $this->add($usage);
        if ($this->showLabels) {
            $this->addLabels();
        }
    }

    protected function addLabels(): void
    {
        $this->add([
            Html::tag('span', ['class' => 'usage-used'], $this->getLabelUsed()),
            ' ',
            Html::tag('span', ['class' => 'usage-capacity'], $this->getLabelCapacity())
        ]);
    }
}
