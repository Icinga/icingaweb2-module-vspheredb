<?php

namespace Icinga\Module\Vspheredb\Polling;

use DateTime;
use Exception;
use gipfl\Curl\CurlAsync;
use gipfl\Curl\RequestError;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Api\SoapClient;
use Icinga\Module\Vspheredb\Exception\NoSessionForCookieError;
use Icinga\Module\Vspheredb\Exception\VmwareException;
use Icinga\Module\Vspheredb\MappedClass\ApiClassMap;
use Icinga\Module\Vspheredb\MappedClass\EventFilterSpec;
use Icinga\Module\Vspheredb\MappedClass\EventFilterSpecByTime;
use Icinga\Module\Vspheredb\MappedClass\ObjectSpec;
use Icinga\Module\Vspheredb\MappedClass\PropertyFilterSpec;
use Icinga\Module\Vspheredb\MappedClass\PropertySpec;
use Icinga\Module\Vspheredb\MappedClass\RetrieveOptions;
use Icinga\Module\Vspheredb\MappedClass\RetrieveResult;
use Icinga\Module\Vspheredb\MappedClass\ServiceContent;
use Icinga\Module\Vspheredb\MappedClass\SessionManager;
use Icinga\Module\Vspheredb\MappedClass\UserSession;
use Icinga\Module\Vspheredb\Polling\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SelectSet\SelectSet;
use Icinga\Module\Vspheredb\SafeCacheDir;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

class VsphereApi
{
    /** @var ServerInfo */
    protected $server;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CurlAsync */
    private $curl;

    /** @var SoapClient */
    private $soapClient;

    /** @var ManagedObjectReference */
    private $serviceInstanceRef;

    private $initialWsdlFile;

    /** @var ServiceContent */
    private $serviceInstance;

    /** @var CookieStore */
    private $cookieStore;

    /** @var LoopInterface */
    private $loop;

    /** @var ?ManagedObjectReference */
    private $eventCollector;

    /** @var int */
    private $lastEventTimestamp;

    public function __construct(
        $initialWsdlFile,
        ServerInfo $server,
        CurlAsync $curl,
        LoopInterface $loop,
        LoggerInterface $logger
    ) {
        $this->loop = $loop;
        $this->cookieStore = new CookieStore(SafeCacheDir::getSubDirectory('cookies'), $server, $logger);
        $this->serviceInstanceRef = new ManagedObjectReference('ServiceInstance', 'ServiceInstance');
        $this->initialWsdlFile = $initialWsdlFile;
        $this->server = $server;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->prepareSoapClient();
    }

    /**
     * A ServiceInstance, lazy-loaded only once
     *
     * This is a stdClass for now, might become a dedicated class
     *
     * Required Privileges: System.Anonymous
     *
     * @return ExtendedPromiseInterface <ServiceContent>
     */
    public function getServiceInstance()
    {
        if ($this->serviceInstance === null) {
            $this->serviceInstance = $this->retrieveServiceContent();
        }

        return resolve($this->serviceInstance);
    }

    /**
     * @return ExtendedPromiseInterface|PromiseInterface <DateTime, Exception>
     */
    public function getCurrentTime()
    {
        return $this->call($this->serviceInstanceRef, 'CurrentTime')->then(function ($result) {
            return new DateTime($result->returnval);
        });
    }

    /**
     * Really fetch the ServiceInstance
     *
     * @return ExtendedPromiseInterface|PromiseInterface <ServiceContent, Exception>
     * @see getServiceInstance()
     *
     */
    public function retrieveServiceContent()
    {
        return $this->call($this->serviceInstanceRef, 'RetrieveServiceContent')->then(function ($result) {
            return $result->returnval;
        });
    }

