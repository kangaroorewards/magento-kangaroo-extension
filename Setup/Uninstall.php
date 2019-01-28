<?php
/**
 * Handle uninstall action, send uninstall request to kangaroo api
 */
namespace Kangaroorewards\Core\Setup;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Kangaroorewards\Core\Helper\KangarooRewardsRequest;;

class Uninstall implements UninstallInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        //Uninstall logic
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager
            ->get('\Magento\Store\Model\StoreManagerInterface');
        $log = $objectManager
            ->get('\Psr\Log\LoggerInterface');
        $log->info("[Kangaroo Rewards]-uninstall-step1");
        $integrationFactory = $objectManager
            ->get('\Magento\Integration\Model\IntegrationFactory');
        $oauthService = $objectManager
            ->get('\Magento\Integration\Api\OauthServiceInterface');
        $baseUrl = $storeManager->getStore()->getBaseUrl();

        $integration = $integrationFactory->create()->load('Kangaroorewards', 'name');
        $consumer = $oauthService->loadConsumer($integration->getConsumerId());
        $key = $consumer->getSecret();

        $request = new KangarooRewardsRequest($key);
        $data = array("domain" => $baseUrl);
        $sendData = json_encode($data);
        $request->post('magento/unInstall', array("data" => $sendData));
    }
}
