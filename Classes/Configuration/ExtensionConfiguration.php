<?php

namespace Cobweb\GoogleApiClient\Configuration;

/*
 * This file is part of the Cobweb/GoogleApiClient project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;


/**
 * Class ExtensionConfiguration
 */
class ExtensionConfiguration implements SingletonInterface
{

    /**
     * @var array
     */
    protected $configuration;

    /**
     * ExtensionConfiguration constructor.
     */
    public function __construct()
    {
        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['google_api_client']);

        if (empty($this->configuration['admin_user_group'])) {
            throw new \RuntimeException('Missing "admin_user_group" configuration. This can be set in the Extension Manager.', 1516712143);
        }

        if (empty($this->configuration['reviewer_user_group'])) {
            throw new \RuntimeException('Missing "reviewer_user_group" configuration. This can be set in the Extension Manager.', 1516712144);
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        return isset($this->configuration[$key])
            ? (string)$this->configuration[$key]
            : '';
    }



}