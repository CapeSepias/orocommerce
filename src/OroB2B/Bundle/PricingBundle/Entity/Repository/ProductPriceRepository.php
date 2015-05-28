<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @param ProductUnit $unit
     */
    public function deleteByProductUnit(Product $product, ProductUnit $unit)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb->delete()
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('productPrice.unit', ':unit'),
                    $qb->expr()->eq('productPrice.product', ':product')
                )
            )
            ->setParameter('unit', $unit)
            ->setParameter('product', $product);

        $qb->getQuery()->execute();
    }
}
