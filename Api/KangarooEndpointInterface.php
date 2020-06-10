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
     * @return string
     */
    public function translation();

    /**
     * @param int $limit
     * @param int $page
     * @return string
     */
    public function transaction($limit, $page);

    /**
     * @return string
     */
    public function balance();

    /**
     * @param int $allow_email
     * @param int $allow_sms
     * @return string
     */
    public function saveSetting($allow_email, $allow_sms);

    /**
     * @param float $redeemAmount
     * @return string
     */
    public function redeem($redeemAmount);

    /**
     * @return string
     */
    public function welcomeMessage();

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
    
}