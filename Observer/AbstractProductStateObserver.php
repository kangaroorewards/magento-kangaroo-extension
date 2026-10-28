<?php
/**
 * Base for the observers that report changed product/variant SKUs to the integration API.
 *
 * Observers only register the SKU with the dispatcher; the request itself is sent when the Magento
 * request ends. Errors are swallowed so a Kangaroo problem can never break a product or stock save.
 */
namespace Kangaroorewards\Core\Observer;

use Kangaroorewards\Core\Model\ProductState\Dispatcher;
use Kangaroorewards\Core\Model\ProductState\StoreResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractProductStateObserver implements ObserverInterface
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var StoreResolver
     */
    protected $storeResolver;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Dispatcher $dispatcher, StoreResolver $storeResolver, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->storeResolver = $storeResolver;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            if (!$this->dispatcher->isEnabled()) {
                return;
            }
            $this->collect($observer);
        } catch (\Throwable $exception) {
            $this->logger->error('[Kangaroo Rewards] Product state observer failed', [
                'observer' => static::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param Observer $observer
     * @return void
     */
    abstract protected function collect(Observer $observer);

    /**
     * @param int|string|null $storeId Null uses the current store
     * @param int[] $websiteIds
     * @param string $sku
     * @param array $state
     * @return void
     */
    protected function track($storeId, array $websiteIds, $sku, array $state = [])
    {
        foreach ($this->storeResolver->resolve($storeId, $websiteIds) as $store) {
            $this->dispatcher->add($store['store_id'], $store['domain'], $sku, $state, static::class);
        }
    }
}