    /**
     * @return ExtendedPromiseInterface|PromiseInterface <UserSession>
     */
    public function eventuallyLogin()
    {
        if ($this->cookieStore->hasCookies()) {
            return $this->getCurrentSession()->then(function (UserSession $session) {
                $this->logger->debug(sprintf(
                    "Our session for %s@%s is still valid",
                    $session->userName,
                    $session->ipAddress
                ));

                return $session;
            }, function (Exception $e) {
                if ($e instanceof RequestError) {
                    $this->logger->error($e->getMessage());
                    throw $e;
                }
                $message = 'Dropping outdated Cookies, logging in again';
                if (! $e instanceof NoSessionForCookieError) {
                    $message .= '. Unknown Error: ' . get_class($e) . ' ' . $e->getMessage();
                }
                $this->logger->notice($message);
                $this->cookieStore->forgetCookies();
                return $this->login();
            });
        } else {
            return $this->login();
        }
    }

    /**
     * API login
     *
     * This will retrieve a session cookie and pass it with subsequent requests
     * @return ExtendedPromiseInterface|PromiseInterface <UserSession>
     */
    public function login()
    {
        $this->logger->debug(sprintf('Sending Login request to %s', $this->makeLocation()));
        return $this->callOnServiceInstanceObject('sessionManager', 'Login', [
            'userName' => $this->server->get('username'),
            'password' => $this->server->get('password'),
        ])->then(function ($result) {
            return $result->returnval;
        });
    }

    /**
     * Logout, destroy our session
     */
    public function logout()
    {
        return $this->callOnServiceInstanceObject('sessionManager', 'Logout')->then(function () {
            $this->logger->notice('Logged out');
            $this->cookieStore->forgetCookies();
        }, function (Exception $exception) {
            $this->logger->error('Logout failed: ' . $exception->getMessage());
            $this->cookieStore->forgetCookies();
        });
    }

    /**
     * Eventually logout
     */
    public function eventuallyLogout()
    {
        return $this->callOnServiceInstanceObject('sessionManager', 'Logout')->then(function () {
            $this->logger->notice('Logged out');
            $this->cookieStore->forgetCookies();
        }, function () {
            $this->cookieStore->forgetCookies();
            return resolve();
        });
    }

    /**
     * @param ManagedObjectReference $self
     * @param $method
     * @param array $arguments
     * @return ExtendedPromiseInterface
     */
    public function call(ManagedObjectReference $self, $method, $arguments = [])
    {
        return $this->soapClient->call($method, [[
                '_this' => $self
            ] + $arguments]);
    }

    public function callOnServiceInstanceObject($serviceInstanceObjectName, $method, $arguments = [])
    {
        $property = $serviceInstanceObjectName;
        return $this->getServiceInstance()
            ->then(function (ServiceContent $serviceContent) use ($property, $method, $arguments) {
                if (!isset($serviceContent->$property)) {
                    return reject("ServiceInstance has no '$property'");
                }
                $ref = $serviceContent->$property;
                if (! $ref instanceof ManagedObjectReference) {
                    return reject(new \InvalidArgumentException(
                        "ServiceContent.$property is no a ManagedObjectReference"
                    ));
                }

                return $this->call($ref, $method, $arguments);
            });
    }

    /**
     * @param ManagedObjectReference $object
     * @param array|null $properties
     * @return PromiseInterface < ?ManagedObject>
     */
    public function requireSingleObjectProperties(ManagedObjectReference $object, $properties = null)
    {
        return $this->fetchSingleObject($object, $properties)->then(function ($resultObject) use ($object) {
            if ($resultObject) {
                return $resultObject;
            }

            return reject(new NotFoundError(sprintf(
                'Failed to fetch properties for %s, got empty result',
                $object->getLogName()
            )));
        });
    }

    /**
     * @param ManagedObjectReference $object
     * @param array|null $properties
     * @return ExtendedPromiseInterface|PromiseInterface < ?ManagedObject>
     */
    public function fetchSingleObject(ManagedObjectReference $object, $properties = null)
    {
        return $this->retrieveProperties([$this->singleObjectSpecSet($object, $properties)])
            ->then(function (RetrieveResult $result) use ($object) {
                $objects = $result->makeObjects();
                if (empty($objects)) {
                    return null;
                }
                if (count($objects) > 1) {
                    return reject(new Exception(sprintf(
                        'Got %d results for %s while expecting only one',
                        count($objects),
                        $object->getLogName()
                    )));
                }
                return $objects[0];
            });
    }

