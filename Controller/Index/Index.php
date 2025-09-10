<?php

namespace Kangaroorewards\Core\Controller\Index;

use Kangaroorewards\Core\Model\Cart\RestoreCartById;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\RedirectFactory;

class Index extends Action
{
    protected $restoreCartById;
    protected $customerSession;
    protected $resultRedirectFactory;

    public function __construct(
        Context $context,
        RestoreCartById $restoreCartById,
        CustomerSession $customerSession,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->restoreCartById = $restoreCartById;
        $this->customerSession = $customerSession;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('krg_build_cart');
        $isMasked = !is_numeric($id);
        $customerId = $this->customerSession->isLoggedIn()
            ? $this->customerSession->getCustomerId()
            : null;

        $this->restoreCartById->restore($id, $isMasked, $customerId);

        // Redirect to normal cart page
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/cart');
        return $resultRedirect;
    }
}