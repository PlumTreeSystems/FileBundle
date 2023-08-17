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
                'key' => $s3Config['credentials']['key'],
                'secret' => $s3Config['credentials']['secret']
            ],
            'region' => $s3Config['region']
        ]);

        $this->bucket = $s3Config['bucket'];
    }

    public function getClient(): S3Client
    {
        return $this->client;
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
        $stream = $file->getDataStream();

        if (!$stream) {
            $ref = $file->getUploadedFileReference();

            if (!$ref) {
                throw new NoUploadedFileException("UploadedFileReference not attached to File");
            }

            $stream = fopen($ref->getPathname(), 'r');
        }
        
        [$bucket, $key] = $this->extractBucketAndKey($file);
        $this->client->upload($bucket, $key, $stream);
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
        return [
            $this->bucket,
            join('/', [$file->getPath(), $file->getName()])
        ];
    }

}
