<?php
/**
 * Customized api interface to get store address
 */
namespace Kangaroorewards\Core\Api;

/**
 * Interface StoreManagementInterface
 *
 * @package Kangaroorewards\Core\Api
 */
interface StoreManagementInterface
{
    /**
     * Return all store address Info.
     *
     * @api
     * @return string store address info
     */
    public function getStoreAddressInfo();
}
