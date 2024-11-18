<?php
declare(strict_types=1);

namespace CSApp\SteamAuth;

use ErrorException;

/**
 * This class provides a simple interface for OpenID 1.1/2.0 authentication.
 *
 * It requires cURL or HTTP/HTTPS stream wrappers enabled.
 *
 * Class cleaned up and refactored by radek203 for PHP >= 8.0 and specific use case.
 * Netherless, the code still doesn't look good and need to be refactored.
 *
 * @link        https://github.com/iignatov/LightOpenID         GitHub Repo
 * @author      Mewp <mewp151 at gmail dot com>
 * @copyright   Copyright (c) 2013 Mewp
 * @license     http://opensource.org/licenses/mit-license.php  MIT License
 */
class LightOpenID
{
    private int $curlConnectTimeOut = 30;
    private int $curlTimeOut = 30;
    private array $data;
    private string $returnUrl;
    private ?string $server = NULL;
    private ?string $setupUrl = NULL;
    private string $trustRoot;
    private string $userAgent = 'LightOpenID';
    private array $headers = [];
    private bool $sreg = false;
    private bool $ax = false;
    private bool $identifierSelect = false;
    private string $claimedId;

    public function __construct(string $host)
    {
        $this->set_realm($host);

        $uri = rtrim(preg_replace('#((?<=\?)|&)openid\.[^&]+#', '', $_SERVER['REQUEST_URI']), '?');
        $this->returnUrl = $this->trustRoot . $uri;

        $this->data = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
    }

    private function set_realm(string $uri): void
    {
        $realm = '';

        # Set a protocol, if not specified.
        $realm .= (($offset = strpos($uri, '://')) === false) ? $this->get_realm_protocol() : '';

        # Set the offset properly.
        $offset = (($offset !== false) ? $offset + 3 : 0);

        # Get only the root, without the path.
        $realm .= (($end = strpos($uri, '/', $offset)) === false) ? $uri : substr($uri, 0, $end);

        $this->trustRoot = $realm;
    }

    private function get_realm_protocol(): string
    {
        if (!empty($_SERVER['HTTPS'])) {
            $useSecureProtocol = ($_SERVER['HTTPS'] !== 'off');
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $useSecureProtocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        } elseif (isset($_SERVER['HTTP__WSSC'])) {
            $useSecureProtocol = ($_SERVER['HTTP__WSSC'] === 'https');
        } else {
            $useSecureProtocol = false;
        }

        return $useSecureProtocol ? 'https://' : 'http://';
    }

    /**
     * Returns authentication url. Usually, you want to redirect your user to it.
     * @return string|NULL The authentication url.
     * @throws ErrorException
     */
    public function authUrl(): ?string
    {
        if ($this->setupUrl) return $this->setupUrl;
        if (!$this->server) $this->discover($this->getIdentity());

        return $this->authUrl_v2();
    }

