<?php

namespace App\Services;

use App\Models\CmsPage;
use Illuminate\Validation\ValidationException;

class CmsLayoutService
{
    public const ALLOWED_BLOCK_TYPES = [
        'PageBanner',
        'Spacer',
        'TwoColumns',
        'Heading',
        'RichText',
        'Image',
        'Button',
    ];

    private const HTML_BLOCK_FIELDS = [
        'RichText' => ['html'],
        'TwoColumns' => ['leftHtml', 'rightHtml'],
    ];

    public function hasRenderableContent(?array $layout): bool
    {
        if (! is_array($layout)) {
            return false;
        }

        $content = $layout['content'] ?? null;

        return is_array($content) && $content !== [];
    }

    public function validateLayout(array $layout): void
    {
        if (! isset($layout['content']) || ! is_array($layout['content'])) {
            throw ValidationException::withMessages([
                'layout_json' => 'Layout harus memiliki array content.',
            ]);
        }

        foreach ($layout['content'] as $index => $block) {
            if (! is_array($block)) {
                throw ValidationException::withMessages([
                    'layout_json' => "Blok #{$index} tidak valid.",
                ]);
            }

            if (empty($block['type']) || ! is_string($block['type'])) {
                throw ValidationException::withMessages([
                    'layout_json' => "Blok #{$index} harus memiliki type.",
                ]);
            }

            if (! in_array($block['type'], self::ALLOWED_BLOCK_TYPES, true)) {
                throw ValidationException::withMessages([
                    'layout_json' => "Tipe blok '{$block['type']}' tidak dikenali.",
                ]);
            }

            if (! isset($block['props']) || ! is_array($block['props'])) {
                throw ValidationException::withMessages([
                    'layout_json' => "Blok #{$index} harus memiliki props.",
                ]);
            }
        }
    }

    public function sanitizeLayout(array $layout): array
    {
        if (! isset($layout['content']) || ! is_array($layout['content'])) {
            return $layout;
        }

        $layout['content'] = array_map(function (array $block): array {
            $type = $block['type'] ?? '';
            $fields = self::HTML_BLOCK_FIELDS[$type] ?? null;

            if ($fields === null) {
                return $block;
            }

            foreach ($fields as $field) {
                if (isset($block['props'][$field]) && is_string($block['props'][$field])) {
                    $block['props'][$field] = HtmlSanitizer::clean($block['props'][$field]) ?? '';
                }
            }

            return $block;
        }, $layout['content']);

        return $layout;
    }

    public function parseLayoutJson(string $layoutJson): array
    {
        $layout = json_decode($layoutJson, true);

        if (! is_array($layout)) {
            throw ValidationException::withMessages([
                'layout_json' => 'Format layout tidak valid.',
            ]);
        }

        $this->validateLayout($layout);

        return $this->sanitizeLayout($layout);
    }

    public function buildFromLegacy(CmsPage $page): array
    {
        $content = [];

        if ($page->banner_url) {
            $content[] = [
                'type' => 'PageBanner',
                'props' => [
                    'id' => 'banner-'.$page->id,
                    'title' => $page->title,
                    'subtitle' => '',
                    'imageUrl' => $page->banner_url,
                ],
            ];
        } else {
            $content[] = [
                'type' => 'Heading',
                'props' => [
                    'id' => 'heading-'.$page->id,
                    'text' => $page->title,
                    'level' => 'h1',
                    'align' => 'left',
                ],
            ];
        }

        if (! empty($page->content)) {
            $content[] = [
                'type' => 'RichText',
                'props' => [
                    'id' => 'content-'.$page->id,
                    'html' => HtmlSanitizer::clean($page->content) ?? '',
                ],
            ];
        }

        return [
            'root' => ['props' => ['showBreadcrumb' => 'yes', 'pageTitle' => $page->title]],
            'content' => $content,
        ];
    }

    public function needsLegacyMigration(CmsPage $page): bool
    {
        if ($this->hasRenderableContent($page->layout_json)) {
            return false;
        }

        return ! empty($page->content) || ! empty($page->banner_image);
    }

    public function migrateLegacyIfNeeded(CmsPage $page): CmsPage
    {
        if (! $this->needsLegacyMigration($page)) {
            return $page;
        }

        $page->update([
            'layout_json' => $this->buildFromLegacy($page),
            'layout_version' => 'puck-1',
        ]);

        return $page->fresh();
    }
}
