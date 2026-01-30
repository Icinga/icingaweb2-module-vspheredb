<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Curl\CurlAsync;
use gipfl\InfluxDb\InfluxDbConnection;
use gipfl\InfluxDb\InfluxDbConnectionFactory;
use gipfl\InfluxDb\InfluxDbConnectionV1;
use gipfl\InfluxDb\InfluxDbConnectionV2;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

class RpcNamespaceInfluxDb
{
    /** @var LoopInterface */
    protected LoopInterface $loop;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var CurlAsync */
    protected CurlAsync $curl;

    /**
     * @param CurlAsync       $curl
     * @param LoopInterface   $loop
     * @param LoggerInterface $logger
     */
    public function __construct(CurlAsync $curl, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->loop = $loop;
        $this->logger = $logger;
    }

    /**
     * @param string $baseUrl Base URL
     *
     * @return PromiseInterface
     */
    public function discoverVersionRequest(string $baseUrl): PromiseInterface
    {
        return $this->connect($baseUrl)->then(function ($connection) {
            return $connection->getVersion();
        });
    }

    /**
     * @param string      $baseUrl    Base URL
     * @param string|null $apiVersion v1/v2
     * @param string|null $username   username / organization
     * @param string|null $password   password / token
     *
     * @return PromiseInterface
     */
    public function testConnectionRequest(
        string $baseUrl,
        ?string $apiVersion = null,
        ?string $username = null,
        ?string $password = null
    ): PromiseInterface {
        return $this->listDatabasesRequest($baseUrl, $apiVersion, $username, $password)->then(function () {
            return true;
        });
    }

    /**
     * @param string      $baseUrl    Base URL
     * @param string|null $apiVersion v1/v2
     * @param string|null $username   username / organization
     * @param string|null $password   password / token
     *
     * @return PromiseInterface
     */
    public function listDatabasesRequest(
        string $baseUrl,
        ?string $apiVersion = null,
        ?string $username = null,
        ?string $password = null
    ): PromiseInterface {
        return $this->connect($baseUrl, $apiVersion, $username, $password)->then(function ($connection) {
            /** @var InfluxDbConnectionV1|InfluxDbConnectionV2 $connection */
            return $connection->listDatabases();
        });
    }

    /**
     * @param string $dbName
     * @param string $baseUrl    Base URL
     * @param string $apiVersion v1/v2
     * @param string $username   username / organization
     * @param string $password   password / token
     *
     * @return PromiseInterface
     */
    public function createDatabaseRequest(
        string $dbName,
        string $baseUrl,
        string $apiVersion,
        string $username,
        string $password
    ): PromiseInterface {
        return $this->connect($baseUrl, $apiVersion, $username, $password)->then(function ($connection) use ($dbName) {
            /** @var InfluxDbConnection $connection */
            $this->logger->info("CREATING $dbName");

            return $connection->createDatabase($dbName);
        });
    }

    /**
     * @param string      $baseUrl
     * @param string|null $apiVersion
     * @param string|null $username
     * @param string|null $password
     *
     * @return PromiseInterface
     */
    protected function connect(
        string $baseUrl,
        ?string $apiVersion = null,
        ?string $username = null,
        ?string $password = null
    ): PromiseInterface {
        switch ($apiVersion) {
            case 'v1':
                return resolve(new InfluxDbConnectionV1($this->curl, $baseUrl, $username, $password));
            case 'v2':
                return resolve(new InfluxDbConnectionV2($this->curl, $baseUrl, $username, $password));
        }

        return InfluxDbConnectionFactory::create($this->curl, $baseUrl, $username, $password);
    }
}
