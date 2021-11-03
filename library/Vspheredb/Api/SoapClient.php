<?php

namespace Icinga\Module\Vspheredb\Api;

use gipfl\Curl\CurlAsync;
use Icinga\Module\Vspheredb\Api\Protocol\ClientDecoder;
use Icinga\Module\Vspheredb\Api\Protocol\ClientEncoder;
use Icinga\Module\Vspheredb\Polling\CookieStore;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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

    /**
     * @param CurlAsync   $curl
     * @param string|null $wsdl
     * @param array       $options
     */
    public function __construct(CurlAsync $curl, $wsdl, array $options = [], $curlOptions = [])
    {
        $this->curl = $curl;
        $this->encoder = new ClientEncoder($wsdl, $options);
        $this->decoder = new ClientDecoder($wsdl, $options);
        $this->curlOptions = $curlOptions;
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
                $this->checkResponseForCookies($response);
                return $this->decoder->decode($method, (string)$response->getBody());
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
