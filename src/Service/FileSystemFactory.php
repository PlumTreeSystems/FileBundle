<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-28
 * Time: 20:37
 */

namespace PlumTreeSystems\FileBundle\Service;

use Aws\S3\S3Client;
use Gaufrette\Adapter;
use Gaufrette\Filesystem;
use PlumTreeSystems\FileBundle\Model\FileSystemFactoryInterface;

class FileSystemFactory implements FileSystemFactoryInterface
{
    private $filesystem;
    private $adapter;

    /**
     * AdapterFactory constructor.
     * @param $provider
     * @param $config
     */
    public function __construct($provider, $config)
    {
        switch ($provider) {
            case 'local':
                $this->adapter = new Adapter\Local($config['directory'], true);
                $this->filesystem = new Filesystem($this->adapter);
                break;
            case 'aws_s3':
                $client = new S3Client([
                    'credentials' => [
                        'key' => $config['key'],
                        'secret' => $config['secret'],
                    ],
                    'version' => $config['version'],
                    'region' => $config['region']
                ]);
                $this->adapter = new Adapter\AwsS3($client, $config['bucket_name']);
                $this->filesystem = new Filesystem($this->adapter);
                break;
        }
    }

    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }
}
