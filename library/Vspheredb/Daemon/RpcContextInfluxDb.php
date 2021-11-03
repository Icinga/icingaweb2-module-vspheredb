<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Curl\CurlAsync;
use gipfl\Protocol\JsonRpc\Handler\RpcContext;
use gipfl\Protocol\JsonRpc\Handler\RpcUserInfo;
use gipfl\Protocol\JsonRpc\Request;
use gipfl\InfluxDb\InfluxDbConnectionFactory;
use gipfl\InfluxDb\InfluxDbConnectionV1;
use gipfl\InfluxDb\InfluxDbConnectionV2;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use function React\Promise\resolve;

class RpcContextInfluxDb extends RpcContext
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    protected $logger;

    protected $curl;

    public function __construct(CurlAsync $curl, LoopInterface $loop, LoggerInterface $logger, RpcUserInfo $userInfo)
    {
        $this->curl = $curl;
        $this->loop = $loop;
        $this->logger = $logger;
        parent::__construct($userInfo);
    }

    public function getNamespace()
    {
        return 'influxdb';
    }

    public function isAccessible()
    {
        return true;
    }

    /**
     * @rpcParam string $baseUrl Base URL
     * @rpcParam string $username username / organization
     * @rpcParam string $password password / token
     * @param Request $request
     */
    public function testConnectionRequest(Request $request)
    {
        return $this->factoryForRequest($request)->then(function () {
            return true;
        }, function () {
            return false;
        });
    }

    /**
     * @rpcParam string $baseUrl Base URL
     * @param Request $request
     */
    public function discoverVersionRequest(Request $request)
    {
        // $this->logger->notice('Discover ' . json_encode($request->getParams()));
        return $this->factoryForRequest($request)->then(function ($connection) {
            return $connection->getVersion();
        });
    }

    /**
     * @rpcParam string $baseUrl Base URL
     * @rpcParam string $username username / organization
     * @rpcParam string $password password / token
     * @param Request $request
     */
    public function listDatabasesRequest(Request $request)
    {
        return $this->factoryForRequest($request)->then(function ($connection) {
            /** @var InfluxDbConnectionV1|InfluxDbConnectionV2 $connection */
            return $connection->listDatabases();
        });
    }

    /**
     * @rpcParam string $baseUrl Base URL
     * @rpcParam string $username username / organization
     * @rpcParam string $password password / token
     * @rpcParam string $dbName
     * @param Request $request
     */
    public function createDatabaseRequest(Request $request)
    {
        $dbName = $request->getParam('dbName');
        return $this->factoryForRequest($request)->then(function ($connection) use ($dbName) {
            /** @var InfluxDbConnectionV1|InfluxDbConnectionV2 $connection */
            $this->logger->info("CREATING $dbName");
            return $connection->createDatabase($dbName);
        });
    }

    protected function factoryForRequest(Request $request)
    {
        $baseUrl = $request->getParam('baseUrl');

        switch ($request->getParam('apiVersion')) {
            case 'v1':
                return resolve(new InfluxDbConnectionV1(
                    $this->curl,
                    $baseUrl,
                    $request->getParam('username'),
                    $request->getParam('password')
                ));
            case 'v2':
                return resolve(new InfluxDbConnectionV2(
                    $this->curl,
                    $baseUrl,
                    $request->getParam('org'),
                    $request->getParam('token')
                ));
        }

        return InfluxDbConnectionFactory::create(
            $this->curl,
            $baseUrl,
            $request->getParam('username', $request->getParam('org')),
            $request->getParam('password', $request->getParam('token'))
        );
    }
}
