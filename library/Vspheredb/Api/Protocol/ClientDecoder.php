<?php

namespace Icinga\Module\Vspheredb\Api\Protocol;

use SoapClient;
use SoapFault;

/**
 * Copied from here:
 * https://github.com/clue/reactphp-soap/blob/main/src/Protocol/ClientDecoder.php
 * @license MIT
 * @internal
 */
final class ClientDecoder extends SoapClient
{
    private $response = null;

    /**
     * Decodes the SOAP response / return value from the given SOAP envelope (HTTP response body)
     *
     * @param string $function
     * @param string $response
     * @return mixed
     * @throws SoapFault if response indicates a fault (error condition) or is invalid
     */
    public function decode($function, $response)
    {
        // Temporarily save response internally for further processing
        $this->response = $response;

        // Let's pretend we just invoked the given SOAP function.
        // This won't actually invoke anything (see `__doRequest()`), but this
        // requires a valid function name to match its definition in the WSDL.
        // Internally, simply use the injected response to parse its results.
        $ret = $this->__soapCall($function, []);
        $this->response = null;

        return $ret;
    }

    /**
     * Overwrites the internal request logic to parse the response
     *
     * By overwriting this method, we can skip the actual request sending logic
     * and still use the internal parsing logic by injecting the response as
     * the return code in this method. This will implicitly be invoked by the
     * call to `pseudoCall()` in the above `decode()` method.
     *
     * @see SoapClient::__doRequest()
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        // the actual result doesn't actually matter, just return the given result
        // this will be processed internally and will return the parsed result
        return $this->response;
    }
}
