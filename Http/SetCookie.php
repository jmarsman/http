<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Cookie;

class SetCookie
{
    /** @var string */
    private $name;
    /** @var string|null */
    private $value;
    /** @var int */
    private $expires = 0;
    /** @var int */
    private $maxAge = 0;
    /** @var string|null */
    private $path;
    /** @var string|null */
    private $domain;
    /** @var bool */
    private $secure = false;
    /** @var bool */
    private $httpOnly = false;

    private function __construct(string $name, ?string $value = null)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getValue() : ?string
    {
        return $this->value;
    }

    public function getExpires() : int
    {
        return $this->expires;
    }

    public function getMaxAge() : int
    {
        return $this->maxAge;
    }

    public function getPath() : ?string
    {
        return $this->path;
    }

    public function getDomain() : ?string
    {
        return $this->domain;
    }

    public function getSecure() : bool
    {
        return $this->secure;
    }

    public function getHttpOnly() : bool
    {
        return $this->httpOnly;
    }

    public function withValue(?string $value = null) : self
    {
        $clone = clone($this);

        $clone->value = $value;

        return $clone;
    }

    private function resolveExpires($expires = null) : int
    {
        if ($expires === null) {
            return 0;
        }

        if ($expires instanceof \DateTimeInterface) {
            return $expires->getTimestamp();
        }

        if (\is_numeric($expires)) {
            return (int) $expires;
        }

        $time = \strtotime($expires);

        if (! \is_int($time)) {
            throw new \InvalidArgumentException(\sprintf('Invalid expires "%s" provided', $expires));
        }

        return $time;
    }

    public function withExpires($expires = null) : self
    {
        $expires = $this->resolveExpires($expires);

        $clone = clone($this);

        $clone->expires = $expires;

        return $clone;
    }

    public function rememberForever() : self
    {
        return $this->withExpires(new \DateTime('+5 years'));
    }

    public function expire() : self
    {
        return $this->withExpires(new \DateTime('-5 years'));
    }

    public function withMaxAge(?int $maxAge = null) : self
    {
        $clone = clone($this);

        $clone->maxAge = (int) $maxAge;

        return $clone;
    }

    public function withPath(?string $path = null) : self
    {
        $clone = clone($this);

        $clone->path = $path;

        return $clone;
    }

    public function withDomain(?string $domain = null) : self
    {
        $clone = clone($this);

        $clone->domain = $domain;

        return $clone;
    }

    public function withSecure(bool $secure = true) : self
    {
        $clone = clone($this);

        $clone->secure = $secure;

        return $clone;
    }

    public function withHttpOnly(bool $httpOnly = true) : self
    {
        $clone = clone($this);

        $clone->httpOnly = $httpOnly;

        return $clone;
    }

    public function __toString() : string
    {
        $cookieStringParts = [
            \urlencode($this->name) . '=' . \urlencode((string) $this->value),
        ];

        $cookieStringParts = $this->appendFormattedDomainPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedPathPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedExpiresPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedMaxAgePartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedSecurePartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedHttpOnlyPartIfSet($cookieStringParts);

        return \implode('; ', $cookieStringParts);
    }

    public static function create(string $name, ?string $value = null) : self
    {
        return new self($name, $value);
    }

    public static function createRememberedForever(string $name, ?string $value = null) : self
    {
        return self::create($name, $value)->rememberForever();
    }

    public static function createExpired(string $name) : self
    {
        return self::create($name)->expire();
    }

    public static function fromString(string $string) : self
    {
        $rawAttributes = Cookie::splitOnDelimiter($string);

        $rawAttribute = \array_shift($rawAttributes);

        if (! \is_string($rawAttribute)) {
            throw new \InvalidArgumentException(\sprintf(
                'The provided cookie string "%s" must have at least one attribute',
                $string
            ));
        }

        list($cookieName, $cookieValue) = Cookie::splitPair($rawAttribute);

        $setCookie = new self($cookieName);

        if ($cookieValue !== null) {
            $setCookie = $setCookie->withValue($cookieValue);
        }

        while ($rawAttribute = \array_shift($rawAttributes)) {
            $rawAttributePair = \explode('=', $rawAttribute, 2);

            $attributeKey   = $rawAttributePair[0];
            $attributeValue = \count($rawAttributePair) > 1 ? $rawAttributePair[1] : null;

            $attributeKey = \strtolower($attributeKey);

            switch ($attributeKey) {
                case 'expires':
                    $setCookie = $setCookie->withExpires($attributeValue);
                    break;
                case 'max-age':
                    $setCookie = $setCookie->withMaxAge((int) $attributeValue);
                    break;
                case 'domain':
                    $setCookie = $setCookie->withDomain($attributeValue);
                    break;
                case 'path':
                    $setCookie = $setCookie->withPath($attributeValue);
                    break;
                case 'secure':
                    $setCookie = $setCookie->withSecure(true);
                    break;
                case 'httponly':
                    $setCookie = $setCookie->withHttpOnly(true);
                    break;
            }
        }

        return $setCookie;
    }

    private function appendFormattedDomainPartIfSet(array $cookieStringParts) : array
    {
        if ($this->domain) {
            $cookieStringParts[] = \sprintf('Domain=%s', $this->domain);
        }

        return $cookieStringParts;
    }

    private function appendFormattedPathPartIfSet(array $cookieStringParts) : array
    {
        if ($this->path) {
            $cookieStringParts[] = \sprintf('Path=%s', $this->path);
        }

        return $cookieStringParts;
    }

    private function appendFormattedExpiresPartIfSet(array $cookieStringParts) : array
    {
        if ($this->expires) {
            $cookieStringParts[] = \sprintf('Expires=%s', \gmdate('D, d M Y H:i:s T', $this->expires));
        }

        return $cookieStringParts;
    }

     private function appendFormattedMaxAgePartIfSet(array $cookieStringParts) : array
    {
        if ($this->maxAge) {
            $cookieStringParts[] = \sprintf('Max-Age=%s', $this->maxAge);
        }

        return $cookieStringParts;
    }

    private function appendFormattedSecurePartIfSet(array $cookieStringParts) : array
    {
        if ($this->secure) {
            $cookieStringParts[] = 'Secure';
        }

        return $cookieStringParts;
    }

    private function appendFormattedHttpOnlyPartIfSet(array $cookieStringParts) : array
    {
        if ($this->httpOnly) {
            $cookieStringParts[] = 'HttpOnly';
        }

        return $cookieStringParts;
    }
}
