<?php

namespace Kangaroorewards\Core\Api;

interface StoreManagementInterface
{
    /**
     * Return all store address Info.
     *
     * @api
     * @return json string store address info
     */
    public function getStoreAddressInfo();
}
