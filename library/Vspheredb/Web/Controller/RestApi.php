<?php

namespace Icinga\Module\Vspheredb\Web\Controller;

use gipfl\Json\JsonException;
use gipfl\Json\JsonString;
use Icinga\Web\Response;
use InvalidArgumentException;
use Zend_Controller_Response_Exception;

trait RestApi
{
    protected function downloadJson(Response $response, array|object $object, string $filename): void
    {
        if (!$this->hasPermission('vspheredb/export')) {
            $this->sendJsonError($this->getResponse(), 'vspheredb/export permissions required', 403);
            return;
        }
        if (!$this->getRequest()->isApiRequest()) {
            $response->setHeader('Content-Disposition', 'attachment; filename=' . self::safeFilename($filename));
        }
        $this->sendJson($response, $object);
    }

    protected function sendJson(Response $response, array|object $object): void
    {
        $response->setHeader('Content-Type', 'application/json', true);
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->disable();
        try {
            echo JsonString::encode($object, JSON_PRETTY_PRINT) . "\n";
        } catch (JsonException $e) {
            $this->sendJsonError($response, $e->getMessage());
        }
    }

    /**
     * @param Response $response
     * @param string $message
     * @param int|null $code
     *
     * @return void
     */
    protected function sendJsonError(Response $response, string $message, ?int $code = null): void
    {
        if ($code !== null) {
            try {
                $response->setHttpResponseCode($code);
            } catch (Zend_Controller_Response_Exception $e) {
                throw new InvalidArgumentException($e->getMessage(), 0, $e);
            }
        }

        $this->sendJson($response, (object) ['error' => $message]);
    }

    /**
     * Hint: this does NOT protect against attacks, it's for convenience only
     */
    protected static function safeFilename(string $filename): string
    {
        return str_replace([' ', '"'], ['_', '_'], iconv(
            'UTF-8',
            'ISO-8859-1//IGNORE',
            $filename
        ));
    }
}
