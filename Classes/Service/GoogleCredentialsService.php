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
        GeneralUtility::writeFileToTypo3tempDir($this->getCredentialsFile(), json_encode($credentials));
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
        $credentialsDirectoryName = 'google_api_credentials';
        // Create language file dynamically
        $credentialsDirectory = PATH_site . 'typo3temp/' . $credentialsDirectoryName;
        if (!is_dir($credentialsDirectory)) {
            GeneralUtility::mkdir($credentialsDirectory);
        }
        return $credentialsDirectory;
    }

    /**
     * @return object|AuthenticatedUserService
     */
    public function getAuthenticatedUserService()
    {
        return GeneralUtility::makeInstance(AuthenticatedUserService::class);
    }

}
