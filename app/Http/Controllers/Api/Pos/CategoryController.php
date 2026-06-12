<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\Category;
use App\Support\Api\PosApiResponse;

class CategoryController extends Controller
{
    public function index()
    {
        $roots = Category::tree();

        return PosApiResponse::success(
            $roots->map(fn (Category $category) => $this->serializeNode($category))->values()->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNode(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'imageUrl' => $category->image_url,
            'children' => $category->children
                ->map(fn (Category $child) => $this->serializeNode($child))
                ->values()
                ->all(),
        ];
    }
}
