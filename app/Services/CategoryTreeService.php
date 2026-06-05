<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoryTreeService
{
    /**
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    public function expandIds(array $categoryIds, bool $includeSelf = true): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $all = Category::all()->keyBy('id');
        $expanded = [];

        foreach ($categoryIds as $id) {
            $category = $all->get($id);
            if (! $category) {
                continue;
            }

            if ($includeSelf) {
                $expanded[] = $category->id;
            }

            $this->collectDescendantIds($category, $all, $expanded);
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @param  Collection<int, Category>  $all
     * @param  list<int>  $expanded
     */
    private function collectDescendantIds(Category $category, Collection $all, array &$expanded): void
    {
        foreach ($all as $child) {
            if ($child->parent_id !== $category->id) {
                continue;
            }

            $expanded[] = $child->id;
            $this->collectDescendantIds($child, $all, $expanded);
        }
    }

    /**
     * @param  Collection<int, Category>  $roots
     * @return list<array{id: int, name: string, slug: string, depth: int}>
     */
    public function flattenForSelect(Collection $roots, int $depth = 0): array
    {
        $items = [];

        foreach ($roots as $category) {
            $items[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'depth' => $depth,
            ];

            if ($category->relationLoaded('children') && $category->children->isNotEmpty()) {
                $items = array_merge($items, $this->flattenForSelect($category->children, $depth + 1));
            }
        }

        return $items;
    }

    /**
     * @param  Collection<int, Category>  $roots
     * @return list<array<string, mixed>>
     */
    public function serializeTree(Collection $roots): array
    {
        return $roots->map(fn (Category $category) => $this->serializeNode($category))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeNode(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'imageUrl' => $category->image_url,
            'order' => $category->order,
            'parentId' => $category->parent_id,
            'productsCount' => $category->products_count ?? null,
            'childrenCount' => $category->children_count ?? ($category->relationLoaded('children') ? $category->children->count() : null),
            'children' => $category->relationLoaded('children')
                ? $this->serializeTree($category->children)
                : [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function flattenForIndex(Collection $roots, int $depth = 0, ?string $parentName = null): array
    {
        $rows = [];

        foreach ($roots as $category) {
            $rows[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'order' => $category->order,
                'depth' => $depth,
                'parentId' => $category->parent_id,
                'parentName' => $parentName,
                'productsCount' => $category->products_count ?? 0,
                'childrenCount' => $category->children_count ?? ($category->relationLoaded('children') ? $category->children->count() : 0),
            ];

            if ($category->relationLoaded('children') && $category->children->isNotEmpty()) {
                $rows = array_merge(
                    $rows,
                    $this->flattenForIndex($category->children, $depth + 1, $category->name),
                );
            }
        }

        return $rows;
    }

    /**
     * @return list<array{id: int, name: string, depth: int}>
     */
    public function parentOptions(?Category $exclude = null): array
    {
        $excludeIds = [];
        if ($exclude) {
            $excludeIds = $this->expandIds([$exclude->id], true);
        }

        $roots = Category::tree();
        $this->loadCounts($roots);

        $flat = $this->flattenForSelect($roots);

        return array_values(array_filter(
            $flat,
            fn (array $item) => ! in_array($item['id'], $excludeIds, true),
        ));
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    public function loadCounts(Collection $categories): void
    {
        foreach ($categories as $category) {
            if (! $category->relationLoaded('children')) {
                continue;
            }

            $category->loadCount(['products', 'children']);

            if ($category->children->isNotEmpty()) {
                $this->loadCounts($category->children);
            }
        }
    }

    public function validateParent(?Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($category && $parentId === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Kategori tidak bisa menjadi induk dirinya sendiri.',
            ]);
        }

        if ($category) {
            $descendantIds = $this->expandIds([$category->id], false);
            if (in_array($parentId, $descendantIds, true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Kategori induk tidak boleh sub-kategori dari kategori ini.',
                ]);
            }
        }

        if (! Category::whereKey($parentId)->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Kategori induk tidak ditemukan.',
            ]);
        }
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public function breadcrumbPath(Category $category): array
    {
        $all = Category::all()->keyBy('id');
        $chain = [];
        $current = $category;

        while ($current) {
            $chain[] = $current;
            $current = $current->parent_id ? $all->get($current->parent_id) : null;
        }

        return array_reverse(array_map(fn (Category $item) => [
            'label' => $item->name,
            'href' => '/products?category='.$item->slug,
        ], $chain));
    }

    /**
     * @return list<array{id: int, name: string, slug: string, depth: int}>
     */
    public function formOptions(): array
    {
        return $this->flattenForSelect(Category::tree());
    }
}
