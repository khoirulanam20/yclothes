<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    /**
     * @param  list<string>  $existingPaths
     * @param  list<string>  $removePaths
     * @param  list<UploadedFile>  $newFiles
     * @return list<string>
     */
    public function mergeGallery(
        array $existingPaths,
        array $removePaths,
        array $newFiles,
        string $storageDir,
    ): array {
        $kept = collect($existingPaths)
            ->filter(fn ($path) => $path && ! in_array($path, $removePaths, true))
            ->values();

        foreach ($removePaths as $path) {
            $this->deletePath($path);
        }

        foreach ($newFiles as $file) {
            if ($file instanceof UploadedFile) {
                $kept->push($file->store($storageDir, 'public'));
            }
        }

        return $kept->values()->all();
    }

    /** @param  list<string>|null  $paths */
    public function deleteGallery(?array $paths): void
    {
        if (! $paths) {
            return;
        }

        foreach ($paths as $path) {
            $this->deletePath($path);
        }
    }

    public function deletePath(?string $path): void
    {
        if (! $path || Str::startsWith($path, 'http')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @param  list<string>|null  $images */
    public function primaryPath(?array $images, ?string $fallback = null): ?string
    {
        $first = collect($images ?? [])->filter()->first();

        return $first ?: ($fallback ?: null);
    }

    /**
     * @param  list<string>|null  $images
     * @return list<string>
     */
    public function copyGallery(?array $images, string $storageDir): ?array
    {
        if (! $images) {
            return null;
        }

        return collect($images)
            ->map(fn (string $path) => $this->copyPath($path, $storageDir))
            ->filter()
            ->values()
            ->all();
    }

    public function copyPath(?string $path, string $storageDir): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, 'http')) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = $storageDir.'/'.Str::uuid().($extension ? '.'.$extension : '');

        Storage::disk('public')->copy($path, $newPath);

        return $newPath;
    }
}
