<?php

namespace Cobweb\GoogleApiClient\Service;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;

/**
 * Class GoogleCredentialsService
 */
class GoogleCredentialsService implements SingletonInterface
{

    /**
     * @param array $credentials
     */
    public function writeCredentials(array $credentials)
    {
        $result = file_put_contents($this->getCredentialsFile(), json_encode($credentials));
        if (!$result) {
            throw new \RuntimeException('I could not write file ' . $this->getCredentialsFile());
        }
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        $jsonEncodedCredentials = file_get_contents($this->getCredentialsFile());
        return json_decode($jsonEncodedCredentials, true);
    }

    /**
     * @return bool
     */
    public function hasCurrentUserCredentials()
    {
        return file_exists($this->getCredentialsFile());
    }

    /**
     * @return string
     */
    protected function getCredentialsFile()
    {
        if (!$this->getAuthenticatedUserService()->isAuthenticated()) {
            throw new \RuntimeException('A Frontend User must be authenticated', 1516713794);
        }

        $userData = $this->getAuthenticatedUserService()->getUserData();
        $userSignature = sprintf('.fe_user_%s_%s', $userData['uid'], $userData['crdate']);

        # If we want to link the credentials to the User session, we could do this.
        #$userSignature = sprintf('.session_%s', $this->getAuthenticatedUserService()->getSessionId());

        return sprintf(
            '%s/%s_credentials.json',
            $this->getCredentialsDirectory(),
            $userSignature
        );
    }

    /**
     * @return string
     */
    protected function getCredentialsDirectory()
    {
        // Create language file dynamically
        $credentialsDirectory = realpath(
            dirname(PATH_site . $this->getExtensionConfiguration()['secret_google_api_client_authentication_file']['value'])
        );
        if (!is_dir($credentialsDirectory)) {
            GeneralUtility::mkdir($credentialsDirectory);
        }
        return $credentialsDirectory;
    }

    /**
     * Clean token files older than 10 days
     */
    public function cleanOldTokenFiles() {
        $tokenFolder = $this->getCredentialsDirectory()."/.fe_user_*";

        /************** RPR DEBUG *************/
        $files = glob($tokenFolder);
        $now   = time();

        foreach ($files as $file) {
            if (is_file($file) && !strpos($file,'client_id.json')) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * 2) { // 2 days
                    unlink($file);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getExtensionConfiguration()
    {
        return $this->getConfigurationUtility()->getCurrentConfiguration('google_api_client');
    }

    /**
     * @return object|ConfigurationUtility
     */
    protected function getConfigurationUtility()
    {
        return $this->getObjectManager()->get(ConfigurationUtility::class);
    }

    /**
     * @return object|ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return object|AuthenticatedUserService
     */
    public function getAuthenticatedUserService()
    {
        return GeneralUtility::makeInstance(AuthenticatedUserService::class);
    }

}
