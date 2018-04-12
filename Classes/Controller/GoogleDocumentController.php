<?php

namespace Cobweb\GoogleApiClient\Controller;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Cobweb\GoogleApiClient\Configuration\DocumentConfiguration;
use Cobweb\GoogleApiClient\Service\AuthenticatedUserService;
use Cobweb\GoogleApiClient\Service\GoogleDocumentService;
use Cobweb\GoogleApiClient\Service\GoogleCredentialsService;
use Cobweb\GoogleApiClient\Service\Typo3UserService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class GoogleDocumentController
 */
class GoogleDocumentController extends ActionController
{

    /**
     * @return void
     */
    public function indexAction()
    {
        $this->view->assignMultiple([
            'isAuthenticated' => $this->getAuthenticatedUserService()->isAuthenticated(),
            'userData' => $this->getAuthenticatedUserService()->getUserData(),
            'isAdmin' => $this->getAuthenticatedUserService()->isAdmin(),
            'isReviewer' => $this->getAuthenticatedUserService()->isReviewer(),
            'reviewers' => $this->getTypo3UserService()->getReviewerUsers(),
        ]);

        if ($this->getAuthenticatedUserService()->getRole()) {
            $this->view->assign('files', $this->getGoogleDocumentService()->getFiles());
        }
    }

    /**
     * @return void
     */
    public function createAction()
    {
        $documentConfiguration = DocumentConfiguration::getInstance()
            ->setName(uniqid('GICHD ', true));

        $this->getGoogleDocumentService()->create($documentConfiguration);
        $this->redirect('index');
    }

    /**
     * @param string $file
     */
    public function showAction($file)
    {
        $this->view->assign('file', $this->getGoogleDocumentService()->get($file));
    }

    /**
     * @param string $file
     */
    public function removeAction($file)
    {
        $this->getGoogleDocumentService()->delete($file);
        $this->addFlashMessage('Document was removed', '', FlashMessage::OK);
        $this->redirect('index');
    }

    /**
     * @return object|GoogleDocumentService
     */
    protected function getGoogleDocumentService()
    {
        return GeneralUtility::makeInstance(GoogleDocumentService::class);
    }

    /**
     * @return object|GoogleCredentialsService
     */
    protected function getGoogleCredentialsService()
    {
        return GeneralUtility::makeInstance(GoogleCredentialsService::class);
    }

    /**
     * @return object|AuthenticatedUserService
     */
    protected function getAuthenticatedUserService()
    {
        return GeneralUtility::makeInstance(AuthenticatedUserService::class);
    }

    /**
     * @return object|Typo3UserService
     */
    protected function getTypo3UserService()
    {
        return GeneralUtility::makeInstance(Typo3UserService::class);
    }

}
