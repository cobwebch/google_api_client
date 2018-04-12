<?php

namespace Cobweb\GoogleApiClient\Controller;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Cobweb\GoogleApiClient\Service\GoogleDocumentService;
use Cobweb\GoogleApiClient\Service\Typo3UserService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class GooglePermissionController
 */
class GooglePermissionController extends ActionController
{

    /**
     * @param string $file
     * @param string $role
     */
    public function changeAllForFileAction($file, $role)
    {
        // Notice: $role is validated in method changeAllPermissionsForFile
        $this->getGoogleDocumentService()->changeAllPermissionsForFile($file, $role);
        $this->addFlashMessage('Permission was changed for all reviewers', '', FlashMessage::OK);

        $this->redirect('index', 'GoogleDocument');
    }

    /**
     * @param string $emailAddress
     * @param string $role
     */
    public function changeAllForUserAction($emailAddress, $role)
    {
        // Notice: $role is validated in method changeAllPermissionsForFile
        $this->getGoogleDocumentService()->changeAllPermissionsForEmailAddress($emailAddress, $role);
        $this->addFlashMessage('Permission was changed for all reviewers', '', FlashMessage::OK);

        $this->redirect('index', 'GoogleDocument');
    }

    /**
     * @param string $file
     * @param string $role
     * @param string $emailAddress
     */
    public function changeAction($file, $role, $emailAddress)
    {
        // Notice: $role is validated in method changeAllPermissionsForFile
        $this->getGoogleDocumentService()->changePermissionForEmailAddress($emailAddress, $file, $role);
        $this->addFlashMessage('Permission was changed for reviewer ' . $emailAddress, '', FlashMessage::OK);

        $this->redirect('index', 'GoogleDocument');
    }

    /**
     * @param string $file
     */
    public function removeAllForFileAction($file)
    {
        $this->getGoogleDocumentService()->removeAllPermissionsForFile($file);
        $this->addFlashMessage('Permission was removed for all reviewers', '', FlashMessage::OK);

        $this->redirect('index', 'GoogleDocument');
    }

    /**
     * @param string $emailAddress
     */
    public function removeAllForUserAction($emailAddress)
    {
        $this->getGoogleDocumentService()->removeAllPermissionsForEmailAddress($emailAddress);
        $this->addFlashMessage('Permission was removed for all reviewers', '', FlashMessage::OK);

        $this->redirect('index', 'GoogleDocument');
    }

    /**
     * @param string $file
     * @param string $emailAddress
     */
    public function removeAction($file, $emailAddress)
    {
        $user = $this->getTypo3UserService()->findReviewerByEmailAddress($emailAddress);
        if ($user['google_permission_id']) {
            $this->getGoogleDocumentService()->removePermissionFor($file, $user['google_permission_id']);
            $this->addFlashMessage('Permission was removed for reviewer ' . $user, '', FlashMessage::OK);
        } else {
            $message = sprintf('Missing "google_permission_id" value for user %s having username "%s"', $user['uid'], $user['username']);
            $this->addFlashMessage($message, '', FlashMessage::WARNING);
            #$this->getLogger()->warning($message, $user);
        }

        $this->redirect('index', 'GoogleDocument');
    }

    /**
     * @return object|GoogleDocumentService
     */
    protected function getGoogleDocumentService()
    {
        return GeneralUtility::makeInstance(GoogleDocumentService::class);
    }

    /**
     * @return object|Typo3UserService
     */
    public function getTypo3UserService()
    {
        return GeneralUtility::makeInstance(Typo3UserService::class);
    }

}
