<?php

namespace Cobweb\GoogleApiClient\Service;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Cobweb\GoogleApiClient\Configuration\DocumentConfiguration;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GoogleDocumentService
 */
class GoogleDocumentService implements SingletonInterface
{

    const ROLE_WRITER = 'writer';
    const ROLE_COMMENTER = 'commenter';
    const ROLE_READER = 'reader';

    /**
     * @var array
     */
    protected $allowedRoles = [
        self::ROLE_WRITER,
        self::ROLE_COMMENTER,
        self::ROLE_READER,
    ];

    /**
     * @param string $fileId
     * @return \Google_Service_Drive_DriveFile
     */
    public function get($fileId)
    {
        return $this->getDriveService()->getInstance()->files->get($fileId, ['fields' => "*"]);
    }

    /**
     * @param string $fileId
     * @return \Google_Service_Drive_DriveFile
     * @see https://developers.google.com/drive/v3/reference/permissions/delete
     */
    public function delete($fileId)
    {
        return $this->getDriveService()->getInstance()->files->delete($fileId);
    }

    /**
     * @param DocumentConfiguration|null $documentConfiguration
     */
    public function create(DocumentConfiguration $documentConfiguration = null)
    {
        $driveService = $this->getDriveService()->getInstance();

        //Create the file
        $file = new \Google_Service_Drive_DriveFile();

        if ($documentConfiguration) {
            $file->setName($documentConfiguration->getName());
        }

        $file->setMimeType('application/vnd.google-apps.document');

        $userData = $this->getAuthenticatedUserService()->getUserData();

        $file = $driveService->files->create($file);
        if (empty($userData['google_permission_id'])) {

            $file = $this->get($file->getId());
            $permissions = $file->getPermissions();
            if (isset($permissions[0])) {
                /** @var \Google_Service_Drive_Permission $permission */
                $permission = $permissions[0];
                $this->getTypo3UserService()->updatePermissionId($userData, $permission->getId());
            }
        }

        $this->changeAllPermissionsForFile($file->getId(), self::ROLE_WRITER);
    }

    /**
     * @param string $fileId
     * @param string $permissionId
     */
    public function removePermissionFor($fileId, $permissionId)
    {
        $driveService = $this->getDriveService()->getInstance();

        // Delete a permission
        try {
            $driveService->permissions->delete($fileId, $permissionId);
        } catch (\Google_Exception $e) {
            // could be a 404. No need to catch
        }
    }

    /**
     * @param string $fileId
     */
    public function removeAllPermissionsForFile($fileId)
    {
        $users = $this->getTypo3UserService()->getReviewerUsers();
        foreach ($users as $user) {
            if ($user['google_permission_id']) {
                $this->removePermissionFor($fileId, $user['google_permission_id']);
            } else {
                $message = sprintf('Missing "google_permission_id" value for user %s having username "%s"', $user['uid'], $user['username']);
                $this->getLogger()->warning($message, $user);
            }
        }
    }

    /**
     * @param string $emailAddress
     */
    public function removeAllPermissionsForEmailAddress($emailAddress)
    {
        $user = $this->getTypo3UserService()->findReviewerByEmailAddress($emailAddress);
        $this->removeAllPermissionsForUser($user);
    }

    /**
     * @param array $user
     */
    public function removeAllPermissionsForUser(array $user)
    {
        if (isset($user['google_permission_id'])) {
            $files = $this->getFiles();
            /** @var \Google_Service_Drive_DriveFile $file */
            foreach ($files as $file) {
                $this->removePermissionFor($file->getId(), $user['google_permission_id']);
            }
        }
    }

    /**
     * @param string $emailAddress
     * @param string $fileId
     * @param string $role possible value reader - commenter - owner
     */
    public function changePermissionForEmailAddress($emailAddress, $fileId, $role)
    {
        $user = $this->getTypo3UserService()->findReviewerByEmailAddress($emailAddress);
        $this->changePermissionForUser($user, $fileId, $role);
    }

