<?php

namespace Cobweb\GoogleApiClient\Service;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Cobweb\GoogleApiClient\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Typo3UserService
 */
class Typo3UserService implements SingletonInterface
{

    /**
     * @var string
     */
    protected $tableName = 'fe_users';

    /**
     * Gets a singleton instance of this class.
     *
     * @return self|object
     */
    static public function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * @param int $groupIdentifier
     * @return array
     */
    protected function getUsers($groupIdentifier = 0)
    {
        $clause = $groupIdentifier > 0
            ? sprintf('FIND_IN_SET(usergroup, %s) ', $groupIdentifier)
            : '1 = 1 ';

        $clause .= $this->getClauseForEnabledFields();
        $users = $this->getDatabaseConnection()->exec_SELECTgetRows('*', $this->tableName, $clause);
        $this->validateUsers($users); // make sure we have valid users
        return $users;
    }

    /**
     * @param string $emailAddress
     * @return array
     */
    public function findReviewerByEmailAddress($emailAddress)
    {
        $clause = $emailAddress
            ? sprintf(
                '(FIND_IN_SET(usergroup, %s) OR FIND_IN_SET(usergroup, %s)) AND connected_google_email = "%s"',
                $reviewerUserGroupIdentifier = $this->getExtensionConfiguration()->get('reviewer_user_group'),
                $adminUserGroupIdentifier = $this->getExtensionConfiguration()->get('admin_user_group'),
                $emailAddress
            )
            : '1 = 0 ';

        $clause .= $this->getClauseForEnabledFields();
        $user = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $this->tableName, $clause);
        return is_array($user)
            ? $user
            : [];
    }

    /**
     * @param int $uid
     * @return array
     */
    public function findReviewerById($uid)
    {
        $clause = (int)$uid > 0
            ? sprintf(
                '(FIND_IN_SET(usergroup, %s)) AND uid = %s',
                $reviewerUserGroupIdentifier = $this->getExtensionConfiguration()->get('reviewer_user_group'),
                #$adminUserGroupIdentifier = $this->getExtensionConfiguration()->get('admin_user_group'),
                (int)$uid
            )
            : '1 = 0 ';

        $user = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $this->tableName, $clause);
        return is_array($user)
            ? $user
            : [];
    }

    /**
     * @param array $users
     * @return void
     */
    protected function validateUsers(array $users)
    {
        foreach ($users as $user) {
            if (empty($user['connected_google_email'])) {
                $message = sprintf('Misconfiguration: missing a connected Google email for username "%s". ', $user['username']);
                throw new \RuntimeException($message, 1516790723);
            }
        }
    }

    /**
     * @return array
     */
    public function getReviewerUsers()
    {
        $reviewerUserGroupIdentifier = $this->getExtensionConfiguration()->get('reviewer_user_group');
        return $this->getUsers($reviewerUserGroupIdentifier);
    }

    /**
     * @param int $userIdentifier
     * @return bool
     */
    public function isReviewer($userIdentifier)
    {
        $user = $this->findReviewerById($userIdentifier);
        return !empty($user);
    }

    /**
     * @return array
     */
    public function getAdminUsers()
    {
        $adminUserGroupIdentifier = $this->getExtensionConfiguration()->get('admin_user_group');
        return $this->getUsers($adminUserGroupIdentifier);
    }

    /**
     * @param array $user
     * @param string $permissionId
     * @return void
     */
    public function updatePermissionId(array $user, $permissionId)
    {
        $values = ['google_permission_id' => $permissionId];
        $this->getDatabaseConnection()->exec_UPDATEquery($this->tableName, 'uid = ' . $user['uid'], $values);
    }

    /**
     * Get the WHERE clause for the enabled fields of this TCA table
     * depending on the context
     *
     * @return string the additional where clause, something like " AND deleted=0 AND hidden=0"
     */
    protected function getClauseForEnabledFields()
    {
        // frontend context
        if ($this->isFrontendMode()) {

            $whereClause = $this->getPageRepository()->enableFields($this->tableName);
            $whereClause .= $this->getPageRepository()->deleteClause($this->tableName);
        } else {
            // backend context
            $whereClause = BackendUtility::BEenableFields($this->tableName);
            $whereClause .= BackendUtility::deleteClause($this->tableName);
        }
        return $whereClause;
    }

    /**
     * Returns whether the current mode is Frontend
     *
     * @return bool
     */
    protected function isFrontendMode()
    {
        return TYPO3_MODE === 'FE';
    }

    /**
     * Returns an instance of the page repository.
     *
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepository()
    {
        return $GLOBALS['TSFE']->sys_page;
    }

    /**
     * Returns a pointer to the database.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return object|ExtensionConfiguration
     */
    protected function getExtensionConfiguration()
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    /**
     * @return object|GoogleDocumentService
     */
    protected function getGoogleDocumentService()
    {
        return GeneralUtility::makeInstance(GoogleDocumentService::class);
    }


}