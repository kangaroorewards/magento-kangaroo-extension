<?php
/**
 * Kangaroo endponint, make request to kangaroo api
 */

namespace Kangaroorewards\Core\Api;

/**
 * Interface KangarooEndpointInterface
 * @package Kangaroorewards\Core\Api
 */
interface KangarooEndpointInterface
{
    /**
     * @param string|null $url
     * @return string
     */
    public function translation($url = null);

    /**
     * @param int $limit
     * @param int $page
     * @param string|null $url
     * @return string
     */
    public function transaction($limit, $page, $url = null);

    /**
     * @param string|null $url
     * @return string
     */
    public function balance($url = null);

    /**
     * @param int $allow_email
     * @param int $allow_sms
     * @param string|null $birth_date
     * @param string|null $first
     * @param string|null $last
     * @param string|null $opt_out
     * @return string
     */
    public function saveSetting($allow_email, $allow_sms, $birth_date = null, $first = null, $last = null, $opt_out = null);

    /**
     * @param float $redeemAmount
     * @return string
     */
    public function redeem($redeemAmount);

    /**
     * @param string|null $url
     * @return string
     */
    public function welcomeMessage($url = null);

    /**
     * @param int $punchItemId
     * @return string
     */
    public function redeemCatalog($punchItemId);

    /**
     * @param string $sku
     * @return string
     */
    public function getProductOffer($sku);

    /**
     * @return string
     */
    public function getShoppingCartItemPrice();

    /**
     * @return string
     */
    public function getShoppingCartSubtotal();

    /**
     * @param string $qrcode
     * @return string
     */
    public function redeemOffer($qrcode);

    /**
     * @return string
     */
    public function version();

    /**
     * @param string $coupon
     * @return string
     */
    public function reclaim($coupon);

    /**
     * @param int $surveyId
     * @param \Kangaroorewards\Core\Model\SurveyAnswer[] $surveyAnswers
     * @return string
     */
    public function surveyAnswers($surveyId, $surveyAnswers);

    /**
     * @param int $actionId
     * @return string
     */
    public function callToActions($actionId);

    /**
     * @param int $spinWinId
     * @return string
     */
    public function spinDraw($spinWinId);

    /**
     * @return string
     */
    public function getCustomerInfo();


    /**
     * @return string
     */
    public function getCartInfo();


    /**
     * @param string $sku
     * @return string
     */
    public function getProductImages($sku);

    /**
     * Forward a storefront event to the integration API.
     *
     * @param string $event_type
     * @param \Kangaroorewards\Core\Api\Data\CollectedEventResourceInterface $resource
     * @param string|null $occurred_at
     * @param \Kangaroorewards\Core\Api\Data\CollectedEventContextInterface|null $context
     * @return string
     */
    public function storeCollectedEvent(
        $event_type,
        \Kangaroorewards\Core\Api\Data\CollectedEventResourceInterface $resource,
        $occurred_at = null,
        $context = null
    );

    /**
     * Notify the integration API that Magento shipment tracking changed.
     *
     * @param int $orderId
     * @param string $domain
     * @param string $storeId
     * @return string
     */
    public function notifyShipmentUpdate($orderId, $domain, $storeId);

}