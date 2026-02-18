<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use Zend_Db_Select as DbSelect;

abstract class ToggleFlagList extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'li';

    /** @var Url */
    private Url $url;

    /** @var string */
    private string $param;

    /** @var ?DbSelect */
    private ?DbSelect $originalQuery = null;

    /** @var ?DbSelect */
    private ?DbSelect $query = null;

    protected string $iconMain = 'angle-double-down';

    protected string $iconModified = 'flapping';

    public function __construct(Url $url, string $param)
    {
        $this->url = $url;
        $this->param = $param;
    }

    public function applyToQuery(DbSelect $query): static
    {
        $this->originalQuery = $query;
        $this->query = clone $query;

        return $this;
    }

    abstract protected function getListLabel(): string;

    abstract protected function getOptions(): array;

    protected function getDefaultSelection(): array
    {
        return array_keys($this->getOptions());
    }

    protected function setEnabled(array $enabled, array $all): void
    {
        if ($all === $enabled) {
            // No need to extend the query with useless overhead
            return;
        }
        // You might want to override this method
        if ($this->originalQuery) {
            if (empty($enabled)) {
                $this->originalQuery->where('1 = 0');
            } else {
                $this->originalQuery->where(
                    $this->param . ' IN (?)',
                    $enabled
                );
            }
        }
    }

    protected function assemble(): void
    {
        $link = Link::create($this->getListLabel(), '#', null, ['class' => 'icon-' . $this->iconMain]);
        $this->add([
            $link,
            $this->createLinkList($this->toggleColumnsOptions($link))
        ]);
    }

    protected function toggleColumnsOptions(Link $mainLink): array
    {
        $default = $this->getDefaultSelection();
        $links = [];
        $url = $this->url;
        $param = $this->param;

        $enabled = $url->getParam($param);
        if ($enabled === null) {
            $enabled = $default;
        } else {
            $mainLink->getAttributes()->set(
                'class',
                'modified icon-' . $this->iconModified
            );
            $links[] = $this->geturlReset();
            $enabled = $this->splitUrlOptions($enabled);
        }

        $all = [];
        $disabled = [];
        foreach ($this->getOptions() as $option => $label) {
            $all[] = $option;
            if (in_array($option, $enabled)) {
                $urlOptions = array_diff($enabled, [$option]);
                $icon = 'check';
                $title = $this->translate('Click to hide');
            } else {
                $disabled[] = $option;
                $urlOptions = array_merge($enabled, [$option]);
                $icon = 'plus';
                $title = $this->translate('Click to show');
            }
            $links[] = Link::create($label, $this->getUrlWithOptions($urlOptions), null, [
                'class' => "icon-$icon",
                'title' => $title,
            ]);
        }
        if (! empty($disabled) && $all !== $default) {
            array_unshift($links, Link::create(
                $this->translate('All'),
                $url->with($param, $this->joinUrlOptions($all)),
                null,
                [
                    'class' => 'icon-resize-horizontal',
                    // TODO: this is helpful with tables with many columns,
                    // but irritating otherwise:
                    // 'data-base-target' => '_main'
                ]
            ));
        }
        $this->setEnabled($enabled, $all);

        return $links;
    }

    protected function geturlReset(): Link
    {
        return Link::create(
            $this->translate('Reset'),
            $this->url->without($this->param),
            null,
            ['class' => 'icon-reply reset-action']
        );
    }

    protected function getUrlWithOptions($options): Url
    {
        return $this->url->with($this->param, $this->joinUrlOptions($options));
    }

    protected function joinUrlOptions($value): string
    {
        return implode(',', $value);
    }

    protected function splitUrlOptions($value): array
    {
        return preg_split('/,/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    protected function createLinkList($links): HtmlElement
    {
        $ul = Html::tag('ul');

        foreach ($links as $link) {
            $ul->add(Html::tag('li', $link));
        }

        return $ul;
    }
}
