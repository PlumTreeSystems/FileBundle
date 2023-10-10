<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:38
 */

namespace PlumTreeSystems\FileBundle\Controller;

use Doctrine\Persistence\ObjectManager;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use PlumTreeSystems\FileBundle\Security\SecurityManager;
use PlumTreeSystems\FileBundle\Service\GaufretteFileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileController extends AbstractController
{
    public function downloadAction(
        $id,
        SecurityManager $securityManager,
        FileManagerInterface $fileManager,
        ObjectManager $om,
        string $ptsFileExtendedEntity
    ) {
        $file = $om->find($ptsFileExtendedEntity, $id);
        if (!$file) {
            throw new NotFoundHttpException("File not found by id: '" . $id . "'");
        }
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
        FileManagerInterface $fileManager,
        ObjectManager $om,
        string $ptsFileExtendedEntity
    ) {
        $backUrl = $request->get('backUrl', '/');

        $file = $om->find($ptsFileExtendedEntity, $id);
        if (!$file) {
            throw new NotFoundHttpException("File not found by id: '" . $id . "'");
        }
        $user = $this->getUser();

        if ($securityManager->checkPermissions($user, $file)) {
            $backUrl = urldecode($backUrl);
            $fileManager->remove($file);
            $om->remove($file);
            $om->flush();
            return $this->redirect($backUrl);
        }
        throw new AccessDeniedHttpException("You have no access to this file");
    }
}
