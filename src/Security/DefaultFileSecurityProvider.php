<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-17
 * Time: 20:04
 */

namespace PlumTreeSystems\FileBundle\Security;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Model\FileSecurityProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DefaultFileSecurityProvider implements FileSecurityProviderInterface
{

    public function hasPermission(UserInterface $user = null, File $file): bool
    {
        return true;
    }
}
