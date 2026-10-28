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
        $event = $observer->getEvent();
        $track = $event->getTrack();
        $shipment = $event->getShipment();
        $this->logger->warning('[Kangaroo Rewards] Shipment observer invoked', [
            'event_name' => $event->getName(),
            'event_data_keys' => array_keys($event->getData()),
            'has_track' => (bool) $track,
            'has_shipment' => (bool) $shipment,
        ]);

        if ($track) {
            if (!$this->trackingDataChanged($track)) {
                $this->logger->info('[Kangaroo Rewards] Shipment tracking event ignored: no tracking change');
                return;
            }
            $orderId = (int) $track->getOrderId();
        } elseif ($shipment) {
            if (!$this->isNewShipment($shipment)) {
                $this->logger->info('[Kangaroo Rewards] Shipment event ignored: shipment already exists', [
                    'shipment_id' => $shipment->getId(),
                ]);
                return;
            }
            $orderId = (int) $shipment->getOrderId();
        } else {
            return;
        }

        if ($orderId <= 0) {
            $this->logger->warning('[Kangaroo Rewards] Shipment update has no order ID');
            return;
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $storeId = (int) $order->getStoreId();
            $domain = $this->storeManager->getStore($storeId)->getBaseUrl();
            $this->logger->info('[Kangaroo Rewards] Shipment update detected', [
                'order_id' => $orderId,
                'source' => $track ? 'tracking' : 'shipment',
            ]);
            $this->endpoint->notifyShipmentUpdate($orderId, $domain, (string) $storeId);
        } catch (\Exception $exception) {
            $this->logger->error('[Kangaroo Rewards] Shipment tracking observer failed', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isNewShipment($shipment)
    {
        return $shipment->getOrigData('entity_id') === null;
    }

    private function trackingDataChanged($track)
    {
        if ($track->getOrigData('entity_id') === null) {
            return true;
        }

        foreach (['track_number', 'carrier_code', 'title'] as $field) {
            if ($track->getOrigData($field) !== $track->getData($field)) {
                return true;
            }
        }

        return false;
    }
}
