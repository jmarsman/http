<?php

namespace Async\Tests;

use Async\Http\UriFactory;
use PHPUnit\Framework\TestCase;

class UriFactoryTest extends TestCase
{
    public function testUri()
    {
        $factory = new UriFactory();
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $uri = $factory->createUri());
        $this->assertEmpty((string)$uri);
        $uri = $factory->createUri($url = 'http://someone:secret@domain.tld:9090/subdir?test=true#phpunit');
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $uri);
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('someone:secret', $uri->getUserInfo());
        $this->assertEquals('domain.tld', $uri->getHost());
        $this->assertEquals(9090, $uri->getPort());
        $this->assertEquals('someone:secret@domain.tld:9090', $uri->getAuthority());
        $this->assertEquals('/subdir', $uri->getPath());
        $this->assertEquals('test=true', $uri->getQuery());
        $this->assertEquals('phpunit', $uri->getFragment());
        $this->assertEquals($url, (string)$uri);
        $this->assertEquals($url, (string)$uri->withPath('subdir'));
    }

    public function testUriInvalidString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $factory = new UriFactory();
        $factory->createUri('http:///domain.tld/');
    }
}