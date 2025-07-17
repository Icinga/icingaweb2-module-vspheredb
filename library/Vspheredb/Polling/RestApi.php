<?php

namespace Icinga\Module\Vspheredb\Polling;

use gipfl\Curl\CurlAsync;
use gipfl\Json\JsonString;
use GuzzleHttp\Psr7\Request;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\SafeCacheDir;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use stdClass;

use function React\Promise\reject;
use function React\Promise\resolve;

class RestApi
{
    /** @var CurlAsync */
    protected $curl;

    /** @var CookieStore */
    protected $sidStore;

    /** @var ServerInfo */
    protected $server;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array[] */
    protected $curlOptions;
    /**
     * @var VCenter
     */
    protected $vCenter;

    /** @var \Closure Will become obsolete with PHP 8.1 */
    private $normalizeBatchResult;

    public function __construct(
        ServerInfo $server,
        VCenter $vCenter,
        CurlAsync $curl,
        LoggerInterface $logger
    ) {
        $this->sidStore = new CookieStore(SafeCacheDir::getSubDirectory('restSessionId'), $server, $logger);
        $this->server = $server;
        $this->vCenter = $vCenter;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->curlOptions = CurlOptions::forServerInfo($server);
        $this->normalizeBatchResult = function ($result) {
            // print_r($result);
            return $this->normalizeBatchResult($result);
        };
    }

    /**
     * @return PromiseInterface<bool>
     */
    public function requireSession(): PromiseInterface
    {
        return $this->checkWhetherSessionIsStillValid()->then(function ($valid) {
            if ($valid) {
                $this->logger->debug('REST API Session is still valid');
                return resolve(true);
            }

            return $this->authenticate();
        });
    }

    /**
     * @return PromiseInterface<string[]>
     */
    public function listTags(): PromiseInterface
    {
        return $this->get('cis/tagging/tag');
    }

    /**
     * @return PromiseInterface<string[]>
     */
    public function listCategories(): PromiseInterface
    {
        return $this->get('cis/tagging/category');
    }

    /**
     * @return PromiseInterface<array<string, stdClass>>
     */
    public function getAll(string $objectType): PromiseInterface
    {
        return $this->taggingBatchLegacy('get-all-' . $objectType);
    }

    /**
     * @return PromiseInterface<array<string, stdClass>>
     */
    public function fetchAllCategories(): PromiseInterface
    {
        return $this->getAll('categories');
    }

    /**
     * @return PromiseInterface<array<string, stdClass>>
     */
    public function fetchAllTags(): PromiseInterface
    {
        return $this->getAll('tags');
    }

    /**
     * @return PromiseInterface<array<string, stdClass>>
     */
    public function fetchAllAssignments(): PromiseInterface
    {
        // Hint -> tag_ids = [] seems to ship ALL assignments
        return $this->taggingBatchLegacy('list-attached-objects-on-tags', ['tag_ids' => []]);
    }

    /**
     * @return PromiseInterface<bool>
     */
    protected function checkWhetherSessionIsStillValid(): PromiseInterface
    {
        if ($this->sidStore->hasCookies()) {
            return $this->send($this->request('GET', $this->apiUrl('session')))->then(
                function (ResponseInterface $result) {
                    if ($result->getStatusCode() === 200) {
                        // Body: {
                        //   "created_time": "2025-07-16T07:18:48.517Z",
                        //   "last_accessed_time":"2025-07-16T07:20:11.799Z",
                        //   "user":"username@VSPHERE.LOCAL"
                        // }
                        return true;
                    } else {
                        $this->logger->debug('REST API Session is no longer valid');
                        $this->sidStore->forgetCookies();
                        return false;
                    }
                }
            );
        }

        return resolve(false);
    }

    /**
     * @return PromiseInterface<true>
     */
    protected function authenticate(): PromiseInterface
    {
        $request = new Request('POST', $this->apiUrl('session'), [
            'Accept' => 'application/json',
            'Authorization' => $this->generateBasicAuthHeaderLine(),
        ]);

        return $this->curl->send($request, $this->curlOptions)
            ->then(function (ResponseInterface $result) {
                if ($result->getStatusCode() > 199 && $result->getStatusCode() < 299) {
                    // Body: "001080b09e3937191f64988540d2d641"
                    $this->sidStore->setCookies([JsonString::decode($result->getBody()->getContents())]);

                    return true;
                }

                return reject(new RuntimeException('REST API Authentication failed: ' . $result->getStatusCode()));
            });
    }

    protected function generateBasicAuthHeaderLine(): string
    {
        $credentials =  base64_encode(implode(':', [$this->server->get('username'), $this->server->get('password')]));
        return "Basic $credentials";
    }

    protected function normalizeBatchResult(array $value): array
    {
        $result = [];
        foreach ($value as $object) {
            if (isset($object->id)) {
                $object->uuid = self::uuidFromTagUrn($object->id)->toString();
                if (isset($object->associable_types)) { // on Category
                    $object->associable_types = JsonString::encode($object->associable_types);
                }
                if (isset($object->category_id)) { // on Tag
                    $object->category_uuid = self::uuidFromTagUrn($object->category_id)->toString();
                }
                $result[$object->uuid] = $object;
            } elseif (isset($object->object_ids)) { // Assignments
                $tagUuid = self::uuidFromTagUrn($object->tag_id)->toString();
                foreach ($object->object_ids as $ref) {
                    $objectUuid = $this->vCenter->makeBinaryGlobalMoRefUuidObject(
                        new ManagedObjectReference($ref->type, $ref->id)
                    )->toString();
                    $result[$objectUuid . '/' . $tagUuid] = (object) [
                        'object_uuid' => $objectUuid,
                        'tag_uuid'    => $tagUuid
                    ];
                }
            } else {
                throw new RuntimeException('Got unexpected object from REST API batch: ' . var_export($object, true));
            }
        }

        return $result;
    }

