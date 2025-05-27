<?php

namespace Kangaroorewards\Core\Model\Cart;

use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;

class RestoreCartById
{
    protected $quoteFactory;
    protected $quoteIdMaskFactory;
    protected $quoteRepository;
    protected $quoteManagement;
    protected $checkoutSession;
    protected $customerSession;

    public function __construct(
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteRepository $quoteRepository,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * Restore the cart by quote ID (masked or unmasked).
     *
     * @param string|int $cartId
     * @param bool $isMasked
     * @param int|null $customerId
     * @return bool
     */
    public function restore($cartId, $isMasked = false, $customerId = null)
    {
        try {
            if ($isMasked) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                $quoteId = $quoteIdMask->getQuoteId();
            } else {
                $quoteId = $cartId;
            }

            $quote = $this->quoteRepository->get($quoteId);

            // Set quote to current session
            $quote->setIsActive(true);
            $quote->setStoreId($quote->getStoreId());

            if ($customerId) {
                $quote->setCustomerId($customerId);
            } else {
                $quote->setCustomerId(null)->setCustomerIsGuest(true);
            }

            $this->quoteRepository->save($quote);
            $this->checkoutSession->replaceQuote($quote);

            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}