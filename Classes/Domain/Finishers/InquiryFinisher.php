<?php


namespace WapplerSystems\Inquiry\Domain\Finishers;


use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class InquiryFinisher extends AbstractFinisher
{

    protected function executeInternal()
    {
        if ($this->options['emptyList'] === true) {

            /** @var FrontendUserAuthentication $frontendUserAuthentication */
            $frontendUserAuthentication = $this->finisherContext->getRequest()->getAttribute('frontend.user');
            $userSession = $frontendUserAuthentication->getSession();
            $userSession->set('items', []);
            $frontendUserAuthentication->storeSessionData();

        }

    }
}
