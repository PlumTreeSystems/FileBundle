<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-28
 * Time: 20:37
 */

namespace PlumTreeSystems\FileBundle\Service;

use Aws\S3\S3Client;
use Exception;
use Gaufrette\Adapter;
use Gaufrette\Filesystem;
use Google_Client;
use Google_Exception;
use Google_Service_Storage;
use PlumTreeSystems\FileBundle\Model\FileSystemFactoryInterface;

class FileSystemFactory implements FileSystemFactoryInterface
{
    private $filesystem;
    private $adapter;

    /**
     * AdapterFactory constructor.
     * @param $provider
     * @param $config
     * @throws Exception
     */
    public function __construct($provider, $config)
    {
        switch ($provider) {
            case 'local':
                $this->setUpLocalFileSystem($config);
                break;
            case 'aws_s3':
                $this->setUpAwsFileSystem($config);
                break;
            case 'google_cloud_storage':
                $this->setUpGoogleFileSystem($config);
                break;
        }
    }

    private function setUpLocalFileSystem($config)
    {
        $this->adapter = new Adapter\Local($config['directory'], true);
        $this->filesystem = new Filesystem($this->adapter);
    }

    private function setUpAwsFileSystem($config)
    {
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
    }

    /**
     * @param $config
     * @throws Exception
     */
    private function setUpGoogleFileSystem($config)
    {
        $client = new Google_Client();

        if (
            !key_exists('auth_config_json', $config)
            || (key_exists('auth_config_json', $config) && !$config['auth_config_json'])
        ) {
            throw new Exception('invalid configuration');
        }

        $authConfig = json_decode($config['auth_config_json'], true);

        if (!is_array($authConfig)) {
            throw new Exception('invalid configuration');
        }

        $client->setAuthConfig($authConfig);

        $client->addScope(['https://www.googleapis.com/auth/devstorage.full_control']);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }

        $service = new Google_Service_Storage($client);
        $this->adapter = new Adapter\GoogleCloudStorage(
            $service,
            $config['google_bucket'],
            ['acl' => 'private'],
            true
        );
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }
}
