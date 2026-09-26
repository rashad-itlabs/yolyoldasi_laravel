<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug', 'title', 'lead', 'body', 'meta_description',
        'content_updated_at', 'is_published',
    ];

    protected $casts = [
        'content_updated_at' => 'date',
        'is_published'       => 'boolean',
    ];

    /** Admindən idarə olunan səhifələr — slug sabitdir, yenisi əlavə edilmir. */
    public const SLUGS = ['terms', 'privacy', 'about'];

    public const LABELS = [
        'terms'   => 'İstifadə qaydaları',
        'privacy' => 'Məxfilik siyasəti',
        'about'   => 'Haqqımızda',
    ];

    public const ROUTES = [
        'terms'   => 'terms-of-use',
        'privacy' => 'privacy-policy',
        'about'   => 'about',
    ];

    public function label(): string
    {
        return self::LABELS[$this->slug] ?? $this->slug;
    }

    public function publicUrl(): ?string
    {
        $route = self::ROUTES[$this->slug] ?? null;

        return $route ? route($route) : null;
    }

    /**
     * Mündəricat mətndəki `<h2 id="...">` başlıqlarından qurulur.
     *
     * Beləliklə admin bölmə əlavə edəndə siyahını ayrıca yeniləmək lazım
     * gəlmir — id verilməyən başlıq sadəcə siyahıya düşmür.
     *
     * @return array<int, array{id: string, title: string}>
     */
    public function tableOfContents(): array
    {
        if (! $this->body) {
            return [];
        }

        preg_match_all(
            '/<h2\b[^>]*\bid=["\']([^"\']+)["\'][^>]*>(.*?)<\/h2>/is',
            $this->body,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(fn ($m) => [
            'id'    => $m[1],
            'title' => trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8')),
        ], $matches);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
