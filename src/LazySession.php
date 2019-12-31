<?php

/**
 * @see       https://github.com/mezzio/mezzio-session for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-session/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Session;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Proxy to an underlying SessionInterface implementation.
 *
 * In order to delay parsing of session data until it is accessed, use this
 * class. It will call the composed SessionPersistenceInterface's createSession()
 * method only on access to any of the various session data methods; otherwise,
 * the session will not be accessed, and, in most cases, started.
 */
final class LazySession implements SessionInterface, SessionIdentifierAwareInterface
{
    /**
     * @var SessionPersistenceInterface
     */
    private $persistence;

    /**
     * @var null|SessionInterface
     */
    private $proxiedSession;

    /**
     * Request instance to use when calling $persistence->initializeSessionFromRequest()
     *
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(SessionPersistenceInterface $persistence, ServerRequestInterface $request)
    {
        $this->persistence = $persistence;
        $this->request = $request;
    }

    public function regenerate() : SessionInterface
    {
        $this->proxiedSession = $this->getProxiedSession()->regenerate();
        return $this;
    }

    public function isRegenerated() : bool
    {
        return $this->getProxiedSession()->isRegenerated();
    }

    public function toArray() : array
    {
        return $this->getProxiedSession()->toArray();
    }

    public function get(string $name, $default = null)
    {
        return $this->getProxiedSession()->get($name, $default);
    }

    public function has(string $name) : bool
    {
        return $this->getProxiedSession()->has($name);
    }

    public function set(string $name, $value) : void
    {
        $this->getProxiedSession()->set($name, $value);
    }

    public function unset(string $name) : void
    {
        $this->getProxiedSession()->unset($name);
    }

    public function clear() : void
    {
        $this->getProxiedSession()->clear();
    }

    public function hasChanged() : bool
    {
        if (! $this->proxiedSession) {
            return false;
        }

        $proxy = $this->getProxiedSession();

        if ($proxy->isRegenerated()) {
            return true;
        }

        return $proxy->hasChanged();
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.1.0
     */
    public function getId() : string
    {
        $proxiedSession = $this->getProxiedSession();
        return $proxiedSession instanceof SessionIdentifierAwareInterface
            ? $proxiedSession->getId()
            : '';
    }

    private function getProxiedSession() : SessionInterface
    {
        if ($this->proxiedSession) {
            return $this->proxiedSession;
        }

        $this->proxiedSession = $this->persistence->initializeSessionFromRequest($this->request);
        return $this->proxiedSession;
    }
}
