<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use Zend_Db_Select as DbSelect;

abstract class ToggleFlagList extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'li';

    /** @var Url */
    private $url;

    /** @var string */
    private $param;

    /** @var DbSelect|null */
    private $originalQuery;

    /** @var DbSelect|null */
    private $query;

    protected $iconMain = 'angle-double-down';

    protected $iconModified = 'flapping';

    public function __construct(Url $url, $param)
    {
        $this->url = $url;
        $this->param = $param;
    }

    public function applyToQuery(DbSelect $query)
    {
        $this->originalQuery = $query;
        $this->query = clone $query;

        return $this;
    }

    abstract protected function getListLabel();

    abstract protected function getOptions();

    protected function getDefaultSelection()
    {
        return \array_keys($this->getOptions());
    }

    protected function setEnabled($enabled, $all)
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

    protected function assemble()
    {
        $link = Link::create($this->getListLabel(), '#', null, ['class' => 'icon-' . $this->iconMain]);
        $this->add([
            $link,
            $this->createLinkList($this->toggleColumnsOptions($link))
        ]);
    }

    protected function toggleColumnsOptions(Link $mainLink)
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
            if (\in_array($option, $enabled)) {
                $urlOptions = \array_diff($enabled, [$option]);
                $icon = 'check';
                $title = $this->translate('Click to hide');
            } else {
                $disabled[] = $option;
                $urlOptions = \array_merge($enabled, [$option]);
                $icon = 'plus';
                $title = $this->translate('Click to show');
            }
            $links[] = Link::create($label, $this->getUrlWithOptions($urlOptions), null, [
                'class' => "icon-$icon",
                'title' => $title,
            ]);
        }
        if (! empty($disabled) && $all !== $default) {
            \array_unshift($links, Link::create(
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

    protected function geturlReset()
    {
        return Link::create(
            $this->translate('Reset'),
            $this->url->without($this->param),
            null,
            ['class' => 'icon-reply reset-action']
        );
    }

    protected function getUrlWithOptions($options)
    {
        return $this->url->with($this->param, $this->joinUrlOptions($options));
    }

    protected function joinUrlOptions($value)
    {
        return \implode(',', $value);
    }

    protected function splitUrlOptions($value)
    {
        return \preg_split('/,/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    protected function createLinkList($links)
    {
        $ul = Html::tag('ul');

        foreach ($links as $link) {
            $ul->add(Html::tag('li', $link));
        }

        return $ul;
    }
}
