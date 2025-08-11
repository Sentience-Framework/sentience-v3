<?php

declare(strict_types=1);

namespace Sentience\Sentience;

use Sentience\Exceptions\EncodingException;
use Sentience\Helpers\Json;
use Sentience\Helpers\Strings;
use Sentience\Helpers\UrlEncoding;
use Sentience\Helpers\Xml;

class Response
{
    public const int OK = 200;
    public const int CREATED = 201;
    public const int ACCEPTED = 202;
    public const int NON_AUTHORITATIVE_INFORMATION = 203;
    public const int NO_CONTENT = 204;
    public const int RESET_CONTENT = 205;
    public const int PARTIAL_CONTENT = 206;
    public const int MULTI_STATUS = 207;
    public const int ALREADY_REPORTED = 208;
    public const int IM_USED = 226;
    public const int MULTIPLE_CHOICES = 300;
    public const int MOVED_PERMANENTLY = 301;
    public const int FOUND = 302;
    public const int SEE_OTHER = 303;
    public const int NOT_MODIFIED = 304;
    public const int TEMPORARY_REDIRECT = 307;
    public const int PERMANENT_REDIRECT = 308;
    public const int BAD_REQUEST = 400;
    public const int UNAUTHORIZED = 401;
    public const int PAYMENT_REQUIRED = 402;
    public const int FORBIDDEN = 403;
    public const int NOT_FOUND = 404;
    public const int METHOD_NOT_ALLOWED = 405;
    public const int NOT_ACCEPTABLE = 406;
    public const int PROXY_AUTHENTICATION_REQUIRED = 407;
    public const int REQUEST_TIMEOUT = 408;
    public const int CONFLICT = 409;
    public const int GONE = 410;
    public const int LENGTH_REQUIRED = 411;
    public const int PRECONDITION_FAILED = 412;
    public const int PAYLOAD_TOO_LARGE = 413;
    public const int URI_TOO_LONG = 414;
    public const int UNSUPPORTED_MEDIA_TYPE = 415;
    public const int RANGE_NOT_SATISFIABLE = 416;
    public const int EXPECTATION_FAILED = 417;
    public const int TEAPOT = 418;
    public const int MISDIRECTED_REQUEST = 421;
    public const int UNPROCESSABLE_CONTENT = 422;
    public const int LOCKED = 423;
    public const int FAILED_DEPENDENCY = 424;
    public const int TOO_EARLY = 425;
    public const int UPGRADE_REQUIRED = 426;
    public const int PRECONDITION_REQUIRED = 428;
    public const int TOO_MANY_REQUESTS = 429;
    public const int REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const int UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const int INTERNAL_SERVER_ERROR = 500;
    public const int NOT_IMPLEMENTED = 501;
    public const int BAD_GATEWAY = 502;
    public const int SERVICE_UNAVAILABLE = 503;
    public const int GATEWAY_TIMEOUT = 504;
    public const int HTTP_VERSION_NOT_SUPPORTED = 505;
    public const int VARIANT_ALSO_NEGOTIATES = 506;
    public const int INSUFFICIENT_STORAGE = 507;
    public const int LOOP_DETECTED = 508;
    public const int NOT_EXTENDED = 510;
    public const int NETWORK_AUTHENTICATION_REQUIRED = 511;

    public static function header(string $key, string $value, bool $replace = true): void
    {
        header(sprintf('%s: %s', $key, $value), $replace);
    }

    public static function cookie(string $key, string $value = '', int $expiresOrOptions = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false): void
    {
        setcookie($key, $value, ['expires' => $expiresOrOptions, 'path' => $path, 'domain' => $domain, 'secure' => $secure, 'httponly' => $httpOnly]);
    }

