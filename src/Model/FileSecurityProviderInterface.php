<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:31
 */

namespace PlumTreeSystems\FileBundle\Model;

use PlumTreeSystems\FileBundle\Entity\File;
use Symfony\Component\Security\Core\User\UserInterface;

interface FileSecurityProviderInterface
{
    public function hasPermission(UserInterface $user = null, File $file): bool;
}
