<?php

namespace Kangaroorewards\Core\Api\Data;

interface CollectedEventAttributesInterface
{
    /**
     * @param int|null $quantity
     * @return $this
     */
    public function setQuantity($quantity);

    /**
     * @return int|null
     */
    public function getQuantity();

    /**
     * @param string|null $productId
     * @return $this
     */
    public function setProductId($productId);

    /**
     * @return string|null
     */
    public function getProductId();

    /**
     * @param string|null $variantId
     * @return $this
     */
    public function setVariantId($variantId);

    /**
     * @return string|null
     */
    public function getVariantId();

    /**
     * @param string|null $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * @return string|null
     */
    public function getSku();

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string|null $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * @return string|null
     */
    public function getCurrency();

    /**
     * @param float|null $price
     * @return $this
     */
    public function setPrice($price);

    /**
     * @return float|null
     */
    public function getPrice();

    /**
     * @param string[]|null $categories
     * @return $this
     */
    public function setCategories($categories);

    /**
     * @return string[]|null
     */
    public function getCategories();
}
