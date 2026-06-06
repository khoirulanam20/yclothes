<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminConfigurationService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $items = null;

    /** @return array<int, array<string, mixed>> */
    public function allItems(): array
    {
        if ($this->items === null) {
            $this->items = config('admin-configuration', []);
        }

        return $this->items;
    }

    /** @return array<int, array<string, mixed>> */
    public function getCategories(): array
    {
        return collect($this->allItems())
            ->filter(fn (array $item) => ! str_contains($item['key'], '.'))
            ->sortBy('sort')
            ->values()
            ->map(fn (array $item) => $this->serializeCategory($item))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function findItem(string $key): ?array
    {
        foreach ($this->allItems() as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function getSection(string $slug): array
    {
        $item = $this->findItem($slug);
        if (! $item) {
            abort(404);
        }

        if (($item['type'] ?? null) === 'link') {
            return [
                'key' => $item['key'],
                'name' => $item['name'],
                'info' => $item['info'] ?? null,
                'type' => 'link',
                'href' => $item['href'] ?? '#',
            ];
        }

        $fields = collect($item['fields'] ?? [])
            ->map(fn (array $field) => $this->serializeField($field))
            ->values()
            ->all();

        return [
            'key' => $item['key'],
            'name' => $item['name'],
            'info' => $item['info'] ?? null,
            'type' => 'form',
            'fields' => $fields,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $query): array
    {
        $query = Str::lower(trim($query));
        if (strlen($query) < 2) {
            return [];
        }

        $results = [];

        foreach ($this->allItems() as $item) {
            if (! str_contains($item['key'], '.')) {
                continue;
            }

            $haystack = Str::lower(($item['name'] ?? '').' '.($item['info'] ?? '').' '.$item['key']);
            if (! str_contains($haystack, $query)) {
                continue;
            }

            $parentKey = Str::beforeLast($item['key'], '.');
            if (str_contains($item['key'], '.')) {
                $parts = explode('.', $item['key']);
                $parentKey = $parts[0];
            }

            $parent = $this->findItem($parentKey) ?? $this->findItem(explode('.', $item['key'])[0]);

            $results[] = [
                'key' => $item['key'],
                'name' => $item['name'],
                'info' => $item['info'] ?? null,
                'category' => $parent['name'] ?? '',
                'href' => '/admin/configuration/'.str_replace('.', '/', $item['key']),
            ];
        }

        return array_slice($results, 0, 15);
    }

    public function validateAndSave(string $slug, Request $request): void
    {
        $item = $this->findItem($slug);
        if (! $item || ($item['type'] ?? null) === 'link') {
            abort(404);
        }

        $fields = $item['fields'] ?? [];
        $rules = [];
        $attributes = [];

        foreach ($fields as $field) {
            if (! $this->fieldIsVisible($field, $request)) {
                continue;
            }

            if (! empty($field['validation'])) {
                $rules[$field['name']] = $field['validation'];
            }

            $attributes[$field['name']] = $field['title'] ?? $field['name'];

            if ($field['type'] === 'image') {
                $rules['remove_'.$field['name']] = 'nullable|boolean';
            }
        }

        $validated = Validator::make($request->all(), $rules, [], $attributes)->validate();

        foreach ($fields as $field) {
            if (! $this->fieldIsVisible($field, $request)) {
                continue;
            }

            $name = $field['name'];

            if ($field['type'] === 'boolean') {
                Setting::updateOrCreate(
                    ['key' => $name],
                    ['value' => $request->boolean($name) ? '1' : '0'],
                );

                continue;
            }

            if ($field['type'] === 'image') {
                $this->saveImageField($name, $field, $request);

                continue;
            }

            if ($field['type'] === 'password' && blank($request->input($name))) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $name],
                ['value' => $validated[$name] ?? $request->input($name)],
            );
        }
    }

    /** @return array<string, mixed> */
    private function serializeCategory(array $category): array
    {
        $prefix = $category['key'].'.';

        $children = collect($this->allItems())
            ->filter(function (array $item) use ($category) {
                if ($item['key'] === $category['key']) {
                    return false;
                }

                if (! str_starts_with($item['key'], $category['key'].'.')) {
                    return false;
                }

                return isset($item['fields']) || ($item['type'] ?? null) === 'link';
            })
            ->sortBy('sort')
            ->values()
            ->map(fn (array $child) => [
                'key' => $child['key'],
                'name' => $child['name'],
                'info' => $child['info'] ?? null,
                'type' => $child['type'] ?? 'form',
                'href' => '/admin/configuration/'.str_replace('.', '/', $child['key']),
            ])
            ->all();

        return [
            'key' => $category['key'],
            'name' => $category['name'],
            'info' => $category['info'] ?? null,
            'children' => $children,
        ];
    }

    /** @param array<string, mixed> $field */
    private function serializeField(array $field): array
    {
        $name = $field['name'];
        $type = $field['type'];
        $stored = Setting::where('key', $name)->value('value');
        $value = $stored ?? ($field['default'] ?? null);

        if ($type === 'boolean') {
            $value = ($value === '1' || $value === 1 || $value === true);
        } elseif ($type === 'number') {
            $value = $value !== null && $value !== '' ? (int) $value : null;
        }

        $serialized = [
            'name' => $name,
            'title' => $field['title'],
            'type' => $type,
            'value' => $value,
            'depends' => $field['depends'] ?? null,
            'options' => $field['options'] ?? null,
        ];

        if ($type === 'image' && $value) {
            $serialized['url'] = storage_url($value);
        }

        if ($type === 'password' && filled($value)) {
            $serialized['hasValue'] = true;
            $serialized['value'] = '';
        }

        return $serialized;
    }

    /** @param array<string, mixed> $field */
    private function fieldIsVisible(array $field, Request $request): bool
    {
        if (empty($field['depends'])) {
            return true;
        }

        [$depField, $depValue] = explode(':', $field['depends'], 2);

        if (($field['type'] ?? '') === 'boolean' || str_ends_with($depField, '_enabled')) {
            return $request->boolean($depField) === ($depValue === '1');
        }

        return (string) $request->input($depField) === $depValue;
    }

    /** @param array<string, mixed> $field */
    private function saveImageField(string $name, array $field, Request $request): void
    {
        if ($request->hasFile($name)) {
            $old = Setting::where('key', $name)->value('value');
            if ($old) {
                Storage::disk($field['disk'] ?? 'public')->delete($old);
            }

            $path = $field['path'] ?? 'uploads';
            $stored = $request->file($name)->store($path, $field['disk'] ?? 'public');
            Setting::updateOrCreate(['key' => $name], ['value' => $stored]);

            return;
        }

        if ($request->boolean('remove_'.$name)) {
            $old = Setting::where('key', $name)->value('value');
            if ($old) {
                Storage::disk($field['disk'] ?? 'public')->delete($old);
            }
            Setting::updateOrCreate(['key' => $name], ['value' => null]);
        }
    }
}
