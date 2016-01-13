<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Entity\Repository;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Component\Testing\WebTestCase;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository;

/**
 * @dbIsolation
 */
class MenuItemRepositoryTest extends WebTestCase
{
    /**
     * @var MenuItemRepository
     */
    protected $repository;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData']);
        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroB2BMenuBundle:MenuItem');
    }

    /**
     * @dataProvider findMenuItemByTitleDataProvider
     * @param string $title
     * @param string|null $expectedData
     */
    public function testFindMenuItemByTitle($title, $expectedData)
    {
        if ($expectedData) {
            $expectedData = $this->getReference($expectedData);
        }
        $this->assertEquals($expectedData, $this->repository->findMenuItemByTitle($title));
    }

    /**
     * @return array
     */
    public function findMenuItemByTitleDataProvider()
    {
        return [
            [
                'title' => 'menu_item.4',
                'expectedData' => 'menu_item.4',
            ],
            [
                'title' => 'not exists',
                'expectedData' => null,
            ]
        ];
    }

    /**
     * @dataProvider findMenuItemWithChildrenAndTitleByTitleDataProvider
     * @param $title
     * @param $expectedData
     */
    public function testFindMenuItemWithChildrenAndTitleByTitle($title, $expectedData)
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BMenuBundle:MenuItem');
        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        /** @var MenuItem $result */
        $result = $this->repository->findMenuItemWithChildrenAndTitleByTitle($title);

        $this->assertTreeEquals($expectedData, $result);

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @return array
     */
    public function findMenuItemWithChildrenAndTitleByTitleDataProvider()
    {
        return [
            [
                'title' => 'menu_item.4',
                'expectedData' => [
                    'menu_item.4' => [
                        'menu_item.4_5' => [
                            'menu_item.4_5_6' => [
                                'menu_item.4_5_6_8' => []
                            ],
                            'menu_item.4_5_7' => [],
                        ]
                    ],
                ]
            ],
            [
                'title' => 'not exists',
                'expectedData' => null,
            ]
        ];
    }

    /**
     * @param $expectedData
     * @param MenuItem|null $root
     */
    protected function assertTreeEquals($expectedData, MenuItem $root = null)
    {
        if (!$expectedData) {
            $this->assertEquals($expectedData, $root);
        } else {
            $this->assertEquals($expectedData, $this->prepareActualData($root));
        }
    }

    /**
     * @param MenuItem $root
     * @return array
     */
    protected function prepareActualData(MenuItem $root)
    {
        $tree = [];
        foreach ($root->getChildren() as $child) {
            $tree = array_merge($tree, $this->prepareActualData($child));
        }
        return [$root->getDefaultTitle()->getString() => $tree];
    }
}
