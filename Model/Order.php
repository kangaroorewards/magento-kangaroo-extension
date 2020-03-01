<?php
/**
 * Retrieve order data for calculation points
 */
namespace Kangaroorewards\Core\Model;

/**
 * Class Order
 *
 * @package Kangaroorewards\Core\Model
 */
class Order
{
    private $_order;

    private $_productFactory;
    /**
     * Order constructor.
     *
     * @param $order
     */
    public function __construct($order, $productFactory)
    {
        $this->_order = $order;
        $this->_productFactory = $productFactory;
    }


    /**
     * Prepare order data array for kangaroo request
     *
     * @return array
     */
    public function getOrderData()
    {
        $data = array();

        // store Info
        $data['storeId'] = $this->_order->getStoreId();
        $data['domain'] = $this->_order->getStore()->getBaseUrl();

        //customer Info
        $data['customer']['id'] = $this->_order->getCustomerId();
        $data['customer']['isGuest'] = $this->_order->getCustomerIsGuest();
        $data['customer']['email'] = $this->_order->getCustomerEmail();
        $data['customer']['firstname'] = $this->_order->getCustomerFirstname();
        $data['customer']['lastname'] = $this->_order->getCustomerLastname();
        $data['customer']['group_id'] = $this->_order->getCustomerGroupId();
        $data['customer']['birthDate'] = $this->_order->getCustomerDob();

        //order info
        $data['order']['id'] = $this->_order->getId();
        $data['order']['state'] = $this->_order->getState();
        $orderItems = $this->_order->getAllItems();

        foreach ($orderItems as $orderProduct) {
            $parent = $orderProduct->getParentItem();
            if ($orderProduct->getProductType() != 'configurable' &&
                $orderProduct->getProductType() != 'bundle'
            ) {
                $price = $orderProduct->getPrice();
                if(isset($parent) && $parent->getProductType() == 'configurable')
                {
                    $price = $parent->getPrice();
                }
                $product = $this->_productFactory->create()->load($orderProduct->getProductId());
                $data['order']['orderItems'][] = array(
                    'code' => $orderProduct->getSku(),
                    'parentId' => isset($parent) ?
                        $parent->getProductId():
                        $orderProduct->getProductId(),
                    'productId' => $orderProduct->getProductId(),
                    'title' => $orderProduct->getName(),
                    'price'=>$price,
                    'qtyOrdered' => $orderProduct->getQtyOrdered(),
                    'qtyRefunded' => $orderProduct->getQtyRefunded(),
                    'qtyCanceled' => $orderProduct->getQtyCanceled(),
                    'categories' => $product->getCategoryIds()
                );
            }
        }

        $data['order']['subtotal'] = $this->_order->getSubtotal();
        $data['order']['taxAmount'] = $this->_order->getTaxAmount();
        $data['order']['total'] = $this->_order->getGrandTotal();
        $data['order']['shippingAmount'] = $this->_order->getShippingAmount();
        $data['order']['discountCode'] = $this->_order->getCouponCode();
        $data['order']['discountAmount'] = $this->_order->getDiscountAmount();
        $data['order']['createTime'] = $this->_order->getCreatedAt();
        $data['order']['subtotalRefunded'] = $this->_order->getSubtotalRefunded();
        $data['order']['discountRefunded'] = $this->_order->getDiscountRefunded();
        $data['order']['totalRefunded'] = $this->_order->getTotalRefunded();
        $data['order']['totalDue'] = $this->_order->getTotalDue();
        $data['order']['totalPaid'] = $this->_order->getTotalPaid();

        return $data;
    }
}