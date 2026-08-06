<?php

namespace App\Services\JobScraping;

use Closure;

class HttpClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    /** @var string[] */
    private array $cookies = [];

    /**
     * @param  (Closure(string): void)|null  $logger
     */
    public function __construct(
        private readonly int $delayMs = 1500,
        private readonly ?Closure $logger = null,
    ) {}

    /**
     * @param  string[]  $extraHeaders
     */
    public function get(string $url, array $extraHeaders = []): ?string
    {
        return $this->request($url, $extraHeaders);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  string[]  $extraHeaders
     */
    public function postJson(string $url, array $payload, array $extraHeaders = []): ?string
    {
        $json = json_encode($payload);

        return is_string($json)
            ? $this->request($url, array_merge([
                'Accept: application/json',
                'Content-Type: application/json; charset=utf-8',
            ], $extraHeaders), $json)
            : null;
    }

    /**
     * Returns the response body even for a 4xx response. Useful for services
     * that return a request token on their initial rejected request.
     *
     * @param  array<string, mixed>  $payload
     */
    public function postJsonIncludingErrors(string $url, array $payload): ?string
    {
        $json = json_encode($payload);

        return is_string($json)
            ? $this->request($url, [
                'Accept: application/json',
                'Content-Type: application/json; charset=utf-8',
            ], $json, true)
            : null;
    }

    /**
     * @param  string[]  $extraHeaders
     */
    private function request(
        string $url,
        array $extraHeaders = [],
        ?string $postBody = null,
        bool $acceptErrorResponse = false,
    ): ?string {
        if ($url === '') {
            return null;
        }

        usleep($this->delayMs * 1000);

        $handle = curl_init($url);
        if ($handle === false) {
            return null;
        }

        $headers = array_merge([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Cache-Control: no-cache',
        ], $extraHeaders);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_COOKIEFILE => '',
        ];
        if ($postBody !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postBody;
        }
        curl_setopt_array($handle, $options);

        foreach ($this->cookies as $cookie) {
            curl_setopt($handle, CURLOPT_COOKIELIST, $cookie);
        }

        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $cookies = curl_getinfo($handle, CURLINFO_COOKIELIST);
        if (is_array($cookies)) {
            $this->cookies = $cookies;
        }
        $error = curl_error($handle);
        curl_close($handle);

        if ($this->logger !== null) {
            ($this->logger)(sprintf('[HTTP %d] %s', $status, $url));
            if ($error !== '') {
                ($this->logger)("cURL error: {$error}");
            }
        }

        if (! is_string($body) || $status < 200 || (! $acceptErrorResponse && $status >= 400)) {
            return null;
        }

        return $body;
    }
}
