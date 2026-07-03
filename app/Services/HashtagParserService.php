<?php

namespace App\Services;

use App\Models\Hashtag;
use Illuminate\Database\Eloquent\Model;

class HashtagParserService
{
    /**
     * Extract hashtags from content and sync the polymorphic relationship.
     */
    public function sync(Model $model, string $content): void
    {
        $tags = $this->extract($content);

        $hashtagIds = collect($tags)->map(function (string $tag) {
            return Hashtag::firstOrCreate(['tag_name' => $tag])->id;
        })->toArray();

        $model->hashtags()->sync($hashtagIds);
    }

    /**
     * Extract unique lowercase hashtags from content string.
     * Matches words prefixed with '#' (e.g., #bug, #mahulu).
     *
     * @return string[]
     */
    public function extract(string $content): array
    {
        preg_match_all('/#([a-zA-Z0-9_\-]+)/', $content, $matches);

        return array_unique(
            array_map('strtolower', $matches[1] ?? [])
        );
    }
}
