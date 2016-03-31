<?php

namespace OroB2B\Bundle\PaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class OroB2BPaymentBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroB2BPaymentExtension();
    }

    public function boot()
    {
        try {
            SecureArrayType::getType(SecureArrayType::TYPE);
        } catch (\Doctrine\DBAL\DBALException $e) {
            SecureArrayType::addType(
                SecureArrayType::TYPE,
                'OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType'
            );

            $mcrypt = $this->container->get('oro_security.encoder.mcrypt');

            /** @var SecureArrayType $secureArrayType */
            $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
            $secureArrayType->setMcrypt($mcrypt);
        }
    }
}
