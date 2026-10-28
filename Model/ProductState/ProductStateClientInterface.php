<?php
/**
 * Sends lightweight product-state events (SKU only) to the integration API.
 */
namespace Kangaroorewards\Core\Model\ProductState;

interface ProductStateClientInterface
{
    /**
     * Whether this installation is connected to Kangaroo (credentials stored).
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Send a product-state event. Never throws; failures are reported in the result.
     *
     * @param string $domain
     * @param string|int $storeId
     * @param string $sku
     * @param string|null $occurredAt ISO 8601 timestamp
     * @return array{success:bool,retryable:bool,http_code:int,response:?string,error:string}
     */
    public function send($domain, $storeId, $sku, $occurredAt = null);

}
