<?php

namespace vipnytt\SitemapParser;

/**
 * Trait UrlParser
 *
 * @package vipnytt\SitemapParser
 */
trait UrlParser
{
    /**
     * URL encoder according to RFC 3986
     * Returns a string containing the encoded URL with disallowed characters converted to their percentage encodings.
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @param string $url
     * @return string
     */
    protected function urlEncode($url)
    {
        $reserved = [
            ":" => '!%3A!ui',
            "/" => '!%2F!ui',
            "?" => '!%3F!ui',
            "#" => '!%23!ui',
            "[" => '!%5B!ui',
            "]" => '!%5D!ui',
            "@" => '!%40!ui',
            "!" => '!%21!ui',
            "$" => '!%24!ui',
            "&" => '!%26!ui',
            "'" => '!%27!ui',
            "(" => '!%28!ui',
            ")" => '!%29!ui',
            "*" => '!%2A!ui',
            "+" => '!%2B!ui',
            "," => '!%2C!ui',
            ";" => '!%3B!ui',
            "=" => '!%3D!ui',
            "%" => '!%25!ui'
        ];
        return preg_replace(array_values($reserved), array_keys($reserved), rawurlencode($url));
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    protected function urlValidate($url)
    {
        return (
            filter_var($url, FILTER_VALIDATE_URL) &&
            ($parsed = parse_url($url)) !== false &&
            $this->urlValidateScheme($parsed['scheme']) &&
            (
                (in_array($parsed['scheme'], ['http', 'https'], true) && $this->urlValidateHost($parsed['host']))
                ||
                (in_array($parsed['scheme'], ['file'], true) && $this->urlValidatePath($parsed['path']))
            ) &&
            $this->urlValidateAgainstBlackList($url)
        );
    }

    /**
     * Validate host name
     *
     * @link http://stackoverflow.com/questions/1755144/how-to-validate-domain-name-in-php
     *
     * @param  string $host
     * @return bool
     */
    protected static function urlValidateHost($host)
    {
        return (
            preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $host) //valid chars check
            && preg_match("/^.{1,253}$/", $host) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $host) //length of each label
        );
    }

    /**
     * Validate URL scheme
     *
     * @param  string $scheme
     * @return bool
     */
    protected static function urlValidateScheme($scheme)
    {
        return in_array($scheme, [
                'http',
                'https',
                'file'
            ]
        );
    }

    /**
     * Check if local file exists at given path.
     *
     * @param mixed $path
     * @return bool
     */
    public function urlValidatePath(mixed $path) {
        $result = file_exists($path);
        if ($result === false && PHP_OS === 'WINNT') {
            // try to reverse url encoding for windows paths:
            return file_exists(urldecode($path));
        }
        return $result;
    }

    protected function urlValidateAgainstBlackList($url)
    {
        if (empty($this->config['url_black_list'])) {
            return true;
        }

        return !in_array($url, $this->config['url_black_list'], true);
    }
}
