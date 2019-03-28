<?php
/**
 * Prepare for send request to kangaroo api
 */
namespace Kangaroorewards\Core\Helper;
use OAuth\Common\Http\Uri\Uri;
use Kangaroorewards\Core\Model\KangarooCredentialFactory;

/**
 * Class KangarooRewardsRequest
 *
 * @package Kangaroorewards\Core\Helper
 */
class KangarooRewardsRequest
{
    private $_token;
    private static $_baseUri = 'https://integrations.kangarooapis.com/';
    private $_timeout = 240;

    /**
     * @var KangarooCredentialFactory
     */
    protected $credentialFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * KangarooRewardsRequest constructor.
     * @param KangarooCredentialFactory $credentialFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        KangarooCredentialFactory $credentialFactory,
        \Psr\Log\LoggerInterface $logger)
    {
        $this->credentialFactory = $credentialFactory;
        $this->logger = $logger;
    }

    /**
     * @param $path
     * @param array $data
     * @return mixed
     */
    public function post($path, $data = array())
    {
        $token = $this->getKangarooAccessToken();
        return $this->_request(
            \Zend\Http\Request::METHOD_POST,
            $path,
            $data,
            $token
        );
    }
    /**
     * @param $path
     * @param array $data
     * @return mixed
     */
    public function get($path, $data = array())
    {
        $token = $this->getKangarooAccessToken();
        return $this->_request(
            \Zend\Http\Request::METHOD_GET,
            $path,
            $data,
            $token
        );
    }

    /**
     * @param $method
     * @param $path
     * @param $data
     * @param string $key
     * @return \Zend\Http\Response
     */
    private function _request($method, $path, $data, $key = '')
    {
        $uriPath = self::getKangarooAPIUrl() . '/' . $path;
        $uri = new Uri($uriPath);

        $request = new \Zend\Http\Request();

        /*
         * This is for the first version signature
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
        */
        if ($key != '') {
            $httpHeaders = new \Zend\Http\Headers();
            $httpHeaders->addHeaders(
                [
                    'Authorization' => $key
                ]
            );
            $request->setHeaders($httpHeaders);
        }

        $request->setUri($uriPath);
        $request->setMethod($method);


        $params = new \Zend\Stdlib\Parameters($data);
        if($method == \Zend\Http\Request::METHOD_POST) {
            $request->setPost($params);
        }
        elseif($method == \Zend\Http\Request::METHOD_GET) {
            $request->setQuery($params);
        }

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

    /**
     * @return string
     * @throws \Exception
     */
    public function getKangarooAccessToken()
    {
        $existingCredential = $this->credentialFactory->create()->load(1);
        $item = $existingCredential->getData();
        if (isset($item)) {
            try {
                if (isset($item['access_token']) && $item['access_token'] != '') {
                    if ($item['updated_at'] + $item['expires_in'] > time()) {
                        return $item['access_token'];
                    }
                }

                return $this->getAccessToken($existingCredential);

            } catch (\Exception $e) {
                $this->logger->info('[KangarooRewards] - Can not get access token');
                return '';
            }
        }
        return '';
    }

    /**
     * @param $credential
     * @return string
     */
    private function getAccessToken($credential)
    {
        $item = $credential->getData();
        $sendData = [
            'grant_type' => 'client_credentials',
            'client_id' => $item['client_id'],
            'client_secret' => $item['client_secret'],
            'scope' => $item['scope'],
        ];

        $response = $this->_request(\Zend\Http\Request::METHOD_POST, 'oauth/token', $sendData);
        $this->logger->info('[KangarooRewards]-UpdateAccessToken: ' . $response);
        if ($response->isSuccess()) {
            $object = json_decode($response->getBody());
            if (isset($object->access_token)) {
                $credential->setAccessToken($object->access_token);
                $credential->setExpiresIn($object->expires_in);
                $credential->save();
                return $object->access_token;
            }
        }
        return '';
    }

}
