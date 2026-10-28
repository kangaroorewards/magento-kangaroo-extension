<?php
/**
 * Decides which product types report their own price/stock.
 */
namespace Kangaroorewards\Core\Model\ProductState;

class ProductTypePolicy
{
    /**
     * Parent products take their price and stock from their children, so the child (simple) SKU is
     * the one that must be reported.
     */
    const PARENT_TYPES = ['configurable', 'grouped'];

    /**
     * @param string|null $typeId
     * @return bool True when the SKU of this product type carries its own price/stock
     */
    public static function isTracked($typeId)
    {
        return !in_array((string)$typeId, self::PARENT_TYPES, true);
    }
}
