<?php

namespace Icinga\Module\Vsphere;

use Exception;

class CurlLoader
{
    private $curl;
    private $proxy;
    private $host;
    private $user;
    private $pass;
    private $port = 443;
    private $persistCookies = false;
    private $cookieFile;
    private $cookies = array();

    public function __construct($host, $user = null, $pass = null)
    {
        if ($this->persistCookies) {
            $this->cookieFile = "/tmp/vmwareWsdl/cookie-$host";
            if (file_exists($this->cookieFile)) {
                $this->cookies[] = file_get_contents($this->cookieFile);
            }
        }
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function hasCookie()
    {
        return ! empty($this->cookies);
    }

    public function forgetCookie()
    {
        $this->cookies = array();
        if ($this->persistCookies) {
            unlink($this->cookieFile);
        }
    }

    public function url($url)
    {
        return sprintf('https://%s:%d/%s', $this->host, $this->port, $url);
    }

    public function get($url, $body = null)
    {
        return $this->request('get', $url, $body);
    }

    public function getRaw($url, $body = null)
    {
        return $this->request('get', $url, $body, true);
    }

    public function post($url, $body = null, $headers = array())
    {
        return $this->request('post', $url, $body, $headers);
    }

    protected function request($method, $url, $body = null, $headers = array())
    {
        $sendHeaders = array('Host: ' . $this->host);
        foreach ($this->cookies as $cookie) {
            $sendHeaders[] = 'Cookie: ' . $cookie;
        }
        foreach ($headers as $key => $val) {
            $sendHeaders[] = "$key: $val";
        }

        /*
        // Testing:
        echo "-->";
        printf("%s %s\n", $method, $url);
        echo implode("\n", $sendHeaders);
        echo "\n";
        echo $body;
        echo "\n\n<--\n";
        */

        $curl = $this->curl();
        $opts = array(
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $sendHeaders,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,

            // TODO: Fix this!
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADERFUNCTION => array($this, 'processHeaderLine'),
        );

        if ($this->user !== null) {
            $opts[CURLOPT_USERPWD] = sprintf('%s:%s', $this->user, $this->pass);
        }

        if ($this->proxy) {
            // $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
            $opts[CURLOPT_PROXY] = $this->proxy;
        }

        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($curl, $opts);
        // TODO: request headers, validate status code

        $res = curl_exec($curl);
        if ($res === false) {
            throw new Exception('CURL ERROR: ' . curl_error($curl));
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode === 401) {
            throw new Exception(
                'Unable to authenticate, please check your API credentials'
            );
        }

        if ($statusCode >= 400) {
            throw new Exception(
                "Got $statusCode: " . var_export($res, 1)
            );
        }

        return $res;
    }

    public function processHeaderLine($curl, $header)
    {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) {
            return $len;
        }

        if ($header[0] === 'Set-Cookie') {
            $cookie = trim($header[1]);
            if ($this->persistCookies) {
                file_put_contents($this->cookieFile, $cookie);
            }
            $this->cookies[] = $cookie;
        }

        return $len;
    }

    /**
     * @throws Exception
     *
     * @return resource
     */
    protected function curl()
    {
        if ($this->curl === null) {
            $this->curl = curl_init(sprintf('https://%s:%d', $this->host, $this->port));
            if (! $this->curl) {
                throw new Exception('CURL INIT ERROR: ' . curl_error($this->curl));
            }
        }

        return $this->curl;
    }
}