    public function fetchCustomFieldsManager()
    {
        return $this->getServiceInstance()->then(function (ServiceContent $serviceContent) {
            if (! isset($serviceContent->customFieldsManager)) {
                // CustomFieldsManager -> vCenter only, not on ESXi Hosts
                return null;
            }

            return $this->fetchSingleObject($serviceContent->customFieldsManager);
        });
    }

    /**
     * @param ManagedObjectReference $object
     * @param array|null $properties
     * @return ExtendedPromiseInterface|PromiseInterface < ?ObjectContent>
     */
    public function fetchSingleObjectProperties(ManagedObjectReference $object, $properties = null)
    {
        return $this->retrieveProperties([$this->singleObjectSpecSet($object, $properties)])
            ->then(function (RetrieveResult $result) use ($object) {
                if (empty($result->objects)) {
                    return null;
                }
                if (count($result->objects) > 1) {
                    return reject(new Exception(sprintf(
                        'Got %d results for %s while expecting only one',
                        count($result->objects),
                        $object->getLogName()
                    )));
                }
                return $result->objects[0];
            });
    }
    /**
     * TODO: Can be used for mass requests once we deal with the token in the RetrieveResult
     *
     * @param PropertyFilterSpec[] $specSet
     * @return ExtendedPromiseInterface|PromiseInterface <RetrieveResult>
     */
    public function retrieveProperties(array $specSet)
    {
        return $this->getPropertyCollector()->then(function (ManagedObjectReference $collector) use ($specSet) {
            return $this->call($collector, 'RetrievePropertiesEx', [
                'specSet' => $specSet,
                'options' => RetrieveOptions::create(100)
            ])->then(function ($result) {
                $result = $this->requireRetrieveResult($result);
                $objects = $result->objects;

                return $this->fetchFullResult($result, $objects)->then(function () use (&$objects) {
                    if (isset($objects[0])) {
                        if ($objects[0]->reportsNotAuthenticated()) {
                            return reject(VmwareException::forMissingSet($objects[0]->missingSet));
                        }
                    }
                    $result = new RetrieveResult();
                    $result->objects = $objects;
                    return $result;
                });
            });
        });
    }

    public function getCurrentSession()
    {
        return $this->getServiceInstance()->then(function (ServiceContent $content) {
            return $this->fetchSingleObject($content->sessionManager, [
                'currentSession',
                'defaultLocale',
            ])->then(function (SessionManager $manager) {
                if (isset($manager->currentSession)) {
                    return $manager->currentSession;
                }

                return reject(new NoSessionForCookieError('There is no active session'));
            });
        });
    }

    /**
     * @return PromiseInterface <ManagedObjectReference>
     */
    public function getRootFolder()
    {
        return $this->getServiceInstance()->then(function (ServiceContent $serviceContent) {
            return $serviceContent->rootFolder;
        });
    }

    /**
     * Returns either the Instance UUID for a vCenter or the Host System UUID for an ESXi Host
     * @return PromiseInterface <UuidInterface>
     */
    public function fetchUniqueId()
    {
        return $this->getServiceInstance()->then(function (ServiceContent $content) {
            if ($content->about->instanceUuid === null) {
                return $this->fetchSpecSet([PropertyFilterSpec::create(
                    [ObjectSpec::create($content->rootFolder, HostSystemSelectSet::create(), false)],
                    [PropertySpec::create('HostSystem', ['hardware.systemInfo.uuid'])]
                )])->then(function ($result) {
                    if (empty($result)) {
                        throw new \RuntimeException('Unable to fetch host object for ESXi system');
                    }
                    if (count($result) > 1) {
                        throw new \RuntimeException(sprintf(
                            'Expected to get one host from an ESXi system, got %d',
                            count($result)
                        ));
                    }
                    if (isset($result[0]['hardware.systemInfo.uuid'])) {
                        return Uuid::fromString($result[0]['hardware.systemInfo.uuid']);
                    }

                    throw new \RuntimeException('Got no hardware.systemInfo.uuid from ESXi host');
                });
            } else {
                return Uuid::fromString($content->about->instanceUuid);
            }
        });
    }

