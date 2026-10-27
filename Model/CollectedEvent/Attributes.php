<?php

namespace Kangaroorewards\Core\Model\CollectedEvent;

use Kangaroorewards\Core\Api\Data\CollectedEventAttributesInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class Attributes extends AbstractSimpleObject implements CollectedEventAttributesInterface
{
    public function setQuantity($quantity)
    {
        return $this->_set('quantity', $quantity);
    }

    public function getQuantity()
    {
        return $this->_get('quantity');
    }

    public function setProductId($productId)
    {
        return $this->_set('product_id', $productId);
    }

    public function getProductId()
    {
        return $this->_get('product_id');
    }

    public function setVariantId($variantId)
    {
        return $this->_set('variant_id', $variantId);
    }

    public function getVariantId()
    {
        return $this->_get('variant_id');
    }

    public function setSku($sku)
    {
        return $this->_set('sku', $sku);
    }

    public function getSku()
    {
        return $this->_get('sku');
    }

    public function setName($name)
    {
        return $this->_set('name', $name);
    }

    public function getName()
    {
        return $this->_get('name');
    }

    public function setCurrency($currency)
    {
        return $this->_set('currency', $currency);
    }

    public function getCurrency()
    {
        return $this->_get('currency');
    }

    public function setPrice($price)
    {
        return $this->_set('price', $price);
    }

    public function getPrice()
    {
        return $this->_get('price');
    }

    public function setCategories($categories)
    {
        return $this->_set('categories', $categories);
    }

    public function getCategories()
    {
        return $this->_get('categories');
    }
}
