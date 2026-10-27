<?php

namespace Kangaroorewards\Core\Api\Data;

interface CollectedEventContextInterface
{
    /**
     * @param string|null $pageUrl
     * @return $this
     */
    public function setPageUrl($pageUrl);

    /**
     * @return string|null
     */
    public function getPageUrl();
}