    /**
     * @param array $user
     * @param string $fileId
     * @param string $role possible value reader - commenter - owner
     * @see https://developers.google.com/drive/v3/reference/permissions/create
     * @see https://developers.google.com/drive/v3/reference/permissions/update
     */
    public function changePermissionForUser(array $user, $fileId, $role)
    {
        if (!isset($user['connected_google_email'])) {
            return; // exit method. It should not exist
        }

        $this->validateRole($role);
        $driveService = $this->getDriveService()->getInstance();

        $permission = null; // declare variable.
        try {
            if ($user['google_permission_id']) {
                $permission = $driveService->permissions->get($fileId, $user['google_permission_id']);
            }
        } catch (\Google_Service_Exception $e) {
            // We could have a 404 status code, meaning we do not have an existing permission yet.
            // We do nothing, we just create a new permission later on.
        }

        if ($permission) {
            // Update a permission
            $permissionToUpdate = new \Google_Service_Drive_Permission();
            $permissionToUpdate->setRole($role);
            try {
                $driveService->permissions->update($fileId, $permission->getId(), $permissionToUpdate);
            } catch (\Google_Service_Exception $e) {
                // Could be 400 error message: You do not have permission to share these item
                // Do nothing...
            }
        } else {

            $permission = new \Google_Service_Drive_Permission();
            $permission->setEmailAddress($user['connected_google_email']);
            $permission->setType('user');
            $permission->setRole($role);

            try {
                $newPermission = $driveService->permissions->create($fileId, $permission);

                if (empty($user['google_permission_id'])) {
                    $this->getTypo3UserService()->updatePermissionId($user, $newPermission->getId());
                }
            } catch (\Google_Service_Exception $e) {
                // Could be 400 error message: You do not have permission to share these item
                // Do nothing...
            }
        }
    }

    /**
     * @param string $fileId
     * @param string $role possible value reader - commenter - owner
     */
    public function changeAllPermissionsForFile($fileId, $role)
    {
        $this->validateRole($role);
        $users = $this->getTypo3UserService()->getReviewerUsers();
        foreach ($users as $user) {
            $this->changePermissionForUser($user, $fileId, $role);
        }
    }

    /**
     * @param array $user
     * @param string $role possible value reader - commenter - owner
     */
    public function changeAllPermissionsForUser(array $user, $role)
    {
        $this->validateRole($role);

        $files = $this->getFiles();
        /** @var \Google_Service_Drive_DriveFile $file */
        foreach ($files as $file) {
            $this->changePermissionForUser($user, $file->getId(), $role);
        }
    }

    /**
     * @param string $emailAddress
     * @param string $role possible value reader - commenter - owner
     */
    public function changeAllPermissionsForEmailAddress($emailAddress, $role)
    {
        $user = $this->getTypo3UserService()->findReviewerByEmailAddress($emailAddress);
        $this->changeAllPermissionsForUser($user, $role);
    }

    /**
     * @return object|GoogleDriveService
     */
    protected function getDriveService()
    {
        return GeneralUtility::makeInstance(GoogleDriveService::class);
    }

    /**
     * @return \Google_Service_Drive_FileList
     * @see https://developers.google.com/drive/v3/reference/files/list
     */
    public function getFiles()
    {
        $driveService = $this->getDriveService()->getInstance();
        return $driveService->files->listFiles([
            #'q' => "'ignace.temporary@gmail.com' in readers",
            'orderBy' => 'name',
            'fields' => "*"
        ]);
    }

    /**
     * @return object|Typo3UserService
     */
    public function getTypo3UserService()
    {
        return GeneralUtility::makeInstance(Typo3UserService::class);
    }

    /**
     * @param string $role
     */
    protected function validateRole($role)
    {
        if (!in_array($role, $this->allowedRoles, true)) {
            throw new \RuntimeException(sprintf('Role is now allowed "%s"', $role), 1517315220);
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        /** @var $loggerManager LogManager */
        $loggerManager = GeneralUtility::makeInstance(LogManager::class);

        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        return $loggerManager->getLogger(get_class($this));
    }

    /**
     * @return object|AuthenticatedUserService
     */
    public function getAuthenticatedUserService()
    {
        return GeneralUtility::makeInstance(AuthenticatedUserService::class);
    }

}
