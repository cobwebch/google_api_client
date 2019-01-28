<?php

namespace Cobweb\GoogleApiClient\Service;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Cobweb\GoogleApiClient\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utilities related to authentication.
 */
class AuthenticatedUserService implements SingletonInterface
{
    const ROLE_ADMIN = 'admin';
    const ROLE_REVIEWER = 'reviewer';

    /**
     * Gets a singleton instance of this class.
     *
     * @return \Cobweb\GoogleApiClient\Service\AuthenticatedUserService|object
     */
    static public function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        $userData = $this->getUserData();
        return isset($userData['uid']) ? $userData['uid'] : 0;
    }

    /**
     * Returns an instance of the current Frontend User.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return !empty($this->getTypoScriptFrontendController()->fe_user->user);
    }

    /**
     * Returns user data of the current Frontend User.
     *
     * @return array
     */
    public function getUserData()
    {
        return $this->getTypoScriptFrontendController()->fe_user->user ?: [];
    }

    /**
     * @return string
     */
    public function getRole()
    {
        $role = '';

        if ($this->isAdmin()) {
            $role = self::ROLE_ADMIN;
        } elseif ($this->isReviewer()) {
            $role = self::ROLE_REVIEWER;
        }

        return $role;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        $userData = $this->getUserData();
        $userGroups = GeneralUtility::trimExplode(',', $userData['usergroup'], true);

        $adminUserGroupIdentifier = $this->getExtensionConfiguration()->get('admin_user_group');
        return in_array($adminUserGroupIdentifier, $userGroups, true);
    }

    /**
     * @return bool
     */
    public function isReviewer()
    {
        $userData = $this->getUserData();
        $userGroups = GeneralUtility::trimExplode(',', $userData['usergroup'], true);

        $reviewerUserGroupIdentifier = $this->getExtensionConfiguration()->get('reviewer_user_group');
        return in_array($reviewerUserGroupIdentifier, $userGroups, true);
    }

    /**
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->getTypoScriptFrontendController()->fe_user->id;
    }

    /**
     * Returns a value given a fieldName
     *
     * @param string $fieldName
     * @return mixed
     */
    public function get($fieldName)
    {
        $userData = $this->getUserData();
        return isset($userData[$fieldName]) ? $userData[$fieldName] : NULL;
    }

    /**
     * Returns an instance of the Frontend object.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return object|ExtensionConfiguration
     */
    protected function getExtensionConfiguration()
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }
}