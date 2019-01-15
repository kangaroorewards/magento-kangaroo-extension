<?php

namespace Kangaroorewards\Core\Observer;
use Kangaroorewards\Core\Model\Order;
use Kangaroorewards\Core\Helper\KangarooRewardsRequest;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Api\OauthServiceInterface;


class UpdateOrderObserver implements ObserverInterface
{
    private $_logger;
    /**
     * @var IntegrationFactory
     */
    protected $_integrationFactory;

    /**
     * @var IntegrationOauthService
     */
    protected $_oauthService;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        IntegrationFactory $integrationFactory,
        OauthServiceInterface $oauthService
    )
    {
        $this->_logger = $logger;
        $this->_integrationFactory = $integrationFactory;
        $this->_oauthService = $oauthService;
    }

    public function execute(Observer $observer)
    {
        $order = new Order($observer->getEvent()->getOrder());
        if (!$order) return;

        $data = array(
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        );

        $data = array_merge($data, $order->getOrderData());
        $integration = $this->_integrationFactory->create()->load('Kangaroorewards', 'name');
        $consumer = $this->_oauthService->loadConsumer($integration->getConsumerId());
        $key = $consumer->getSecret();
        $request = new KangarooRewardsRequest($key);
        $sendData = json_encode($data);
        $request->post('magento/order', array("data" => $sendData));
        $this->_logger->info("[Kangaroo Rewards]" . $sendData);

    }
}