    public function setLastEventTimestamp($timestamp)
    {
        $this->lastEventTimestamp = $timestamp;

        return $this;
    }

    protected function fetchFullResult(RetrieveResult $result, &$objects)
    {
        $deferred = new Deferred();
        if ($result->hasMoreResults()) {
            $this->loop->futureTick(function () use ($result, &$objects, $deferred) {
                $this->continueFetchProperties($result->token)
                    ->then(function (RetrieveResult $result) use (&$objects) {
                        foreach ($result->objects as $object) {
                            $objects[] = $object;
                        }

                        return $this->fetchFullResult($result, $objects);
                    })->then(function () use ($deferred) {
                        $deferred->resolve();
                    });
            });
        } else {
            $this->loop->futureTick(function () use ($deferred) {
                $deferred->resolve();
            });
        }
        return $deferred->promise();
    }

    protected function getPropertyCollector()
    {
        return $this->getServiceInstance()->then(function (ServiceContent $serviceContent) {
            return $serviceContent->propertyCollector;
        });
    }

    protected function requireRetrieveResult($result)
    {
        if (! isset($result->returnval)) {
            return new RetrieveResult();
            // TODO: test outdated EventCollector. Does it still fail, as it should?
            // throw new \RuntimeException('Got no returnval / RetrieveResult');
        }

        $result = $result->returnval;
        assert($result instanceof RetrieveResult);

        return $result;
    }

    protected function continueFetchProperties($token)
    {
        return $this->callOnServiceInstanceObject('propertyCollector', 'ContinueRetrievePropertiesEx', [
            'token' => $token
        ])->then(function ($result) {
            return $this->requireRetrieveResult($result);
        });
    }

    protected function singleObjectSpecSet(ManagedObjectReference $moRef, $properties = null)
    {
        return PropertyFilterSpec::create(
            [ObjectSpec::create($moRef, null, false)],
            [PropertySpec::create($moRef->type, $properties, $properties === null)]
        );
    }

    protected function fetchSpecSet(array $specSet)
    {
        return $this->retrieveProperties($specSet)->then(function (RetrieveResult $result) {
            return $result->jsonSerialize();
        });
    }

    /**
     * @param string|SelectSet $selectSetClass It's a string, SelectSet helps the IDE
     * @param string|PropertySet $propertySetClass It's a string, PropertySet helps the IDE
     * @return PromiseInterface
     */
    public function fetchBySelectAndPropertySetClass($selectSetClass, $propertySetClass)
    {
        return $this->getRootFolder()->then(function ($rootFolder) use ($selectSetClass, $propertySetClass) {
            return $this->fetchSpecSet([PropertyFilterSpec::create(
                [ObjectSpec::create($rootFolder, $selectSetClass::create(), false)],
                $propertySetClass::create()
            )]);
        });
    }

    public function readNextEvents()
    {
        return $this->callOnEventCollector('ReadNextEvents', [
            'maxCount' => 1000,
        ]);
    }

    public function rewindEventCollector()
    {
        return $this->callOnEventCollector('RewindCollector');
    }

    public function fetchPerformanceManager()
    {
        return $this->getServiceInstance()->then(function (ServiceContent $serviceContent) {
            return $this->fetchSingleObject($serviceContent->perfManager);
        });
    }

