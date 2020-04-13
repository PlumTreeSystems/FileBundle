<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:38
 */

namespace PlumTreeSystems\FileBundle\Controller;

use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use PlumTreeSystems\FileBundle\Security\SecurityManager;
use PlumTreeSystems\FileBundle\Service\GaufretteFileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FileController extends AbstractController
{
    public function downloadAction($id, SecurityManager $securityManager, FileManagerInterface $fileManager)
    {
        /**
         * @var GaufretteFileManager $fileManager
         * @var SecurityManager $securityManager
         */
        $file = $fileManager->getById($id);
        $user = $this->getUser();
        if ($securityManager->checkPermissions($user, $file)) {
            return $fileManager->downloadFile($file);
        }
        throw new AccessDeniedHttpException("You have no access to this file");
    }

    public function removeAction(
        Request $request,
        $id,
        SecurityManager $securityManager,
        FileManagerInterface $fileManager
    ) {
        $backUrl = $request->get('backUrl', '/');

        /**
         * @var GaufretteFileManager $fileManager
         * @var SecurityManager $securityManager
         */
        $file = $fileManager->getById($id);
        $user = $this->getUser();

        if ($securityManager->checkPermissions($user, $file)) {
            $backUrl = urldecode($backUrl);
            $fileManager->removeEntity($file, true);
            return $this->redirect($backUrl);
        }
        throw new AccessDeniedHttpException("You have no access to this file");
    }
}
