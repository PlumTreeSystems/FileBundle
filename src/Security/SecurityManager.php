<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-17
 * Time: 20:01
 */

namespace PlumTreeSystems\FileBundle\Security;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Model\FileSecurityProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityManager
{
    /**
     * @var FileSecurityProviderInterface[] $securityProviders
     */
    private $securityProviders;

    /**
     * SecurityManager constructor.
     */
    public function __construct()
    {
        $this->securityProviders = [];
    }

    public function addSecurityProvider(FileSecurityProviderInterface $provider)
    {
        $this->securityProviders[] = $provider;
    }

    public function checkPermissions(UserInterface $user = null, File $file)
    {
        foreach ($this->securityProviders as $securityProvider) {
            /**
             * @var FileSecurityProviderInterface $securityProvider
             */
            if (!$securityProvider->hasPermission($user, $file)) {
                return false;
            }
        }
        return true;
    }
}
