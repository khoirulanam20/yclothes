<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StorageCopyService
{
    public function copyPublicFile(string $sourcePath, string $destinationDirectory): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException('File banner promosi tidak ditemukan di storage.');
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $destPath = trim($destinationDirectory, '/').'/'.Str::uuid().'.'.$extension;
        $disk->copy($sourcePath, $destPath);

        return $destPath;
    }
}
