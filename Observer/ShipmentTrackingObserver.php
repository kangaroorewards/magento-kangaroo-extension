<?php
/**
 * Notify the integration API when shipment tracking data changes.
 */
namespace Kangaroorewards\Core\Observer;

use Kangaroorewards\Core\Api\KangarooEndpointInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ShipmentTrackingObserver implements ObserverInterface
{
    private $endpoint;
    private $orderRepository;
    private $storeManager;
    private $logger;

    public function __construct(
        KangarooEndpointInterface $endpoint,
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->endpoint = $endpoint;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $track = $observer->getEvent()->getTrack();
        if (!$track || !$this->trackingDataChanged($track)) {
            return;
        }

        $orderId = (int) $track->getOrderId();
        if ($orderId <= 0) {
            $this->logger->warning('[Kangaroo Rewards] Shipment tracking update has no order ID');
            return;
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $storeId = (int) $order->getStoreId();
            $domain = $this->storeManager->getStore($storeId)->getBaseUrl();
            $this->endpoint->notifyShipmentUpdate($orderId, $domain, (string) $storeId);
        } catch (\Exception $exception) {
            $this->logger->error('[Kangaroo Rewards] Shipment tracking observer failed', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function trackingDataChanged($track)
    {
        foreach (['track_number', 'carrier_code', 'title'] as $field) {
            if ($track->getOrigData($field) !== $track->getData($field)) {
                return true;
            }
        }

        return false;
    }
}
