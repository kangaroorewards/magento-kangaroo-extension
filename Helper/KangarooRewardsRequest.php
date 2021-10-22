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
    private static $_baseUri = 'https://integ-api-dev.traktrok.com/';
    private $_timeout = 240;

    /**
     * @var KangarooCredentialFactory
     */
    protected $credentialFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    private $lang;

    /**
     * KangarooRewardsRequest constructor.
     * @param KangarooCredentialFactory $credentialFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $lang
     */
    public function __construct(
        KangarooCredentialFactory $credentialFactory,
        \Psr\Log\LoggerInterface $logger,
        $lang = null
    )
    {
        $this->credentialFactory = $credentialFactory;
        $this->logger = $logger;
        $this->lang = $lang;
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
     * @param bool $retry
     * @return \Zend\Http\Response
     */
    private function _request($method, $path, $data, $key = '', $retry = true)
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
            $headers = [
                'Authorization' => $key
            ];
            if (isset($this->lang)) {
                $headers['Accept-Language'] = $this->lang;
            }
            $httpHeaders->addHeaders($headers);
            $request->setHeaders($httpHeaders);
        }

        $request->setUri($uriPath);
        $request->setMethod($method);


        $params = new \Zend\Stdlib\Parameters($data);
        if ($method == \Zend\Http\Request::METHOD_POST) {
            $request->setPost($params);
        } elseif ($method == \Zend\Http\Request::METHOD_GET) {
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
        if ($retry && $response->getStatus() == 401) {
            $this->logger->info('[KangarooRewards] - 401 error - path:' . $path);
            $key = $this->getKangarooAccessToken(true);
            return $this->_request($method, $path, $data, $key, $retry = false);
        }
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
     * @param $force
     * @throws \Exception
     */
    public function getKangarooAccessToken($force = false)
    {
        $existingCredential = $this->credentialFactory->create()->load(1);
        $item = $existingCredential->getData();
        if (isset($item)) {
            try {
                if (!$force && isset($item['access_token']) && $item['access_token'] != '') {
                    if ($item['updated_at'] + $item['expires_in'] > time()) {
                        $this->logger->info('[KangarooRewards] - Get access token from db.');
                        return $item['access_token'];
                    }
                }
                $this->logger->info('[KangarooRewards] - Before request a token.' . json_encode($item));
                return $this->getAccessToken($existingCredential);

            } catch (\Exception $e) {
                $this->logger->info('[KangarooRewards] - Can not get access token. ' . $e->getMessage());
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
        $this->logger->info('[KangarooRewards]- UpdateAccessToken');
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