    /**
     * Performs Yadis and HTML discovery. Normally not used.
     * @param string $url URL.
     * @return false|string OP Endpoint (i.e. OpenID provider address).
     * @throws ErrorException
     */
    private function discover(string $url): false|string
    {
        if (!$url) throw new ErrorException('No identity supplied.');
        # Use xri.net proxy to resolve i-name identities
        if (!preg_match('#^https?:#', $url)) {
            $url = "https://xri.net/$url";
        }

        # We'll jump a maximum of 5 times, to avoid endless redirections.
        for ($i = 0; $i < 5; $i++) {
            $headers = $this->request($url, 'HEAD', [], true);

            $next = false;
            if (isset($headers['x-xrds-location'])) {
                $url = $this->build_url(parse_url($url), parse_url(trim($headers['x-xrds-location'])));
                $next = true;
            }

            if (isset($headers['content-type']) && $this->is_allowed_type($headers['content-type'])) {
                # Found an XRDS document, now let's find the server, and optionally delegate.
                $content = $this->request($url);

                preg_match_all('#<Service.*?>(.*?)</Service>#s', $content, $m);
                foreach ($m[1] as $content) {
                    $content = ' ' . $content; # The space is added, so that strpos doesn't return 0.

                    # OpenID 2
                    $ns = preg_quote('http://specs.openid.net/auth/2.0/', '#');
                    if (preg_match('#<Type>\s*' . $ns . '(server|signon)\s*</Type>#s', $content, $type)) {
                        if ($type[1] === 'server') $this->identifierSelect = true;

                        preg_match('#<URI.*?>(.*)</URI>#', $content, $server);
                        preg_match('#<(Local|Canonical)ID>(.*)</\1ID>#', $content, $delegate);
                        if (empty($server)) {
                            return false;
                        }
                        # Does the server advertise support for either AX or SREG?
                        $this->ax = boolval(strpos($content, '<Type>http://openid.net/srv/ax/1.0</Type>'));
                        $this->sreg = strpos($content, '<Type>http://openid.net/sreg/1.0</Type>') || strpos($content, '<Type>http://openid.net/extensions/sreg/1.1</Type>');

                        $server = $server[1];
                        if (isset($delegate[2])) $this->setIdentity(trim($delegate[2]));

                        $this->server = $server;
                        return $server;
                    }

                    # OpenID 1.1
                    $ns = preg_quote('http://openid.net/signon/1.1', '#');
                    if (preg_match('#<Type>\s*' . $ns . '\s*</Type>#s', $content)) {

                        preg_match('#<URI.*?>(.*)</URI>#', $content, $server);
                        preg_match('#<.*?Delegate>(.*)</.*?Delegate>#', $content, $delegate);
                        if (empty($server)) {
                            return false;
                        }
                        # AX can be used only with OpenID 2.0, so checking only SREG
                        $this->sreg = strpos($content, '<Type>http://openid.net/sreg/1.0</Type>') || strpos($content, '<Type>http://openid.net/extensions/sreg/1.1</Type>');

                        $server = $server[1];
                        if (isset($delegate[1])) $this->setIdentity($delegate[1]);

                        $this->server = $server;
                        return $server;
                    }
                }

                $content = NULL;
                break;
            }
            if ($next) continue;

            # There are no relevant information in headers, so we search the body.
            $content = $this->request($url, 'GET', [], true);

            if (isset($this->headers['x-xrds-location'])) {
                $url = $this->build_url(parse_url($url), parse_url(trim($this->headers['x-xrds-location'])));
                continue;
            }

            $location = $this->htmlTag($content, 'meta', 'http-equiv', 'X-XRDS-Location', 'content');
            if ($location) {
                $url = $this->build_url(parse_url($url), parse_url($location));
                continue;
            }

            if (!$content) $content = $this->request($url);

            # At this point, the YADIS Discovery has failed, so we'll switch
            # to openid2 HTML discovery, then fallback to openid 1.1 discovery.
            $server = $this->htmlTag($content, 'link', 'rel', 'openid2.provider', 'href');
            $delegate = $this->htmlTag($content, 'link', 'rel', 'openid2.local_id', 'href');

            if (!$server) {
                # The same with openid 1.1
                $server = $this->htmlTag($content, 'link', 'rel', 'openid.server', 'href');
                $delegate = $this->htmlTag($content, 'link', 'rel', 'openid.delegate', 'href');
            }

            if ($server) {
                # We found an OpenID2 OP Endpoint
                if ($delegate) {
                    # We have also found an OP-Local ID.
                    $this->setIdentity($delegate);
                }
                $this->server = $server;
                return $server;
            }

            throw new ErrorException("No OpenID Server found at $url", 404);
        }
        throw new ErrorException('Endless redirection!', 500);
    }

    /**
     * @throws ErrorException
     */
    private function request(string $url, string $method = 'GET', array $params = [], bool $updateClaimedId = false): bool|array|string
    {
        return $this->request_curl($url, $method, $params, $updateClaimedId);
    }

