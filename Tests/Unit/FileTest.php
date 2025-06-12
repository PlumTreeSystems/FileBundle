<?php

namespace PlumTreeSystems\FileBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testFileContextManipulation()
    {
        $file = new \PlumTreeSystems\FileBundle\Tests\Functional\TestFile();

        $file->addContext('Content-Type', 'test/ext');
        $this->assertEquals(['Content-Type' => 'test/ext'], $file->getContext());
        $this->assertEquals('test/ext', $file->getContextValue('Content-Type'));

        $file->addContext('filesize', '0');
        $this->assertEquals(['Content-Type' => 'test/ext', 'filesize' => '0'], $file->getContext());

        $file->removeContext('Content-Type');
        $this->assertNull($file->getContextValue('Content-Type'));
        $this->assertEquals('0', $file->getContextValue('filesize'));
    }
}
