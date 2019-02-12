<?php
/**
 * Magento side endpoint for interact with kangaroo api
 */

namespace Kangaroorewards\Core\Model;

use Kangaroorewards\Core\Api\KangarooEndpointInterface;
use Kangaroorewards\Core\Block\InitKangarooApp;
use Kangaroorewards\Core\Helper\KangarooRewardsRequest;
use Kangaroorewards\Core\Model\KangarooCredentialFactory;

/**
 * Class KangarooEndpoint
 * @package Kangaroorewards\Core\Model
 */
class KangarooEndpoint implements KangarooEndpointInterface
{
    /**
     * @var InitKangarooApp 
     */
    protected $kangarooData;
    
    /**
     * @var KangarooRewardsRequest 
     */
    protected $request;
    
    /**
     * @var 
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\Session 
     */
    protected $customerSession;

    /**
     * @var \Psr\Log\LoggerInterface 
     */
    protected $logger;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface 
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\App\Http\Context 
     */
    protected $httpContext;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface 
     */
    protected $productRepository;

    /**
     * KangarooEndpoint constructor.
     * @param InitKangarooApp $kangarooData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Kangaroorewards\Core\Model\KangarooCredentialFactory $credentialFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        InitKangarooApp $kangarooData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\App\Http\Context $httpContext,
        KangarooCredentialFactory $credentialFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->kangarooData = $kangarooData;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->httpContext = $httpContext;
        $this->request = new KangarooRewardsRequest($credentialFactory, $logger);
        $this->productRepository = $productRepository;
    }

    /**
     * If Customer is login
     * @return bool
     */
    private function isCustomerLoggedIn()
    {
        $islogin = (int)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($islogin || $this->customerSession->isLoggedIn()) {
            return true;
        }
        return false;
    }

    /**
     * Get logged in customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    protected function _getCustomer()
    {
        $this->logger->info('KangarooEndpoint_getCustomer()ID: '.$this->customerSession->getCustomerId());
        if (empty($this->customer)) {
            $this->customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        }
        return $this->customer;
    }
    /**
     * @return string
     */
    public function translation()
    {
        $data = [
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl()
        ];
        $response = $this->request->get('magento/translation', $data);
        if($response->isSuccess()){
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @param int $limit
     * @param int $page
     * @return string
     */
    public function transaction($limit, $page)
    {
        $data = [
            'limit' => $limit,
            'page' => $page,
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }

        $response = $this->request->get('magento/transaction', $data);
        if ($response->isSuccess()) {
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @return string
     */
    public function balance()
    {
        $data = [
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }

        $response = $this->request->get('magento/balance', $data);
        if($response->isSuccess()){
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @param int $allow_email
     * @param int $allow_sms
     * @return string
     */
    public function saveSetting($allow_email, $allow_sms)
    {
        $data = [
            'allow_email' => $allow_email,
            'allow_sms' => $allow_sms,
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }

        $response = $this->request->get('magento/saveSetting', $data);
        if ($response->isSuccess()) {
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @param float $redeemAmount
     * @param float $subtotalAmount
     * @return string
     */
    public function redeem($redeemAmount, $subtotalAmount)
    {
        $data = [
            'redeemAmount' => $redeemAmount,
            'subtotalAmount' => $subtotalAmount,
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }

        $response = $this->request->get('magento/redeem', $data);
        if ($response->isSuccess()) {
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @return string
     */
    public function welcomeMessage2()
    {
        $data = [
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        $response = $this->request->get('magento/welcomeMessage', $data);
        if($response->isSuccess()){
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @param int $punchItemId
     * @return string
     */
    public function redeemCatalog($punchItemId)
    {
        $data = [
            'punchItemId' => $punchItemId,
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }

        $response = $this->request->get('magento/redeemCatalog', $data);
        if ($response->isSuccess()) {
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @param string $sku
     * @return string
     */
    public function getProductOffer($sku)
    {
        $data = [
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }

        $product = $this->getProductBySKU($sku);
        if($product) {
            $data['productId'] = $product->code;
            $productDetail = [];
            foreach ($product->product as $item) {
                $productDetail[] = [
                    'code' => $item["code"],
                    'productId' => $item["productId"],
                    'price' => $item["price"],
                    'title' => $item["title"]
                ];
            }

            $data['product'] = ['id' => $product->code,
                'product' => $productDetail];
        }
        $response = $this->request->get('magento/getProductOffer', $data);
        if ($response->isSuccess()) {
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @return string
     */
    public function getShoppingCartItemPrice()
    {
        $data = [
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        if ($this->isCustomerLoggedIn()) {
            $customer = $this->_getCustomer();
            $data['customerEmail'] = $customer->getEmail();
            $data['customerId'] = $customer->getId();
        }
        
        if($this->kangarooData->isShoppingCartExist()) {
            $cart = $this->kangarooData->getCart();
            if ($cart) {
                $data['discount'] = $cart->discount;
                $data['subtotal'] = $cart->subtotal;

                $productList = [];
                foreach ($cart->cartItems as $item) {
                    $productList[] = [
                        'code' => $item["code"],
                        'variant_id' => $item["productId"],
                        'price' => $item["price"],
                        'quantity' => $item["quantity"]
                    ];
                }
                $data['productList'] = $productList;
            }
        }
        $response = $this->request->get('magento/getShoppingCartItemPrice', $data);
        if ($response->isSuccess()) {
            return $response->getBody();
        }
        return json_encode(["active" => false]);
    }

    /**
     * @param $sku
     * @return object
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProductBySKU($sku){
        $product = $this->productRepository->get($sku);
        if ($product->getTypeId() == 'simple') {
            $productOne = array("code" => $product->getSku(),
                "productId" => $product->getId(),
                "price" => $product->getPrice(),
                "title" => $product->getName()
            );
            $productL[] = $productOne;

            return (object)["code" => $product->getSku(),
                "product" => $productL];
        } else {
            $parentId = $product->getId();
            return (object)["code" => $product->getSku(),
                "product" => $this->kangarooData->getChildren($parentId)];
        }
    }
    
    public function welcomeMessage()
    {
        $data = [
            'storeId' => $this->kangarooData->getStoreId(),
            'domain' => $this->kangarooData->getBaseStoreUrl(),
        ];

        $response = $this->test('magento/welcomeMessage',$data);//$this->request->get('magento/welcomeMessage', $data);
        if($response!=''){
            return $response;
        }
        return json_encode(["active" => false]);
    }
    
    private function test($url,$data)
    {
        //standard form data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://integ-api-dev.traktrok.com'.$url); //absolute url
        curl_setopt($ch, CURLOPT_HEADER, true); // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_POST, 1); // This is a POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Data to POST
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // Fetch and return content
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return '';
        }


        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);
        return $body;
    }
}