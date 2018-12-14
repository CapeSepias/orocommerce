<?php

namespace Oro\Bundle\ConsentBundle\Layout\DataProvider;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Layout data provider that helps to get DTO object ConsentData for certain website from config
 */
class FrontendConsentProvider implements FeatureToggleableInterface
{
    const CUSTOMER_CONSENTS_STEP = 'customer_consents';

    use FeatureCheckerHolderTrait;

    /**
     * @var ConsentDataProvider
     */
    private $provider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param ConsentDataProvider $provider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ConsentDataProvider $provider, TokenStorageInterface $tokenStorage)
    {
        $this->provider = $provider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return ConsentData[]
     */
    public function getAllConsentData()
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getAllConsentData();
    }

    /**
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return ConsentData[]
     */
    public function getNotAcceptedRequiredConsentData(array $consentAcceptances = null)
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        $requiredConsentData = $this->provider->getNotAcceptedRequiredConsentData();
        $filteredConsentData = [];
        foreach ($requiredConsentData as $consentData) {
            $key = sprintf('%s_%s', $consentData->getId(), $consentData->getCmsPageData()->getId());
            $filteredConsentData[$key] = $consentData;
        }


        $consentAcceptances = (array) $consentAcceptances;
        foreach ($consentAcceptances as $acceptance) {
            $key = sprintf('%s_%s', $acceptance->getConsent()->getId(), $acceptance->getLandingPage()->getId());
            if (array_key_exists($key, $filteredConsentData)) {
                unset($filteredConsentData[$key]);
            }
        }

        return $filteredConsentData;
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return bool
     */
    public function isCustomerUserCurrentlyLoggedIn(CustomerUser $customerUser)
    {
        return $customerUser === $this->getCustomerUser();
    }

    /**
     * @param array $excludedSteps
     *
     * @return array
     */
    public function getExcludedSteps(array $excludedSteps = [])
    {
        if (!$this->isFeaturesEnabled()) {
            $excludedSteps[] = self::CUSTOMER_CONSENTS_STEP;
        }

        return $excludedSteps;
    }

    /**
     * @param int $actualStep
     *
     * @return int
     */
    public function getStepOrder($actualStep)
    {
        if (!$this->isFeaturesEnabled()) {
            --$actualStep;
        }

        return $actualStep;
    }

    /**
     * @return CustomerUser|null
     */
    private function getCustomerUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof CustomerUser) {
            return $user;
        }

        return null;
    }
}
