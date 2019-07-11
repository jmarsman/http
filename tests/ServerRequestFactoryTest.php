<?php

namespace Async\Tests;

use Async\Http\ServerRequestFactory;
use Psr\Http\Message\UriInterface;
use PHPUnit\Framework\TestCase;

class ServerRequestFactoryTest extends TestCase
{
    public function testCreateServerRequest()
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', 'http://domain.tld:9090/subdir?test=true#phpunit');
        $this->assertInstanceOf('Psr\\Http\\Message\\ServerRequestInterface', $request);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('domain.tld', $request->getUri()->getHost());
        $this->assertEquals(9090, $request->getUri()->getPort());
        $this->assertEquals('http://domain.tld:9090/subdir?test=true#phpunit', (string)$request->getUri());
    }

    public function testCreateServerRequestFromArray()
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequestFromArray(array(
            'CONTENT_LENGTH' => '128',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_HOST' => 'domain.tld:9090',
            'HTTP_INVALID' => null,
            'HTTP_X_REWRITE_URL' => '/some-fancy-url',
            'HTTP_X_ORIGINAL_URL' => '/subdir?test=true#phpunit',
            'QUERY_STRING' => 'test=true',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => 'http://domain.tld:9090/subdir#phpunit',
            'SERVER_PORT' => '9090',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ));
        $this->assertInstanceOf('Psr\\Http\\Message\\ServerRequestInterface', $request);
        $this->assertEquals('1.0', $request->getProtocolVersion());
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $request->getUri());
        $this->assertEquals('128', $request->getHeaderLine('Content-Length'));
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('domain.tld', $request->getUri()->getHost());
        $this->assertEquals(9090, $request->getUri()->getPort());
        $this->assertEquals('http://domain.tld:9090/subdir?test=true#phpunit', (string)$request->getUri());
    }
}