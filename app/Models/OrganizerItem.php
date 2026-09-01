<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrganizerItem extends Model
{
    public const SECTIONS = [
        'tasks' => 'Задачи',
        'drafts' => 'Черновики',
        'affiliates' => 'Партнёрки',
        'programs' => 'Программы',
    ];

    protected $fillable = [
        'section',
        'title',
        'content',
        'category',
        'source_key',
    ];

    public function scopeForSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public static function sectionTitle(string $section): string
    {
        return self::SECTIONS[$section] ?? 'Записи';
    }
}