    protected function getEventCollector()
    {
        if ($this->eventCollector) {
            return resolve($this->eventCollector);
        }

        return $this->createEventCollector($this->lastEventTimestamp)->then(function ($result) {
            if (isset($result->returnval) && $result->returnval instanceof ManagedObjectReference) {
                $this->eventCollector = $result->returnval;

                return $this->rewindEventCollector()->then(function () {
                    return $this->eventCollector;
                });
            }

            throw new \RuntimeException('EventCollector reference expected, got ' . var_export($result, 1));
        });
    }

    protected function callOnEventCollector($method, $arguments = [])
    {
        // Sample:
        // $collector = new ManagedObjectReference(
        //     'EventHistoryCollector',
        //     'session[52244af1-bf50-7602-35f9-8379a4f7056b]526601fc-62e7-25b6-9a43-8aecb5c89ed9'
        // );
        return $this->getEventCollector()->then(function (ManagedObjectReference $collector) use ($method, $arguments) {
            return $this->call($collector, $method, $arguments)->then(function ($result) {
                if (property_exists($result, 'returnval')) {
                    return $result->returnval;
                }

                return [];
            }, function (Exception $e) {
                if ($e instanceof \SoapFault) {
                    if (isset($e->detail)) {
                        $details = (array)$e->detail;
                        if (current($details)->enc_stype === 'ManagedObjectNotFound') {
                            $this->eventCollector = null;
                            throw new \RuntimeException(
                                'Dropping formerly known EventCollector: ' . $e->getMessage(),
                                $e->getCode(),
                                $e
                            );
                        }
                    }
                }
                throw $e;
            });
        });
    }

    protected function createEventCollector($lastEventTimestamp = null)
    {
        $spec = new EventFilterSpec();
        $spec->type = $this->getRequiredEventTypes();
        if ($lastEventTimestamp) {
            $spec->time = EventFilterSpecByTime::create((int) floor($lastEventTimestamp / 1000));
        }

        return $this->callOnServiceInstanceObject('eventManager', 'CreateCollectorForEvents', [
            'filter' => $spec
        ]);
    }

    /**
     * @throws Exception e.g.: SOAP-ERROR: Parsing Schema: can't import schema from '/tmp/[..]/vim-types.xsd
     */
    protected function prepareSoapClient()
    {
        $this->soapClient = new SoapClient($this->curl, $this->initialWsdlFile, [
            'trace'              => true,
            'location'           => $this->makeLocation(),
            'exceptions'         => true,
            'connection_timeout' => 10,
            'classmap'           => ApiClassMap::getMap(),
            'features'           => SOAP_SINGLE_ELEMENT_ARRAYS | SOAP_USE_XSI_ARRAY_TYPE,
            'cache_wsdl'         => WSDL_CACHE_NONE,
            'compression'        => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
        ], CurlOptions::forServerInfo($this->server), $this->logger);
        $this->soapClient->setCookieStore($this->cookieStore);
    }

    /**
     * Builds our base url
     *
     * @return string
     */
    protected function makeLocation()
    {
        return $this->server->getUrl() . '/sdk';
    }

    protected function getRequiredEventTypes()
    {
        return [
            'AlarmAcknowledgedEvent',
            'AlarmClearedEvent',
            'AlarmCreatedEvent',
            'AlarmReconfiguredEvent',
            'AlarmRemovedEvent',
            'AlarmStatusChangedEvent',

            'VmBeingMigratedEvent',
            'VmBeingHotMigratedEvent',
            'VmEmigratingEvent',
            'VmFailedMigrateEvent',
            'VmMigratedEvent',
            'DrsVmMigratedEvent',
            //
            'VmBeingCreatedEvent',
            'VmCreatedEvent',
            'VmStartingEvent',
            'VmPoweredOnEvent',
            'VmPoweredOffEvent',
            'VmResettingEvent',
            'VmSuspendedEvent',

            'VmStoppingEvent',

            'VmBeingDeployedEvent',
            'VmReconfiguredEvent',

            'VmBeingClonedEvent',
            'VmBeingClonedNoFolderEvent',
            'VmClonedEvent',
            'VmCloneFailedEvent',
        ];
    }
}
