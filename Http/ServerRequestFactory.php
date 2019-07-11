<?php

namespace Async\Http;

use Async\Http\UriFactory;
use Async\Http\ServerRequest;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Class ServerRequestFactory
 * 
 * @package Async\Http
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (\is_string($uri)) {
            $factory = new UriFactory();
            $uri = $factory->createUri($uri);
        }
        return new ServerRequest($method, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequestFromArray(array $server)
    {
        $body = self::getPhpInputStream();
        $method = $server['REQUEST_METHOD'];
        $protocolVersion = self::getProtocolVersion($server);
        $uri = self::getUri($server);
        $request = new ServerRequest($method, $uri);
        $request = $request->withBody($body)
            ->withProtocolVersion($protocolVersion)
            ->withServerParams($server);
        $headers = self::getHeaders($server);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $request;
    }

    /**
     * @param array $server
     * @return array
     */
    protected static function getHeaders(array $server)
    {
        $headers = array();
        static $pick = array('CONTENT_', 'HTTP_');
        foreach ($server as $key => $value) {
            if (!$value) {
                continue;
            }
            if (\strpos($key, 'REDIRECT_') === 0) {
                $key = \substr($key, 9);
                if (\array_key_exists($key, $server)) {
                    continue;
                }
            }
            foreach ($pick as $prefix) {
                if (\strpos($key, $prefix) === 0) {
                    if ($prefix !== $pick[0]) {
                        $key = \substr($key, \strlen($prefix));
                    }
                    $key = \strtolower(\strtr($key, '_', '-'));
                    $headers[$key] = $value;
                    continue;
                }
            }
        }
        return $headers;
    }

    /**
     * @return StreamInterface
     */
    protected static function getPhpInputStream()
    {
        $temp = \fopen('php://temp', 'w+');
        \stream_copy_to_stream($input = \fopen('php://input', 'r'), $temp);
        \fclose($input);
        $stream = new Stream($temp);
        $stream->rewind();
        return $stream;
    }

    /**
     * @param array $server
     * @return string
     */
    protected static function getProtocolVersion(array $server)
    {
        if (isset($server['SERVER_PROTOCOL'])) {
            return \str_replace('HTTP/', '', $server['SERVER_PROTOCOL']);
        }
        return '1.1';
    }

    /**
     * @param array $server
     * @return UriInterface
     */
    public static function getUri(array $server)
    {
        $uri = new Uri();
        $scheme = isset($server['HTTPS']) && ($server['HTTPS'] === 'on') ? 'https' : 'http';
        $uri = $uri->withScheme($scheme);
        if (\array_key_exists('HTTP_HOST', $server)) {
            $host = $server['HTTP_HOST'];
            if (\preg_match('~(?P<host>[^:]+):(?P<port>\d+)$~', $host, $matches)) {
                $uri = $uri->withHost($matches['host'])
                    ->withPort((int)$matches['port']);
            } else {
                $uri = $uri->withHost($host);
            }
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
            if (\array_key_exists('SERVER_PORT', $server)) {
                $uri = $uri->withPort((int)$server['SERVER_PORT']);
            }
        }
        $path = null;
        if (isset($server['REQUEST_URI'])) {
            $path = $server['REQUEST_URI'];
        }
        if (isset($server['HTTP_X_REWRITE_URL'])) {
            $path = $server['HTTP_X_REWRITE_URL'];
        }
        if (isset($server['HTTP_X_ORIGINAL_URL'])) {
            $path = $server['HTTP_X_ORIGINAL_URL'];
        }
        if ($path !== null) {
            if (\preg_match('~^[^/:]+://[^/]+(?P<path>.*)$~', $path, $matches)) {
                $path = $matches['path'];
            }
        } elseif (isset($server['ORIG_PATH_INFO'])) {
            $path = $server['ORIG_PATH_INFO'];
        }
        if (empty($path)) {
            $path = '/';
        }
        if (\stripos($path, '#') !== false) {
            list($path, $fragment) = \explode('#', $path, 2);
            $uri = $uri->withFragment($fragment);
        }
        if (($i = stripos($path, '?')) !== false) {
            $path = substr($path, 0, $i);
        }
        if (isset($server['QUERY_STRING'])) {
            $query = \ltrim($server['QUERY_STRING'], '?');
            $uri = $uri->withQuery($query);
        }
        $uri = $uri->withPath($path);
        return $uri;
    }
}