    /**
     * @throws ErrorException
     */
    private function request_curl(string $url = NULL, string $method = 'GET', array $params = [], bool $updateClaimedId = NULL): bool|array|string
    {
        $params = http_build_query($params, '', '&');
        $curl = curl_init($url . ($method === 'GET' && $params ? '?' . $params : ''));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/x-www-form-urlencoded']);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/xrds+xml, */*']);
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, $this->curlTimeOut); // defaults to infinite
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->curlConnectTimeOut); // defaults to 300s

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        } elseif ($method === 'HEAD') {
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_NOBODY, true);
        } else {
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        $response = curl_exec($curl);

        if ($method === 'HEAD' && curl_getinfo($curl, CURLINFO_HTTP_CODE) == 405) {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            $response = curl_exec($curl);
            $response = substr($response, 0, strpos($response, "\r\n\r\n"));
        }

        if ($method === 'HEAD' || $method === 'GET') {
            $headerResponse = $response;

            # If it's a GET request, we want to only parse the header part.
            if ($method === 'GET') {
                $headerResponse = substr($response, 0, strpos($response, "\r\n\r\n"));
            }

            $headers = [];
            foreach (explode("\n", $headerResponse) as $header) {
                $pos = strpos($header, ':');
                if ($pos !== false) {
                    $name = strtolower(trim(substr($header, 0, $pos)));
                    $headers[$name] = trim(substr($header, $pos + 1));
                }
            }

            if ($updateClaimedId) {
                # Update the claimed_id value in case of redirections.
                $effectiveUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
                # Ignore the fragment (some cURL versions don't handle it well).
                if (strtok($effectiveUrl, '#') !== strtok($url, '#')) {
                    $this->setIdentity($this->claimedId = $effectiveUrl);
                }
            }

            if ($method === 'HEAD') {
                return $headers;
            } else {
                $this->headers = $headers;
            }
        }

        if (curl_errno($curl)) {
            throw new ErrorException(curl_error($curl), curl_errno($curl));
        }

        return $response;
    }

    public function setIdentity(string $value): void
    {
        if (strlen($value = trim($value))) {
            if (preg_match('#^xri:/*#i', $value, $m)) {
                $value = substr($value, strlen($m[0]));
            } elseif (!preg_match('/^(?:[=@+!]|https?:)/i', $value)) {
                $value = "http://$value";
            }
            if (preg_match('#^https?://[^/]+$#i', $value, $m)) {
                $value .= '/';
            }
        }
        $this->claimedId = $value;
    }

    private function build_url($url, $parts): string
    {
        if (isset($url['query'], $parts['query'])) {
            $parts['query'] = $url['query'] . '&' . $parts['query'];
        }

        $url = $parts + $url;
        return $url['scheme'] . '://'
            . (empty($url['username']) ? ''
                : (empty($url['password']) ? "{$url['username']}@"
                    : "{$url['username']}:{$url['password']}@"))
            . $url['host']
            . (empty($url['port']) ? '' : ":{$url['port']}")
            . (empty($url['path']) ? '' : $url['path'])
            . (empty($url['query']) ? '' : "?{$url['query']}")
            . (empty($url['fragment']) ? '' : "#{$url['fragment']}");
    }

    private function is_allowed_type(string $contentType): bool
    {
        # Apparently, some providers return XRDS documents as text/html.
        # While it is against the spec, allowing this here shouldn't break
        # compatibility with anything.
        $allowedTypes = ['application/xrds+xml', 'text/xml'];

        # Only allow text/html content type for the Yahoo logins, since
        # it might cause an endless redirection for the other providers.
        if ($this->get_provider_name($this->claimedId) === 'yahoo') {
            $allowedTypes[] = 'text/html';
        }

        foreach ($allowedTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    private function get_provider_name(string $providerUrl): string
    {
        $result = '';

        if (!empty($providerUrl)) {
            $tokens = array_reverse(explode('.', parse_url($providerUrl, PHP_URL_HOST)));
            $result = strtolower((count($tokens) > 1 && strlen($tokens[1]) > 3) ? $tokens[1] : (count($tokens) > 2 ? $tokens[2] : ''));
        }

        return $result;
    }

    /**
     * Helper function used to scan for <meta>/<link> tags and extract information
     * from them
     */
    private function htmlTag(string $content, string $tag, string $attrName, string $attrValue, string $valueName)
    {
        preg_match_all("#<{$tag}[^>]*$attrName=['\"].*?$attrValue.*?['\"][^>]*$valueName=['\"](.+?)['\"][^>]*/?>#i", $content, $matches1);
        preg_match_all("#<{$tag}[^>]*$valueName=['\"](.+?)['\"][^>]*$attrName=['\"].*?$attrValue.*?['\"][^>]*/?>#i", $content, $matches2);

        $result = array_merge($matches1[1], $matches2[1]);
        return empty($result) ? false : $result[0];
    }

    public function getIdentity(): string
    {
        return $this->claimedId;
    }

    private function authUrl_v2(): string
    {
        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $this->returnUrl,
            'openid.realm' => $this->trustRoot,
        ];

        if (!$this->ax && !$this->sreg) {
            # If OP doesn't advertise either SREG, nor AX, let's send them both
            # in worst case we don't get anything in return.
            $params += $this->sregParams();
        }

        if ($this->identifierSelect) {
            $params['openid.identity'] = $params['openid.claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
        }

        return $this->build_url(parse_url($this->server), ['query' => http_build_query($params, '', '&')]);
    }

    private function sregParams(): array
    {
        $params = [];
        # We always use SREG 1.1, even if the server is advertising only support for 1.0.
        # That's because it's fully backwards compatible with 1.0, and some providers
        # advertise 1.0 even if they accept only 1.1. One such provider is myopenid.com
        $params['openid.ns.sreg'] = 'http://openid.net/extensions/sreg/1.1';

        return $params;
    }

    /**
     * Performs OpenID verification with the OP.
     * @return Bool Whether the verification was successful.
     * @throws ErrorException
     */
    public function validate(): bool
    {
        # If the request was using immediate mode, a failure may be reported
        # by presenting user_setup_url (for 1.1) or reporting
        # mode 'setup_needed' (for 2.0). Also catching all modes other than
        # id_res, in order to avoid throwing errors.
        if (isset($this->data['openid_user_setup_url'])) {
            $this->setupUrl = $this->data['openid_user_setup_url'];
            return false;
        }
        if ($this->getMode() !== 'id_res') {
            return false;
        }

        $this->claimedId = $this->data['openid_claimed_id'] ?? $this->data['openid_identity'];
        $params = [
            'openid.assoc_handle' => $this->data['openid_assoc_handle'],
            'openid.signed' => $this->data['openid_signed'],
            'openid.sig' => $this->data['openid_sig'],
        ];

        if (isset($this->data['openid_ns'])) {
            # We're dealing with an OpenID 2.0 server, so let's set a ns
            # Even though we should know location of the endpoint,
            # we still need to verify it by discovery, so $server is not set here
            $params['openid.ns'] = 'http://specs.openid.net/auth/2.0';
        } elseif (isset($this->data['openid_claimed_id']) && $this->data['openid_claimed_id'] !== $this->data['openid_identity']) {
            # If it's an OpenID 1 provider, and we've got claimed_id,
            # we have to append it to the returnUrl, like authUrl_v1 does.
            $this->returnUrl .= (strpos($this->returnUrl, '?') ? '&' : '?') . 'openid.claimed_id=' . $this->claimedId;
        }

        if ($this->data['openid_return_to'] !== $this->returnUrl) {
            # The return_to url must match the url of current request.
            # I'm assuming that no one will set the returnUrl to something that doesn't make sense.
            return false;
        }

        $server = $this->discover($this->claimedId);

        foreach (explode(',', $this->data['openid_signed']) as $item) {
            # Checking whether magic_quotes_gpc is turned on, because
            # the function may fail if it is. For example, when fetching
            # AX namePerson, it might contain an apostrophe, which will be escaped.
            # In such case, validation would fail, since we'd send different data than OP
            # wants to verify. stripslashes() should solve that problem, but we can't
            # use it when magic_quotes is off.
            $params['openid.' . $item] = $this->data['openid_' . str_replace('.', '_', $item)];

        }

        $params['openid.mode'] = 'check_authentication';

        $response = $this->request($server, 'POST', $params);

        return boolval(preg_match('/is_valid\s*:\s*true/i', $response));
    }

    public function getMode()
    {
        return empty($this->data['openid_mode']) ? NULL : $this->data['openid_mode'];
    }
}