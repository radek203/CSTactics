<?php

declare(strict_types=1);

namespace CSApp;

class Request
{

    private array $get;
    private array $post;
    private array $server;
    private array $cookie;
    private array $session;

    public function __construct(array $get, array $post, array $server, array $cookie, array $session)
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->cookie = $cookie;
        $this->session = $session;
    }

    public function paramGet(string $name, $default = null)
    {
        return $this->get[$name] ?? $default;
    }

    public function paramPost(string $name, $default = null)
    {
        return $this->post[$name] ?? $default;
    }

    public function issetPost(string $name): bool
    {
        return isset($this->post[$name]);
    }

    public function paramServer(string $name, $default = null)
    {
        return $this->server[$name] ?? $default;
    }

    public function paramCookie(string $name, $default = null)
    {
        return $this->cookie[$name] ?? $default;
    }

    public function paramSession(string $name, $default = null)
    {
        return $this->session[$name] ?? $default;
    }

    public function setSession(string $key, $value): void
    {
        $_SESSION[$key] = $value;
        $this->session[$key] = $value;
    }

    public function unsetSession(string $key): void
    {
        unset($_SESSION[$key]);
        unset($this->session[$key]);
    }

    public function setCookie(string $key, $value): void
    {
        setcookie($key, $value, ['expires' => time() + (86400 * 365), 'samesite' => 'Lax']);
        $this->cookie[$key] = $value;
    }

    public function deleteCookie(string $key): void
    {
        setcookie($key, '', time() + (86400 * 365), '/');
    }

}
