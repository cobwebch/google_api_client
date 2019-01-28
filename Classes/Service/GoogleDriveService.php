<?php

namespace Cobweb\GoogleApiClient\Service;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Google_Service_Plus;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class GoogleDriveService
 */
class GoogleDriveService implements SingletonInterface
{
    /**
     * @var \Google_Client
     */
    protected $client;

    /**
     * @var \Google_Service_Drive
     */
    protected $driveService;

    /**
     * @return \Google_Client
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = new \Google_Client();
            $this->client->setAuthConfig($this->getAuthenticationFile());
            #$this->client->addScope(\Google_Service_Drive::DRIVE);
            $this->client->setScopes([
                \Google_Service_Drive::DRIVE,
                \Google_Service_Drive::DRIVE_APPDATA,
                \Google_Service_Drive::DRIVE_METADATA,
                \Google_Service_Drive::DRIVE_FILE,
                \Google_Service_Oauth2::USERINFO_EMAIL
            ]);

            # See if this is necessary
            # https://github.com/nadavkav/moodle-mod_googledrive/blob/master/classes/googledrive.php#L105
            #$this->client->setRedirectUri('https://gichd-litmos.cobweb.blue/what-we-do/overview/');

            // See if that is required
            $this->client->setAccessType('offline');
            $this->client->setApprovalPrompt('force');
            #$this->client->setApplicationName('GICHD');
        }

        $this->handleOAuthAuthentication();
        return $this->client;
    }

    /**
     * @return void
     */
    protected function handleOAuthAuthentication()
    {
        if (!$this->getGoogleCredentialsService()->hasCurrentUserCredentials()) {
            if (GeneralUtility::_GP('code')) {
                $this->client->fetchAccessTokenWithAuthCode(GeneralUtility::_GP('code'));
                $accessToken = $this->client->getAccessToken();

                $plus = new Google_Service_Plus($this->client);
                $googleEmails = $plus->people
                    ->get('me')
                    ->getEmails();

                // Check that the current Google account corresponds to the email address defines in the FE User.
                $isValid = $this->getTypo3SecurityService()->isGoogleAccountEmailValid($googleEmails);
                if (!$isValid) {
                    // hard redirect to the 404 page
                    // todo make me configurable!!
                    $url = $this->getContentObject()->typoLink_URL(['parameter' => 69]);
                    HttpUtility::redirect($url);
                }

                if ($accessToken) {
                    $this->getGoogleCredentialsService()->writeCredentials($this->client->getAccessToken());
                }
                $url = $this->getContentObject()->typoLink_URL(['parameter' => $this->getTypoScriptFrontendController()->id]);
                HttpUtility::redirect($url);
            } else {
                $url = $this->client->createAuthUrl();
                HttpUtility::redirect($url);
            }
        } else {
            $accessToken = $this->getGoogleCredentialsService()->getCredentials();
            $this->client->setAccessToken($accessToken);

            //Refresh the token if it's expired.
            if ($this->client->isAccessTokenExpired()) {

                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

                # Refresh token is only returned once! Quite a strange behavior!
                # We must merge to the first received access token
                # https://stackoverflow.com/questions/38467374/google-api-refresh-token-null-and-how-to-refresh-access-token/41105959#41105959
                $newAccessToken = $this->client->getAccessToken();
                $accessToken = array_merge($accessToken, $newAccessToken);
                $this->client->setAccessToken($accessToken);
                $this->getGoogleCredentialsService()->writeCredentials($accessToken);
            }
        }
    }

    /**
     * @return \Google_Service_Drive
     */
    public function getInstance()
    {
        if ($this->driveService === null) {
            $client = $this->getClient();
            $this->driveService = new \Google_Service_Drive($client);
        }
        return $this->driveService;
    }

    /**
     * @return object|ContentObjectRenderer
     */
    protected function getContentObject()
    {
        return GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * @return object|ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return object|ConfigurationUtility
     */
    protected function getConfigurationUtility()
    {
        return $this->getObjectManager()->get(ConfigurationUtility::class);
    }

    /**
     * @return array
     */
    protected function getExtensionConfiguration()
    {
        return $this->getConfigurationUtility()->getCurrentConfiguration('google_api_client');
    }

    /**
     * @return string
     */
    protected function getAuthenticationFile()
    {
        return PATH_site . $this->getExtensionConfiguration()['secret_google_api_client_authentication_file']['value'];
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
     * @return object|GoogleCredentialsService
     */
    protected function getGoogleCredentialsService()
    {
        return GeneralUtility::makeInstance(GoogleCredentialsService::class);
    }

    /**
     * @return Typo3SecurityService|object
     */
    protected function getTypo3SecurityService(): Typo3SecurityService
    {
        return GeneralUtility::makeInstance(Typo3SecurityService::class);
    }

}
