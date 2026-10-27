<?php

namespace Kangaroorewards\Core\Model\CollectedEvent;

use Kangaroorewards\Core\Api\Data\CollectedEventContextInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class Context extends AbstractSimpleObject implements CollectedEventContextInterface
{
    public function setPageUrl($pageUrl)
    {
        return $this->_set('page_url', $pageUrl);
    }

    public function getPageUrl()
    {
        return $this->_get('page_url');
    }
}
