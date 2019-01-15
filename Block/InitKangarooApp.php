<?php
namespace Kangaroorewards\Core\Block;
use Kangaroorewards\Core\Helper\KangarooRewardsRequest;

class InitKangarooApp extends \Magento\Framework\View\Element\Template
{
    protected $customerSession;
    protected $storeManage;
    protected $httpContext;
    protected $registry;
    protected $cart;
    protected $searchCriteriaBuilder;
    protected $linkManagement;
    protected $productRepository;
    protected $_logger;

    public function __construct(\Magento\Customer\Model\Session $customerSession,
                                \Magento\Store\Model\StoreManagerInterface $storeManage,
                                \Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Framework\Registry $registry,
                                \Magento\Checkout\Model\Cart $cart,
                                \Magento\Framework\App\Http\Context $httpContext,
                                \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
                                \Magento\ConfigurableProduct\Api\LinkManagementInterface $linkManagement,
                                \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
                                \Psr\Log\LoggerInterface $logger,
                                array $data = []


    )
    {
        $this->customerSession = $customerSession;
        $this->storeManage = $storeManage;
        $this->httpContext = $httpContext;
        $this->registry = $registry;
        $this->cart = $cart;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->linkManagement = $linkManagement;
        $this->productRepository = $productRepository;
        $this->_logger = $logger;
        parent::__construct($context, $data);

    }

    public function isCustomerLoggedIn()
    {
        $isLogin = $this->customerSession->isLoggedIn();
        if ($isLogin) {
            return true;
        } else {
            return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        }

    }

    public function getCustomerEmail()
    {
        if ($this->isCustomerLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }

    }

    public function getBaseStoreUrl()
    {
        return $this->storeManage->getStore()->getBaseUrl();
    }

    public function getStoreId()
    {
        return $this->storeManage->getStore()->getId();
    }

    public function getCurrentProduct()
    {
        $product = $this->registry->registry('current_product');

        if ($product->getTypeId() == 'simple') {
            $productOne = array("code" => $product->getSku(),
                "productId" => $product->getId(),
                "price" => $product->getPrice(),
                "title" => $product->getName()
            );
            $productL[] = $productOne;

            return array("code" => $product->getSku(),
                "product" => $productL);
        } else {
            return array("code" => $product->getSku(),
                "product" => $this->getChildren());
        }

    }

    public function getChildren()
    {
        $parentId = $this->registry->registry('current_product')->getId();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('type_id', 'configurable')
            ->addFilter('entity_id', $parentId)
            ->create();

        $configurableProducts = $this->productRepository
            ->getList($searchCriteria);
        $childProducts = [];
        foreach ($configurableProducts->getItems() as $configurableProduct) {
            $children = $this->linkManagement
                ->getChildren($configurableProduct->getSku());
            foreach ($children as $child) {
                $childProducts[] = array(
                    "code" => $child->getSku(),
                    "productId" => $child->getId(),
                    "price" => $child->getPrice(),
                    "title" => $child->getName()
                );
            }
        }
        return $childProducts;
    }

    public function isProductPage()
    {
        $product = $this->registry->registry('current_product');
        if ($product) {
            return true;
        }
        return false;
    }

    public function getCart()
    {
        return $this->cart->getQuote();
    }

    public function getKangarooAPIUrl()
    {
        return KangarooRewardsRequest::getKangarooAPIUrl();
    }
}
