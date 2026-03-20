<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

use function preg_replace;
use function rawurlencode;

// Stolen from Icinga Director, removed module-specific logic
class Documentation
{
    use TranslationHelper;

    protected const PUBLIC_URL_MAP = [
        'vspheredb' => 'icinga-vsphere-integration',
        'director'  => 'icinga-director'
    ];

    protected ApplicationBootstrap $app;

    protected Auth $auth;

    // true links to GitHub, false to icinga.com
    protected bool $linkToGitHub = false;

    public function __construct(ApplicationBootstrap $app, Auth $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
    }

    /**
     * @param string $label
     * @param string $module
     * @param string $chapter
     * @param ?string $title
     *
     * @return Link|HtmlElement
     */
    public static function link(string $label, string $module, string $chapter, ?string $title = null): Link|HtmlElement
    {
        return (new static(Icinga::app(), Auth::getInstance()))->getModuleLink($label, $module, $chapter, $title);
    }

    /**
     * @param string $label
     * @param string $module
     * @param string $chapter
     * @param ?string $title
     *
     * @return Link|HtmlElement
     */
    public function getModuleLink(
        string $label,
        string $module,
        string $chapter,
        ?string $title = null
    ): Link|HtmlElement {
        if ($title !== null) {
            $title = sprintf($this->translate('Click to read our documentation: %s'), $title);
        }
        $baseParams = ['class' => 'icon-book', 'title' => $title];
        if ($this->hasAccessToDocumentationModule()) {
            return Link::create(
                $label,
                $this->getModuleDocumentationUrl($module, $chapter),
                null,
                ['data-base-target' => '_next'] + $baseParams
            );
        }

        $baseParams = ['target' => '_blank', 'rel' => 'noreferrer'];
        if ($this->linkToGitHub || ! isset(self::PUBLIC_URL_MAP[$module])) {
            return Html::tag('a', ['href' => $this->githubDocumentationUrl($module, $chapter)] + $baseParams, $label);
        }

        return Html::tag(
            'a',
            ['href' => $this->icingaDocumentationUrl(self::PUBLIC_URL_MAP[$module], $chapter)] + $baseParams,
            $label
        );
    }

    protected function getModuleDocumentationUrl(string $moduleName, string $chapter): string
    {
        return sprintf(
            'doc/module/%s/chapter/%s',
            rawurlencode($moduleName),
            preg_replace('/^\d+-/', '', rawurlencode($chapter))
        );
    }

    protected function githubDocumentationUrl(string $module, string $chapter): string
    {
        return sprintf(
            "https://github.com/Icinga/icingaweb2-module-%s/blob/master/doc/%s.md",
            rawurlencode($module),
            rawurlencode($chapter)
        );
    }

    protected function icingaDocumentationUrl(string $module, string $chapter): string
    {
        return sprintf(
            'https://icinga.com/docs/%s/latest/doc/%s/',
            rawurlencode($module),
            rawurlencode($chapter)
        );
    }

    protected function hasAccessToDocumentationModule(): bool
    {
        return $this->app->getModuleManager()->hasLoaded('doc') && $this->auth->hasPermission('module/doc');
    }
}
