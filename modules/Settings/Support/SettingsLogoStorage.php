<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use Marwa\Support\File;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class SettingsLogoStorage
{
    private const UPLOAD_FIELD = 'settings_logo_url';
    private const UPLOAD_DIRECTORY = 'uploads/settings/interface';

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

    public function uploadedLogo(ServerRequestInterface $request): ?UploadedFileInterface
    {
        $upload = $this->nestedUploadedFile($request->getUploadedFiles(), [self::UPLOAD_FIELD]);

        if (!$upload instanceof UploadedFileInterface) {
            return null;
        }

        if ($upload->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $upload;
    }

    public function store(UploadedFileInterface $upload): string
    {
        if ($upload->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded logo could not be read.');
        }

        $extension = strtolower(pathinfo((string) $upload->getClientFilename(), PATHINFO_EXTENSION));

        if ($extension === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Upload a PNG, JPG, WebP, GIF, or SVG logo.');
        }

        $clientMediaType = strtolower((string) $upload->getClientMediaType());
        if ($clientMediaType !== '' && !str_starts_with($clientMediaType, 'image/') && $extension !== 'svg') {
            throw new RuntimeException('Upload a valid image file for the logo.');
        }

        $directory = public_path(self::UPLOAD_DIRECTORY);
        File::makeDirectory($directory);

        $relativePath = self::UPLOAD_DIRECTORY . '/logo.' . $extension;
        $destination = public_path($relativePath);

        $this->deleteExistingLogosExcept(basename($destination));

        if (is_file($destination)) {
            File::delete($destination);
        }

        $upload->moveTo($destination);

        return $relativePath;
    }

    public function remove(): void
    {
        $directory = public_path(self::UPLOAD_DIRECTORY);

        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . 'logo.*') ?: [] as $existingFile) {
            if (is_file($existingFile)) {
                File::delete($existingFile);
            }
        }
    }

    /**
     * @param array<int|string, mixed> $files
     * @param list<string> $path
     */
    private function nestedUploadedFile(array $files, array $path): mixed
    {
        $current = $files;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function deleteExistingLogosExcept(string $keepFilename): void
    {
        $directory = public_path(self::UPLOAD_DIRECTORY);

        foreach (glob($directory . DIRECTORY_SEPARATOR . 'logo.*') ?: [] as $existingFile) {
            if (is_file($existingFile) && basename($existingFile) !== $keepFilename) {
                File::delete($existingFile);
            }
        }
    }
}
