<?php

namespace Sentience\Sentience;

use SimpleXMLElement;
use Sentience\Helpers\Json;
use Sentience\Helpers\UrlEncoding;
use Sentience\Helpers\Xml;

class Request
{
    public static function createFromSuperGlobals(): static
    {
        $url = (\array_key_exists('HTTPS', $_SERVER) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $path = (string) strtok($_SERVER['REQUEST_URI'], '?');
        $method = strtoupper((string) $_SERVER['REQUEST_METHOD']);
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $queryString = (string) strtok($_SERVER['QUERY_STRING'] ?? '', '#');
        $queryParams = UrlEncoding::decode($queryString, config('sentience->encoding->url_encoding_unique', false));
        $cookies = $_COOKIE;
        $body = file_get_contents('php://input');

        return new static($url, $path, $method, $headers, $queryString, $queryParams, $cookies, $body);
    }

    public function __construct(
        public string $url,
        public string $path,
        public string $method,
        public array $headers,
        public string $queryString,
        public array $queryParams,
        public array $cookies,
        public string $body
    ) {
    }

    public function getHeader(string $key, ?string $default = null): ?string
    {
        if (!\array_key_exists($key, $this->headers)) {
            return $default;
        }

        return $this->headers[$key];
    }

    public function getQueryParam(string $key, null|string|array $default = null): mixed
    {
        if (!\array_key_exists($key, $this->queryParams)) {
            return $default;
        }

        return $this->queryParams[$key];
    }

    public function getCookie(string $key, ?string $default = null): ?string
    {
        if (!\array_key_exists($key, $this->cookies)) {
            return $default;
        }

        return $this->cookies[$key];
    }

    public function getJson(bool $assoc = true): mixed
    {
        return Json::decode($this->body, $assoc);
    }

    public function getXml(): ?SimpleXMLElement
    {
        return Xml::decode($this->body);
    }

    public function getUrlEncoded(): array
    {
        return UrlEncoding::decode($this->body, config('sentience->encoding->url_encoding_unique', false));
    }

    public function decode(): mixed
    {
        return match (config('sentience->encoding->default')) {
            'json' => $this->getJson(),
            'xml' => $this->getXml(),
            'url' => $this->getUrlEncoded(),
            default => $this->body
        };
    }

    public function getIPAddress(): ?string
    {
        $keys = [
            'REMOTE_ADDR',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR'
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key] ?? null)) {
                return (string) strtok($_SERVER[$key], ',');
            }
        }

        return null;
    }
}
