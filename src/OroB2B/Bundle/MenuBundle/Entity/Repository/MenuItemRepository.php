<?php

namespace OroB2B\Bundle\MenuBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Component\Tree\Entity\Repository\NestedTreeRepository;

class MenuItemRepository extends NestedTreeRepository
{
    /**
     * @param string $title
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findMenuItemByTitle($title)
    {
        $subQuery = $this->getMenuItemIdByTitleQueryBuilder($title);

        $queryBuilder = $this->createQueryBuilder('node');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('node.id', $subQuery->getDQL()))
            ->orderBy('node.level', Criteria::ASC)
            ->addOrderBy('node.left', Criteria::ASC)
            ->setParameter('title', $title);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $title
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getMenuItemIdByTitleQueryBuilder($title)
    {
        $subQueryBuilder = $this->createQueryBuilder('menu');
        return $subQueryBuilder->select('menu.id')
            ->join('menu.titles', 't')
            ->where($subQueryBuilder->expr()->eq('t.string', ':title'))
            ->andWhere($subQueryBuilder->expr()->isNull('t.locale'))
            ->setParameter('title', $title);
    }

    /**
     * @param string $title
     * @return MenuItem
     */
    public function findMenuItemWithChildrenAndTitleByTitle($title)
    {
        $subQuery = $this->getMenuItemIdByTitleQueryBuilder($title);

        $queryBuilder = $this->createQueryBuilder('node');
        $queryBuilder
            ->addSelect('title')
            ->addSelect('children')
            ->leftJoin('node.titles', 'title')
            ->leftJoin('node.children', 'children')
            ->andWhere($queryBuilder->expr()->in('node.root', $subQuery->getDQL()))
            ->orderBy('node.level', Criteria::ASC)
            ->addOrderBy('node.left', Criteria::ASC)
            ->setParameter('title', $title);

        /** @var MenuItem[] $result */
        $result = $queryBuilder->getQuery()->execute();

        return array_shift($result);
    }
}
