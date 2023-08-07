<?php

namespace VtexIntegration;

use Exception;

class VtexApiHelper
{
    private $baseUrl;
    private $headers;

    public function __construct($baseUrl, $credentials)
    {
        $this->baseUrl = $baseUrl;
        $this->headers = [
            "X-VTEX-API-AppKey:" . $credentials['appkey'],
            "X-VTEX-API-AppToken:" . $credentials['apptoken'],
        ];
    }

    public function get($endpoint, $queryParams = [])
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new Exception("API request failed with HTTP code $httpCode : " . $response);
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    public function post($endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($data !== null)
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new Exception("API request failed with HTTP code $httpCode : " . $response);
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
