<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;
use dipl\Web\Url;
use Icinga\Authentication\Auth;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;

class AdditionalTableActions
{
    use TranslationHelper;

    /** @var Auth */
    protected $auth;

    /** @var Url */
    protected $url;

    /** @var BaseTable */
    protected $table;

    public function __construct(BaseTable $table, Auth $auth, Url $url)
    {
        $this->auth = $auth;
        $this->url = $url;
        $this->table = $table;
    }

    public function appendTo(Html $parent)
    {
        $links = [];
        if (false && $this->hasPermission('vspheredb/admin')) {
            // TODO: not yet
            // $links[] = $this->createDownloadJsonLink();
        }
        if ($this->hasPermission('vspheredb/showsql')) {
            $links[] = $this->createShowSqlToggle();
        }

        if (! empty($links)) {
            $parent->add($this->moreOptions($links));
        }

        return $this;
    }

    protected function createDownloadJsonLink()
    {
        return Link::create(
            $this->translate('Download as JSON'),
            $this->url->with('format', 'json'),
            null,
            ['target' => '_blank']
        );
    }

    protected function createShowSqlToggle()
    {
        if ($this->url->getParam('format') === 'sql') {
            $link = Link::create(
                $this->translate('Hide SQL'),
                $this->url->without('format')
            );
        } else {
            $link = Link::create(
                $this->translate('Show SQL'),
                $this->url->with('format', 'sql')
            );
        }

        return $link;
    }

    protected function moreOptions($links)
    {
        $options = $this->ul([
            $this->li([
                Link::create(Icon::create('down-open'), '#'),
                $this->linkList($links)
            ]),
        ], ['class' => 'nav']);

        return $options;
    }

    protected function linkList($links)
    {
        $ul = Html::tag('ul');

        foreach ($links as $link) {
            $ul->add($this->li($link));
        }

        return $ul;
    }

    protected function ulLi($content)
    {
        return $this->ul($this->li($content));
    }

    protected function ul($content, $attributes = null)
    {
        return Html::tag('ul', $attributes, $content);
    }

    protected function li($content)
    {
        return Html::tag('li', null, $content);
    }

    protected function hasPermission($permission)
    {
        return $this->auth->hasPermission($permission);
    }
}
