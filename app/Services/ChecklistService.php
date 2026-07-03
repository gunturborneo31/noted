<?php

namespace App\Services;

class ChecklistService
{
    /**
     * Parse markdown checklist items from content.
     *
     * Returns:
     *   [
     *     'total'     => int,
     *     'completed' => int,
     *     'percent'   => int (0-100),
     *     'items'     => [['text' => string, 'checked' => bool], ...],
     *   ]
     */
    public function parse(string $content): array
    {
        preg_match_all('/- \[(x| )\] (.+)/i', $content, $matches, PREG_SET_ORDER);

        $items = array_map(fn($m) => [
            'text'    => trim($m[2]),
            'checked' => strtolower($m[1]) === 'x',
        ], $matches);

        $total     = count($items);
        $completed = count(array_filter($items, fn($i) => $i['checked']));
        $percent   = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return compact('total', 'completed', 'percent', 'items');
    }

    /**
     * Toggle a checklist item at the given index in raw content.
     */
    public function toggle(string $content, int $index): string
    {
        $count = 0;

        return preg_replace_callback('/- \[(x| )\] (.+)/i', function ($match) use ($index, &$count) {
            if ($count === $index) {
                $count++;
                $newMark = strtolower($match[1]) === 'x' ? ' ' : 'x';
                return "- [{$newMark}] {$match[2]}";
            }
            $count++;
            return $match[0];
        }, $content);
    }
}
