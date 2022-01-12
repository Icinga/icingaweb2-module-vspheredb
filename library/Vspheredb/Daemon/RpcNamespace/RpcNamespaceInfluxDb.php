<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Curl\CurlAsync;
use gipfl\InfluxDb\InfluxDbConnection;
use gipfl\InfluxDb\InfluxDbConnectionFactory;
use gipfl\InfluxDb\InfluxDbConnectionV1;
use gipfl\InfluxDb\InfluxDbConnectionV2;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use function React\Promise\resolve;

class RpcNamespaceInfluxDb
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    protected $logger;

    protected $curl;

    public function __construct(CurlAsync $curl, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->loop = $loop;
        $this->logger = $logger;
    }

    /**
     * @param string $baseUrl Base URL
     */
    public function discoverVersionRequest($baseUrl)
    {
        return $this->connect($baseUrl)->then(function ($connection) {
            return $connection->getVersion();
        });
    }

    /**
     * @param string $baseUrl Base URL
     * @param string $apiVersion v1/v2
     * @param string $username username / organization
     * @param string $password password / token
     */
    public function testConnectionRequest($baseUrl, $apiVersion = null, $username = null, $password = null)
    {
        return $this->listDatabasesRequest($baseUrl, $apiVersion, $username, $password)->then(function () {
            return true;
        });
    }

    /**
     * @param string $baseUrl Base URL
     * @param string $apiVersion v1/v2
     * @param string $username username / organization
     * @param string $password password / token
     */
    public function listDatabasesRequest($baseUrl, $apiVersion = null, $username = null, $password = null)
    {
        return $this->connect($baseUrl, $apiVersion, $username, $password)->then(function ($connection) {
            /** @var InfluxDbConnectionV1|InfluxDbConnectionV2 $connection */
            return $connection->listDatabases();
        });
    }

    /**
     * @param string $dbName
     * @param string $baseUrl Base URL
     * @param string $apiVersion v1/v2
     * @param string $username username / organization
     * @param string $password password / token
     */
    public function createDatabaseRequest($dbName, $baseUrl, $apiVersion, $username, $password)
    {
        return $this->connect($baseUrl, $apiVersion, $username, $password)->then(function ($connection) use ($dbName) {
            /** @var InfluxDbConnection $connection */
            $this->logger->info("CREATING $dbName");
            return $connection->createDatabase($dbName);
        });
    }

    protected function connect($baseUrl, $apiVersion = null, $username = null, $password = null)
    {
        switch ($apiVersion) {
            case 'v1':
                return resolve(new InfluxDbConnectionV1($this->curl, $baseUrl, $username, $password));
            case 'v2':
                return resolve(new InfluxDbConnectionV2($this->curl, $baseUrl, $username, $password));
        }

        return InfluxDbConnectionFactory::create($this->curl, $baseUrl, $username, $password);
    }
}
