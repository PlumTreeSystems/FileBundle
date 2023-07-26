<?php

namespace PlumTreeSystems\FileBundle\Provider;

use Aws\S3\S3Client;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Exception\NoUploadedFileException;

class S3FileProvider implements FileProviderInterface
{

    protected S3Client $client;

    protected string $bucket;

    public function __construct(array $s3Config)
    {
        $this->client = new S3Client([
            'credentials' => [
                'key' => $s3Config['key'],
                'secret' => $s3Config['secret']
            ],
            'region' => $s3Config['region']
        ]);

        $this->bucket = $s3Config['bucket'];

    }

    public function getAuthorizedRemoteUri(File $file): ?string 
    {
        [$bucket, $key] = $this->extractBucketAndKey($file);
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
        $req = $this->client->createPresignedRequest($cmd, '+1 minutes');
        return (string) $req->getUri();
    }
 
    public function persist(File $file)
    {
        
        $ref = $file->getUploadedFileReference();
        if (!$ref) {
            throw new NoUploadedFileException("UploadedFileReference not attached to File");
        }
        
        [$bucket, $key] = $this->extractBucketAndKey($file);
        $fd = fopen($ref->getPathname(), 'r');
        $this->client->upload($bucket, $key, $fd);
    }

    public function remove(File $file)
    {
        [$bucket, $key] = $this->extractBucketAndKey($file);
        $this->client->deleteObject(['Bucket' => $bucket, 'Key' => $key]);
    }

    public function getStreamableUri(File $file): string
    {
        $this->client->registerStreamWrapper();
        return 's3://' . $file->getPath();
    }

    public function getRawRemoteUri(File $file): string 
    {
        [$bucket, $key] = $this->extractBucketAndKey($file);
        return $this->client->getObjectUrl($bucket, $key);
    }

    protected function extractBucketAndKey(File $file) {
        return [$this->bucket, $file->getPath()];
    }

}
