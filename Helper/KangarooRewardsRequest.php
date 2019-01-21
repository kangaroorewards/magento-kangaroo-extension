<?php
/**
 * Prepare for send request to kangaroo api
 */
namespace Kangaroorewards\Core\Helper;
use OAuth\Common\Http\Uri\Uri;

/**
 * Class KangarooRewardsRequest
 *
 * @package Kangaroorewards\Core\Helper
 */
class KangarooRewardsRequest
{
    private $_token;
    private static $_baseUri = 'https://integ-api-dev.traktrok.com/';
    private $_timeout = 30;

    /**
     * KangarooRewardsRequest constructor.
     *
     * @param string $key
     */
    public function __construct($key = '')
    {
        $this->_token = $key;
    }

    /**
     * @param $path
     * @param array $data
     * @return mixed
     */
    public function post($path, $data = array())
    {
        return $this->_request(
            \Zend\Http\Request::METHOD_POST,
            $path,
            $data
        );
    }

    /**
     * @param $method
     * @param $path
     * @param $data
     * @return mixed
     */
    private function _request($method, $path, $data)
    {
        $uriPath = self::getKangarooAPIUrl() . '/' . $path;
        $uri = new Uri($uriPath);

        $request = new \Zend\Http\Request();
        if ($this->_token != '') {
            $httpHeaders = new \Zend\Http\Headers();
            $httpHeaders->addHeaders(
                [
                'x-signature' => $this->_getSignature($this->_token, $uri, $data),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
                ]
            );
            $request->setHeaders($httpHeaders);
        }
        $request->setUri($uriPath);
        $request->setMethod($method);

        $params = new \Zend\Stdlib\Parameters($data);
        $request->setQuery($params);

        $client = new \Zend\Http\Client();
        $options = [
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => [CURLOPT_FOLLOWLOCATION => true],
            'maxredirects' => 0,
            'timeout' => $this->_timeout
        ];

        $client->setOptions($options);
        $response = $client->send($request);
        return $response;
    }

    /**
     * @param string $signKey
     * @param Uri    $uri
     * @param array  $params
     * @param string $method
     * @return string
     */
    private function _getSignature(string $signKey,
        Uri $uri,
        array $params,
        $method = 'POST'
    ) {
        $queryStringData = !$uri->getQuery() ? [] : array_reduce(
            explode('&', $uri->getQuery()),
            function ($carry, $item) {
                list($key, $value) = explode('=', $item, 2);
                $carry[rawurldecode($key)] = rawurldecode($value);
                return $carry;
            },
            []
        );

        foreach (array_merge($queryStringData, $params) as $key => $value) {
            $signatureData[rawurlencode($key)] = rawurlencode($value);
        }

        ksort($signatureData);

        $baseString = strtoupper($method) . '&';
        $baseString .= rawurlencode($this->_buildSignatureDataString($signatureData));

        return base64_encode(
            hash_hmac(
                'sha512',
                $baseString,
                rawurlencode($signKey),
                true
            )
        );
    }

    /**
     * @param array $signatureData
     *
     * @return string
     */
    private function _buildSignatureDataString(array $signatureData)
    {
        $signatureString = '';
        $delimiter = '';
        foreach ($signatureData as $key => $value) {
            $signatureString .= $delimiter . $key . '=' . $value;

            $delimiter = '&';
        }

        return $signatureString;
    }

    /**
     * @return string
     */
    public static function getKangarooAPIUrl()
    {
        $url = rtrim(self::$_baseUri, '/');
        return $url;
    }
}
