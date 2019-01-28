<?php

namespace Cobweb\GoogleApiClient\Service;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Google_Service_Plus_PersonEmails;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class Typo3SecurityService
 */
class Typo3SecurityService implements SingletonInterface
{

    /**
     * Check that the current Google account corresponds to the email address defines in the FE User.
     *
     * @param array $personEmails
     * @return bool
     */
    public function isGoogleAccountEmailValid(array $personEmails): bool
    {
        $googleEmails = [];
        $configuredEmail = trim($this->getUserData()['connected_google_email']);
        if ($configuredEmail) {
            /** @var Google_Service_Plus_PersonEmails $personEmail */
            foreach ($personEmails as $personEmail) {
                $googleEmails[] = $personEmail->getValue();
            }
        }
        return in_array($configuredEmail, $googleEmails, true);
    }

    /**
     * Returns user data of the current Frontend User.
     * @return array
     */
    protected function getUserData(): array
    {
        return $this->getFrontendUser()->user ? $this->getFrontendUser()->user : [];
    }

    /**
     * Returns an instance of the current Frontend User.
     *
     * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    protected function getFrontendUser()
    {
        return $GLOBALS['TSFE']->fe_user;
    }
}
