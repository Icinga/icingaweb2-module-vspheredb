<?php

namespace Icinga\Module\Vspheredb\Polling;

use Psr\Log\LoggerInterface;

use function file;
use function file_exists;
use function file_put_contents;
use function implode;
use function unlink;

class CookieStore
{
    /** @var string */
    private $cacheDir;

    /** @var string */
    private $cookieFile;

    /** @var array */
    private $cookies = [];

    /** @var LoggerInterface */
    private $logger;

    public function __construct($cacheDir, ServerInfo $serverInfo, LoggerInterface $logger)
    {
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
        $this->cookieFile = $this->cacheDir . "/cookie-" . $serverInfo->get('id');
        if (file_exists($this->cookieFile)) {
            $this->cookies = file($this->cookieFile);
        }
    }

    /**
     * @return bool
     */
    public function hasCookies()
    {
        return !empty($this->cookies);
    }

    public function setCookies(array $cookies)
    {
        if ($cookies !== $this->cookies) {
            $this->logger->notice('Cookies changed, storing new ones');
            $this->cookies = $cookies;
            file_put_contents($this->cookieFile, implode("\n", $cookies));
        }
    }

    /**
     * Discard our Cookie
     */
    public function forgetCookies()
    {
        $this->cookies = [];
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function getCookies()
    {
        return $this->cookies;
    }
}
