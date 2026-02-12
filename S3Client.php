<?php

class S3Client {
    private $endpoint;
    private $accessKey;
    private $secretKey;
    private $bucket;
    private $region;

    public function __construct($endpoint, $accessKey, $secretKey, $bucket, $region = 'auto') {
        $this->endpoint = rtrim($endpoint, '/');
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
    }

    public function putObject($key, $filePath, $contentType = 'application/octet-stream') {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        return $this->request('PUT', $key, $content, $contentType);
    }

    public function deleteObject($key) {
        return $this->request('DELETE', $key);
    }

    private function request($method, $key, $content = '', $contentType = '') {
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $uri = '/' . $this->bucket . '/' . ltrim($key, '/');
        $url = $this->endpoint . $uri;

        $headers = [
            'Host' => $host,
            'x-amz-date' => gmdate('Ymd\THis\Z'),
            'x-amz-content-sha256' => hash('sha256', $content)
        ];

        if ($method === 'PUT' && $contentType) {
            $headers['Content-Type'] = $contentType;
        }

        $signatureHeaders = $this->getSignatureHeaders($method, $uri, $headers, $content);
        $headers = array_merge($headers, $signatureHeaders);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = "$k: $v";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
    }

    private function getSignatureHeaders($method, $uri, $headers, $content) {
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $timestamp = $headers['x-amz-date'];
        $date = substr($timestamp, 0, 8);

        // Canonical Headers
        $canonicalHeaders = '';
        $signedHeaders = [];
        ksort($headers);
        foreach ($headers as $k => $v) {
            $lowerK = strtolower($k);
            $canonicalHeaders .= $lowerK . ':' . trim($v) . "\n";
            $signedHeaders[] = $lowerK;
        }
        $signedHeadersString = implode(';', $signedHeaders);

        // Canonical Request
        $canonicalRequest = "$method\n"
            . "$uri\n"
            . "\n" // Query string (empty)
            . "$canonicalHeaders\n"
            . "$signedHeadersString\n"
            . $headers['x-amz-content-sha256'];

        // Credential Scope
        $credentialScope = "$date/{$this->region}/$service/aws4_request";

        // String to Sign
        $stringToSign = "$algorithm\n"
            . "$timestamp\n"
            . "$credentialScope\n"
            . hash('sha256', $canonicalRequest);

        // Signing Key
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $date, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        // Signature
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return [
            'Authorization' => "$algorithm Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeadersString, Signature=$signature"
        ];
    }
}