    public static function ok(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::OK, $content, $encoding);
    }

    public static function created(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::CREATED, $content, $encoding);
    }

    public static function accepted(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::ACCEPTED, $content, $encoding);
    }

    public static function nonAuthoritativeInformation(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NON_AUTHORITATIVE_INFORMATION, $content, $encoding);
    }

    public static function noContent(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NO_CONTENT, $content, $encoding);
    }

    public static function resetContent(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::RESET_CONTENT, $content, $encoding);
    }

    public static function partialContent(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::PARTIAL_CONTENT, $content, $encoding);
    }

    public static function multiStatus(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::MULTI_STATUS, $content, $encoding);
    }

    public static function alreadyReported(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::ALREADY_REPORTED, $content, $encoding);
    }

    public static function imUsed(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::IM_USED, $content, $encoding);
    }

    public static function multipleChoices(string $url): void
    {
        static::redirect(static::MULTIPLE_CHOICES, $url);
    }

    public static function movedPermanently(string $url): void
    {
        static::redirect(static::MOVED_PERMANENTLY, $url);
    }

    public static function found(string $url): void
    {
        static::redirect(static::FOUND, $url);
    }

    public static function seeOther(string $url): void
    {
        static::redirect(static::SEE_OTHER, $url);
    }

    public static function notModified(string $url): void
    {
        static::redirect(static::NOT_MODIFIED, $url);
    }

    public static function temporaryRedirect(string $url): void
    {
        static::redirect(static::TEMPORARY_REDIRECT, $url);
    }

    public static function permanentRedirect(string $url): void
    {
        static::redirect(static::PERMANENT_REDIRECT, $url);
    }

    public static function badRequest(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::BAD_REQUEST, $content, $encoding);
    }

    public static function unauthorized(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::UNAUTHORIZED, $content, $encoding);
    }

    public static function paymentRequired(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::PAYMENT_REQUIRED, $content, $encoding);
    }

    public static function forbidden(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::FORBIDDEN, $content, $encoding);
    }

    public static function notFound(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NOT_FOUND, $content, $encoding);
    }

    public static function methodNotAllowed(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::METHOD_NOT_ALLOWED, $content, $encoding);
    }

    public static function notAcceptable(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NOT_ACCEPTABLE, $content, $encoding);
    }

    public static function proxyAuthenticationRequired(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::PROXY_AUTHENTICATION_REQUIRED, $content, $encoding);
    }

    public static function requestTimeout(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::REQUEST_TIMEOUT, $content, $encoding);
    }

    public static function conflict(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::CONFLICT, $content, $encoding);
    }

    public static function gone(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::GONE, $content, $encoding);
    }

    public static function lengthRequired(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::LENGTH_REQUIRED, $content, $encoding);
    }

    public static function preconditionFailed(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::PRECONDITION_FAILED, $content, $encoding);
    }

    public static function payloadTooLarge(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::PAYLOAD_TOO_LARGE, $content, $encoding);
    }

    public static function uriTooLong(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::URI_TOO_LONG, $content, $encoding);
    }

    public static function unsupportedMediaType(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::UNSUPPORTED_MEDIA_TYPE, $content, $encoding);
    }

    public static function rangeNotSatisfiable(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::RANGE_NOT_SATISFIABLE, $content, $encoding);
    }

    public static function expectationFailed(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::EXPECTATION_FAILED, $content, $encoding);
    }

    public static function teapot(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::TEAPOT, $content, $encoding);
    }

    public static function misdirectedRequest(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::MISDIRECTED_REQUEST, $content, $encoding);
    }

    public static function unprocessableContent(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::UNPROCESSABLE_CONTENT, $content, $encoding);
    }

    public static function locked(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::LOCKED, $content, $encoding);
    }

    public static function failedDependency(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::FAILED_DEPENDENCY, $content, $encoding);
    }

    public static function tooEarly(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::TOO_EARLY, $content, $encoding);
    }

    public static function upgradeRequired(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::UPGRADE_REQUIRED, $content, $encoding);
    }

    public static function preconditionRequired(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::PRECONDITION_REQUIRED, $content, $encoding);
    }

    public static function tooManyRequests(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::TOO_MANY_REQUESTS, $content, $encoding);
    }

    public static function requestHeaderFieldsTooLarge(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::REQUEST_HEADER_FIELDS_TOO_LARGE, $content, $encoding);
    }

    public static function unavailableForLegalReasons(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::UNAVAILABLE_FOR_LEGAL_REASONS, $content, $encoding);
    }

    public static function internalServerError(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::INTERNAL_SERVER_ERROR, $content, $encoding);
    }

    public static function notImplemented(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NOT_IMPLEMENTED, $content, $encoding);
    }

    public static function badGateway(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::BAD_GATEWAY, $content, $encoding);
    }

    public static function serviceUnavailable(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::SERVICE_UNAVAILABLE, $content, $encoding);
    }

    public static function gatewayTimeout(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::GATEWAY_TIMEOUT, $content, $encoding);
    }

    public static function httpVersionNotSupported(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::HTTP_VERSION_NOT_SUPPORTED, $content, $encoding);
    }

    public static function variantAlsoNegotiates(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::VARIANT_ALSO_NEGOTIATES, $content, $encoding);
    }

    public static function insufficientStorage(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::INSUFFICIENT_STORAGE, $content, $encoding);
    }

    public static function loopDetected(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::LOOP_DETECTED, $content, $encoding);
    }

    public static function notExtended(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NOT_EXTENDED, $content, $encoding);
    }

    public static function networkAuthenticationRequired(mixed $content = null, string $encoding = 'default'): void
    {
        static::respond(static::NETWORK_AUTHENTICATION_REQUIRED, $content, $encoding);
    }

    public static function custom(string $content, string $contentType, int $statusCode = 200): void
    {
        http_response_code($statusCode);

        header('Content-Type: ' . $contentType);

        echo $content;

        exit;
    }

    protected static function redirect(int $statusCode, string $url): void
    {
        http_response_code($statusCode);

        header(sprintf('Location: %s', $url));

        exit;
    }

    protected static function respond(int $statusCode, mixed $content, string $encoding): void
    {
        http_response_code($statusCode);

        if (is_null($content)) {
            header('Content-Type: ');

            echo '';

            exit;
        }

        if (is_scalar($content)) {
            header('Content-Type: text/plain');

            echo strval($content);

            exit;
        }

        $encoding = !in_array($encoding, ['json', 'xml', 'url'])
            ? env('APP_DEFAULT_ENCODING')
            : $encoding;

        if ($encoding == 'json') {
            header('Content-Type: application/json');

            echo Json::encode($content);

            exit;
        }

        if ($encoding == 'xml') {
            header('Content-Type: text/xml');

            echo Xml::encode(
                $content,
                function (string $parent, string $key) use ($statusCode): string {
                    if (strtolower($parent) == 'trace' && $statusCode == 500) {
                        return 'frame';
                    }

                    return Strings::singularize($parent);
                }
            );

            exit;
        }

        if ($encoding == 'url') {
            header('Content-Type: application/x-www-form-urlencoded');

            echo UrlEncoding::encode($content);

            exit;
        }

        throw new EncodingException('unknown default encoding %s', $encoding);
    }
}
