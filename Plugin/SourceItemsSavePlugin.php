<?php
/**
 * Reports SKUs whose per-source inventory changed (Magento Multi-Source Inventory).
 *
 * MSI writes the legacy stock rows without dispatching the stock item events, so the source item
 * save API is observed instead. The plugin is inert when MSI is not installed.
 */
namespace Kangaroorewards\Core\Plugin;

use Kangaroorewards\Core\Model\ProductState\Dispatcher;
use Kangaroorewards\Core\Model\ProductState\StoreResolver;
use Psr\Log\LoggerInterface;

class SourceItemsSavePlugin
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var StoreResolver
     */
    private $storeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Dispatcher $dispatcher, StoreResolver $storeResolver, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->storeResolver = $storeResolver;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\InventoryApi\Api\SourceItemsSaveInterface $subject
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems
     * @return null Arguments are left untouched
     */
    public function beforeExecute($subject, array $sourceItems)
    {
        try {
            if (!$this->dispatcher->isEnabled()) {
                return null;
            }

            $stores = $this->storeResolver->resolve();
            foreach ($sourceItems as $sourceItem) {
                foreach ($stores as $store) {
                    $this->dispatcher->add($store['store_id'], $store['domain'], $sourceItem->getSku(), [
                        'qty' => $sourceItem->getQuantity(),
                        'is_in_stock' => $sourceItem->getStatus(),
                    ], static::class);
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->error('[Kangaroo Rewards] Product state source item plugin failed', [
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }
}
