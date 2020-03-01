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
     * @return array
     */
    public function translation();

    /**
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function transaction($limit, $page);

    /**
     * @return array
     */
    public function balance();

    /**
     * @param int $allow_email
     * @param int $allow_sms
     * @return array
     */
    public function saveSetting($allow_email, $allow_sms);

    /**
     * @param float $redeemAmount
     * @return array
     */
    public function redeem($redeemAmount);

    /**
     * @return array
     */
    public function welcomeMessage();
    
    /**
     * @param int $punchItemId
     * @return array
     */
    public function redeemCatalog($punchItemId);

    /**
     * @param string $sku
     * @return array
     */
    public function getProductOffer($sku);

    /**
     * @return array
     */
    public function getShoppingCartItemPrice();

    /**
     * @return array
     */
    public function getShoppingCartSubtotal();
    
}