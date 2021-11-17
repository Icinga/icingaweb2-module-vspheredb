<?php

namespace Icinga\Module\Vspheredb\Api;

use gipfl\Curl\CurlAsync;
use Icinga\Module\Vspheredb\Api\Protocol\ClientDecoder;
use Icinga\Module\Vspheredb\Api\Protocol\ClientEncoder;
use Icinga\Module\Vspheredb\Polling\CookieStore;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\ExtendedPromiseInterface;

/**
 * Heavily inspired by Clue\React\Soap
 */
class SoapClient
{
    /** @var array */
    protected $curlOptions;

    private $curl;

    private $encoder;

    private $decoder;

    /** @var CookieStore */
    protected $cookieStore;

    protected $logger;

    /**
     * @param CurlAsync   $curl
     * @param string|null $wsdl
     * @param array       $options
     */
    public function __construct(
        CurlAsync $curl,
        $wsdl,
        array $options = [],
        $curlOptions = [],
        LoggerInterface $logger = null
    ) {
        $this->curl = $curl;
        $this->encoder = new ClientEncoder($wsdl, $options);
        $this->decoder = new ClientDecoder($wsdl, $options);
        $this->curlOptions = $curlOptions;
        $this->logger = $logger ?: new NullLogger();
    }

    public function setCookieStore(CookieStore $cookieStore)
    {
        $this->cookieStore = $cookieStore;
    }

    /**
     * @param string $method
     * @param mixed[] $args
     * @return ExtendedPromiseInterface
     */
    public function call($method, $args)
    {
        $request = $this->addCookiesToRequest(
            $this->encoder->encode($method, $args),
            $method
        );

        return $this->curl->send($request, $this->curlOptions)
            ->then(function (ResponseInterface $response) use ($method) {
                $status = $response->getStatusCode();
                if ($status > 199 && $status<= 299) {
                    try {
                        $result =  $this->decoder->decode($method, (string)$response->getBody());
                        $this->checkResponseForCookies($response);

                        return $result;
                    } catch (\Exception $e) {
                        $this->logger->debug('Failing Response: ' . $response->getBody());
                        throw $e;
                    }
                } else {
                    throw new \Exception($response->getReasonPhrase());
                }
            });
    }

    protected function addCookiesToRequest(RequestInterface $request, $soapFunctionName)
    {
        if ($this->cookieStore && $this->cookieStore->hasCookies()) {
            foreach ($this->cookieStore->getCookies() as $cookie) {
                $request = $request->withAddedHeader('Cookie', $cookie);
            }
        }

        return $request;
    }

    protected function checkResponseForCookies(ResponseInterface $response)
    {
        if ($this->cookieStore) {
            $cookies = $response->getHeader('set-cookie');
            if (! empty($cookies)) {
                $this->cookieStore->setCookies($cookies);
            }
        }
    }
}
