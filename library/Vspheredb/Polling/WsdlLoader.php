<?php

namespace Icinga\Module\Vspheredb\Polling;

use Exception;
use gipfl\Curl\CurlAsync;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;
use SoapClient;
use SoapFault;

use function file_exists;
use function file_put_contents;
use function React\Promise\reject;
use function unlink;

class WsdlLoader
{
    /**
     * Involved WSDL files
     *
     * We'll always fetch and store them in case they are not available. Pay
     * attention when modifying this list, we'll use the first one to start
     * with when connecting to the SOAP API
     *
     * @var array
     */
    protected $requiredFiles = [
        'vimService.wsdl',
        'vim.wsdl',
        'core-types.xsd',
        'query-types.xsd',
        'query-messagetypes.xsd',
        'reflect-types.xsd',
        'reflect-messagetypes.xsd',
        'vim-types.xsd',
        'vim-messagetypes.xsd',
    ];

    protected $logger;

    /** @var LoopInterface */
    protected $loop;

    protected $cacheDir;

    protected $curl;

    protected $serverInfo;

    protected $baseUrl;

    /** @var ExtendedPromiseInterface[] */
    protected $pending = [];

    /** @var ?Deferred */
    protected $deferred;

    public function __construct($cacheDir, LoggerInterface $logger, ServerInfo $server, CurlAsync $curl)
    {
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
        $this->serverInfo = $server;
        $this->curl = $curl;
        $this->baseUrl = $server->getUrl();
    }

    public function fetchInitialWsdlFile(LoopInterface $loop)
    {
        $this->loop = $loop;
        return $this->fetchFiles()->then(function () {
            return $this->getInitialFilename();
        });
    }

    protected function getInitialFilename()
    {
        return $this->cacheDir . '/' . $this->requiredFiles[0];
    }

    public function stop()
    {
        if ($this->deferred) {
            $deferred = $this->deferred;
            $pending = $this->pending;
            $this->deferred = null;
            $this->pending = [];
            $deferred->reject();
            foreach ($pending as $promise) {
                $promise->cancel();
            }
        }
    }

    public function flushWsdlCache()
    {
        $dir = $this->cacheDir;
        $unlinked = false;
        foreach ($this->requiredFiles as $file) {
            if (file_exists("$dir/$file")) {
                unlink("$dir/$file");
                $unlinked = true;
            }
        }
        if ($unlinked) {
            $this->logger->notice("Flushed WSDL Cache in $dir");
        }
    }

    protected function processFileResult(ResponseInterface $response, $file)
    {
        // Ignore unwanted delayed responses
        if (isset($this->pending[$file])) {
            $this->logger->info("Loaded sdk/$file");
            file_put_contents($this->cacheDir . "/$file", $response->getBody());
            unset($this->pending[$file]);
            $this->resolveIfReady();
        }
    }

    protected function processFileFailure(Exception $e, $file)
    {
        if (isset($this->pending[$file])) {
            $logUrl = $this->url($file);
            $this->logger->error("Loading $logUrl failed: " . $e->getMessage());
            if (count($this->pending) > 1) {
                $this->logger->debug(sprintf('Stopping %d pending requests', count($this->pending) - 1));
            }
            $this->pending = []; // TODO: is there a way to stop pending requests?
            $this->flushWsdlCache();
            $this->deferred->reject(new Exception('Loading WSDL files failed'));
        }
    }

    protected function fetchFiles()
    {
        if ($this->deferred) {
            $this->logger->notice('Calling WsdlLoader::fetchFiles while already loading');
            return $this->deferred->promise();
        }
        $this->deferred = $deferred = new Deferred();
        $this->pending = [];
        $curl = $this->curl;
        $dir = $this->cacheDir;
        foreach ($this->requiredFiles as $file) {
            if (! file_exists("$dir/$file")) {
                $this->logger->debug("Fetching $file");
                $this->pending[$file] = $curl
                    ->get($this->url($file), [], CurlOptions::forServerInfo($this->serverInfo))
                    ->then(function (ResponseInterface $response) use ($file) {
                        $status = $response->getStatusCode();
                        if ($status > 199 && $status <= 299) {
                            $this->processFileResult($response, $file);
                        } else {
                            $this->processFileFailure(new Exception($response->getReasonPhrase()), $file);
                        }
                    }, function (Exception $e) use ($file) {
                        $this->processFileFailure($e, $file);
                    });
            }
        }

        $this->resolveIfReady();

        return $deferred->promise();
    }

    protected function resolveIfReady()
    {
        if (empty($this->pending)) {
            $deferred = $this->deferred;
            $this->deferred = null;
            $this->loop->futureTick(function () use ($deferred) {
                try {
                    new SoapClient($this->getInitialFilename());
                    $deferred->resolve();
                } catch (SoapFault $e) {
                    $this->flushWsdlCache();
                    $deferred->reject($e);
                }
            });
        }
    }

    protected function url($file)
    {
        return $this->baseUrl . "/sdk/$file";
    }
}
