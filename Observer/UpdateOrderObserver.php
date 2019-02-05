<?php
/**
 * Order update observer
 */
namespace Kangaroorewards\Core\Observer;
use Kangaroorewards\Core\Model\Order;
use Kangaroorewards\Core\Helper\KangarooRewardsRequest;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Api\OauthServiceInterface;
use Kangaroorewards\Core\Model\KangarooCredentialFactory;

/**
 * Class UpdateOrderObserver
 *
 * @package Kangaroorewards\Core\Observer
 */

class UpdateOrderObserver implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    /**
     * @var IntegrationFactory
     */
    protected $integrationFactory;

    /**
     * @var IntegrationOauthService
     */
    protected $oauthService;

    /**
     * @var KangarooCredentialFactory 
     */
    protected $credentialFactory;
    /**
     * UpdateOrderObserver constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param IntegrationFactory       $integrationFactory
     * @param OauthServiceInterface    $oauthService
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        IntegrationFactory $integrationFactory,
        OauthServiceInterface $oauthService,
        KangarooCredentialFactory $credentialFactory
    ) {
        $this->_logger = $logger;
        $this->integrationFactory = $integrationFactory;
        $this->oauthService = $oauthService;
        $this->credentialFactory = $credentialFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = new Order($observer->getEvent()->getOrder());
        if ($order) {
            $data = array(
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            );

            $data = array_merge($data, $order->getOrderData());
//            $integration = $this->integrationFactory
//                ->create()
//                ->load('Kangaroorewards', 'name');
//            $consumer = $this->oauthService
//                ->loadConsumer($integration->getConsumerId());
//            $key = $consumer->getSecret();
            $request = new KangarooRewardsRequest($this->credentialFactory, $this->_logger);
            $sendData = json_encode($data);
            $request->post('magento/order', array("data" => $sendData));
            $this->_logger->info("[Kangaroo Rewards]" . $sendData);
        }
    }
}
