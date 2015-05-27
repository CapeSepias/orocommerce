<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency;

class LoadPriceListDemoData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BPricingBundle/Migrations/Data/Demo/ORM/data/price_lists.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $priceList = new PriceList();
            $priceList->setName($row['name']);
            $priceList->setDefault((bool)$row['default']);

            $currencies = explode(',', $row['currencies']);
            foreach ($currencies as $currency) {
                $priceListCurrency = new PriceListCurrency();
                $priceListCurrency->setPriceList($priceList);
                $priceListCurrency->setCurrency(trim($currency));
                $priceList->addCurrency($priceListCurrency);
            }

            $manager->persist($priceList);
        }

        fclose($handler);

        $manager->flush();
    }
}
