<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use ipl\Html\Html;

use function preg_replace;
use function rawurlencode;

// Stolen from Icinga Director, removed module-specific logic
class Documentation
{
    use TranslationHelper;

    /** @var ApplicationBootstrap */
    protected $app;

    /** @var Auth */
    protected $auth;

    /** @var bool true links to GitHub, false to icinga.com */
    protected $linkToGitHub = false;

    public function __construct(ApplicationBootstrap $app, Auth $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
    }

    public static function link($label, $module, $chapter, $title = null)
    {
        $doc = new static(Icinga::app(), Auth::getInstance());
        return $doc->getModuleLink($label, $module, $chapter, $title);
    }

    public function getModuleLink($label, $module, $chapter, $title = null)
    {
        if ($title !== null) {
            $title = sprintf(
                $this->translate('Click to read our documentation: %s'),
                $title
            );
        }
        $baseParams = [
            'class' => 'icon-book',
            'title' => $title,
        ];
        if ($this->hasAccessToDocumentationModule()) {
            return Link::create(
                $label,
                $this->getModuleDocumentationUrl($module, $chapter),
                null,
                ['data-base-target' => '_next'] + $baseParams
            );
        }

        $baseParams['target'] = '_blank';
        if ($this->linkToGitHub) {
            return Html::tag('a', [
                'href' => $this->githubDocumentationUrl($module, $chapter),
            ] + $baseParams, $label);
        }

        return Html::tag('a', [
            'href' => $this->icingaDocumentationUrl($module, $chapter),
        ] + $baseParams, $label);
    }

    protected function getModuleDocumentationUrl($moduleName, $chapter): string
    {
        return sprintf(
            'doc/module/%s/chapter/%s',
            rawurlencode($moduleName),
            preg_replace('/^\d+-/', '', rawurlencode($chapter))
        );
    }

    protected function githubDocumentationUrl($module, $chapter): string
    {
        return sprintf(
            "https://github.com/Icinga/icingaweb2-module-%s/blob/master/doc/%s.md",
            rawurlencode($module),
            rawurlencode($chapter)
        );
    }

    protected function icingaDocumentationUrl($module, $chapter): string
    {
        return sprintf(
            'https://icinga.com/docs/%s/latest/doc/%s/',
            rawurlencode($module),
            rawurlencode($chapter)
        );
    }

    protected function hasAccessToDocumentationModule(): bool
    {
        return $this->app->getModuleManager()->hasLoaded('doc')
            && $this->auth->hasPermission('module/doc');
    }
}
