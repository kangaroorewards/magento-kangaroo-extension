<?php
/**
 * Looks up SKU and type of products by ID.
 */
namespace Kangaroorewards\Core\Model\ProductState;

interface ProductLookupInterface
{
    /**
     * @param int[] $productIds
     * @return array[] List of ['sku' => string, 'type_id' => string]
     */
    public function findByIds(array $productIds);
}
