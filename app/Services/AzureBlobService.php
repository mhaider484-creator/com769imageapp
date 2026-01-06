<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobService
{
    protected BlobRestProxy $client;
    protected string $container;

    public function __construct()
    {
        $connectionString = sprintf(
            'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;EndpointSuffix=core.windows.net',
            config('services.azure.name'),
            config('services.azure.key')
        );

        $this->client = BlobRestProxy::createBlobService($connectionString);
        $this->container = config('services.azure.container');
    }

    /**
     * Upload a new file
     */
    public function upload(UploadedFile $file, string $folder = 'posts'): string
    {
        $filename = uniqid('', true).'.'.$file->getClientOriginalExtension();
        $path = trim($folder, '/').'/'.$filename;

        $this->client->createBlockBlob(
            $this->container,
            $path,
            fopen($file->getRealPath(), 'r')
        );

        return $path;
    }

    /**
     * Replace existing file (delete old → upload new)
     */
    public function update(?string $oldPath, UploadedFile $file, string $folder = 'posts'): string
    {
        if ($oldPath) {
            $this->delete($oldPath);
        }

        return $this->upload($file, $folder);
    }

    /**
     * Delete file
     */
    public function delete(string $path): void
    {
        try {
            $this->client->deleteBlob($this->container, $path);
        } catch (ServiceException $e) {
            // Ignore if blob does not exist
        }
    }

    /**
     * Get public URL (for public container)
     */
    public function url(string $path): string
    {
        return sprintf(
            'https://%s.blob.core.windows.net/%s/%s',
            config('services.azure.name'),
            $this->container,
            $path
        );
    }
}
