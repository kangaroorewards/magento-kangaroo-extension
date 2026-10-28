<?php
/**
 * Sends product-state events to POST magento/product-state on the integration API.
 *
 * The payload only carries the identifiers (domain, store, SKU, timestamp). The integration API
 * reads the authoritative price/stock data from Magento with its own stored credentials, so no
 * Magento API credentials are ever included here.
 */
namespace Kangaroorewards\Core\Model\ProductState;

use Kangaroorewards\Core\Helper\KangarooRewardsRequest;
use Psr\Log\LoggerInterface;

class ProductStateClient implements ProductStateClientInterface
{
    const PATH = 'magento/product-state';

    /** Keep the request short so Magento operations are never held up by the API. */
    const TIMEOUT_SECONDS = 5;

    /**
     * @var KangarooRewardsRequest
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(KangarooRewardsRequest $request, LoggerInterface $logger)
    {
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        try {
            return (bool)$this->request->isConnected();
        } catch (\Exception $exception) {
            $this->logger->warning('[Kangaroo Rewards] Product state: cannot read credentials', [
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function send($domain, $storeId, $sku, $occurredAt = null)
    {
        $payload = [
            'domain' => (string)$domain,
            'storeId' => (string)$storeId,
            'sku' => (string)$sku,
        ];
        if ($occurredAt !== null && $occurredAt !== '') {
            $payload['occurredAt'] = (string)$occurredAt;
        }

        try {
            $response = $this->request->post(self::PATH, $payload, self::TIMEOUT_SECONDS);
        } catch (\Throwable $exception) {
            return $this->failure(0, null, $exception->getMessage(), true);
        }

        $last = $this->request->getLastResult();
        $httpCode = (int)$last['http_code'];
        $responseBody = is_string($response) ? $response : null;

        if ((int)$last['errno'] !== 0 || $response === false) {
            $error = $last['error'] !== '' ? $last['error'] : 'Request to the Kangaroo API failed';
            return $this->failure($httpCode, $responseBody, $error, true);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return $this->failure($httpCode, $responseBody, 'Kangaroo API responded with HTTP ' . $httpCode, $this->isRetryableStatus($httpCode));
        }

        return [
            'success' => true,
            'retryable' => false,
            'http_code' => $httpCode,
            'response' => $responseBody,
            'error' => '',
        ];
    }

    /**
     * @param int $httpCode
     * @return bool
     */
    private function isRetryableStatus($httpCode)
    {
        return $httpCode >= 500 || in_array($httpCode, [401, 408, 429], true);
    }

    /**
     * @param int $httpCode
     * @param string|null $response
     * @param string $error
     * @param bool $retryable
     * @return array
     */
    private function failure($httpCode, $response, $error, $retryable)
    {
        return [
            'success' => false,
            'retryable' => $retryable,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error,
        ];
    }
}
