<?php

namespace Kangaroorewards\Core\Model;

class Order
{
    private $_order;

    public function __construct($order)
    {
        $this->_order = $order;
    }


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
            if ($orderProduct->getProductType() == 'simple') {
                $data['order']['orderItems'][] = array('code' => $orderProduct->getSku(),
                    'productId' => $orderProduct->getProductId(),
                    'title' => $orderProduct->getName(),
                    'price' => isset($parent) ? $parent->getPrice() : $orderProduct->getPrice(),
                    'qtyOrdered' => $orderProduct->getQtyOrdered()
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