    /**
     * create_spec.description  string  The description of the tag.
     * create_spec.category_id  string  The unique identifier of the parent category in which this tag will be created.
     *                                  When clients pass a value of this structure as a parameter, the field must
     *                                  be an identifier for the resource type: com.vmware.cis.tagging.Category. When
     *                                  operations return a value of this structure as a result, the field will be an
     *                                  identifier for the resource type: com.vmware.cis.tagging.Category.
     * create_spec.tag_id       string  This attribute was added in vSphere API 6.7
     *                                  Optional. If unset an identifier will be generated by the server. When clients
     *                                  pass a value of this structure as a parameter, the field must be an identifier
     *                                  for the resource type: com.vmware.cis.tagging.Tag. When operations return a
     *                                  value of this structure as a result, the field will be an identifier for the
     *                                  resource type: com.vmware.cis.tagging.Tag.
     */

    protected function send(RequestInterface $request): PromiseInterface
    {
        // var_dump($request->getMethod() . ' ' . $request->getUri());
        // print_r($request->getHeaders());
        return $this->curl->send($request, $this->curlOptions);
    }

    public function getUsedCategories()
    {
        // Test only, doesn't work
        return $this->post("cis/tagging/category?action=list-used-categories");
    }

    protected function taggingBatch(string $action, $body = null): PromiseInterface
    {
        // Doesn't work, didn't find a /api URL offering what /batch does for /rest
        return $this->post("cis/tagging/tag-association?action=$action", $body);
    }

    protected function get(string $url): PromiseInterface
    {
        return $this->send($this->request('GET', $this->apiUrl($url)))
            ->then([$this, 'decodeResponse']);
    }

    protected function post(string $url, $body = null): PromiseInterface
    {
        return $this->send($this->request('POST', $this->apiUrl($url), $body))
            ->then([$this, 'decodeResponse']);
    }

    protected function taggingBatchLegacy(string $action, $body = null): PromiseInterface
    {
        return $this->postLegacy("cis/tagging/batch?~action=$action", $body)
            ->then($this->normalizeBatchResult);
    }

    protected function getLegacy(string $url): PromiseInterface
    {
        return $this->send($this->request('GET', $this->legacyUrl($url)))
            ->then([$this, 'decodeResponse'])
            ->then([$this, 'requireValueProperty']);
    }

    protected function postLegacy(string $url, $body = null): PromiseInterface
    {
        return $this->send($this->request('POST', $this->legacyUrl($url), $body))
            ->then([$this, 'decodeResponse'])
            ->then([$this, 'requireValueProperty']);
    }

    /**
     * Will become protected, once we have ->decodeResponse(...) on 8.1
     * @internal
     */
    public function decodeResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() > 299) {
            throw new RuntimeException(
                'Request failed: ' . $response->getStatusCode() . $response->getBody()->getContents()
            );
        }

        return JsonString::decode($response->getBody()->getContents());
    }

    protected function apiUrl(?string $path = null): string
    {
        // Hint: now there is /api, but /rest/com/vmware still seems being the only viable option for batch operations
        return $this->server->getUrl() . '/api/' . ltrim(($path ?? ''), '/');
    }

    protected function legacyUrl(?string $path = null): string
    {
        return $this->server->getUrl() . '/rest/com/vmware/' . ltrim(($path ?? ''), '/');
    }

    protected function addSessionIdToRequest(RequestInterface $request): RequestInterface
    {
        if ($this->sidStore && $this->sidStore->hasCookies()) {
            foreach ($this->sidStore->getCookies() as $sid) {
                if (str_starts_with($request->getUri()->getPath(), '/rest/')) {
                    $request = $request->withAddedHeader('cookie', "vmware-api-session-id=$sid");
                } else {
                    $request = $request->withAddedHeader('vmware-api-session-id', $sid);
                }
            }
        }

        return $request;
    }

    /**
     * @internal Will become protected with 8.1
     */
    public function requireValueProperty(stdClass $result)
    {
        if (isset($result->error_type)) {
            // {
            //   "error_type": "NOT_FOUND",
            //   "messages": [
            //     {
            //       "args": [],
            //       "default_message": "Not found.",
            //       "id": "com.vmware.vapi.rest.httpNotFound",
            //     }
            //   ]
            // }
            throw new RuntimeException($result->error_type);
        }
        if (! isset($result->value)) {
            throw new RuntimeException("Got no ->value");
        }

        return $result->value;
    }

    protected function request(string $method, string $url, $body = null): RequestInterface
    {
        $headers = [
            'Accept' => 'application/json',
        ];
        if ($body) {
            $headers['Content-type'] = 'application/json';
            $body = JsonString::encode($body);
        }

        return $this->addSessionIdToRequest(new Request($method, $url, $headers, $body));
    }

    protected static function uuidFromTagUrn(string $urn): UuidInterface
    {
        // urn:vmomi:InventoryService{Tag|Category}:{UUID}:{SCOPE}
        // e.g. urn:vmomi:InventoryServiceCategory:18b7552a-1a13-402b-b91ebbb77-7b73458:GLOBAL
        [$urnPrefix, $ns, $type, $uuid, $scope] = explode(':', $urn, 5);

        return Uuid::fromString($uuid);
    }
}
