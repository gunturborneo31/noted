<?php

namespace App\Services;

class NoteRenderer
{
    /**
     * Convert note body (markdown-like) to HTML for display.
     * Handles: bold, italic, code, links, images, video embeds, checklists, hashtags.
     */
    public static function render(string $content): string
    {
        if (empty(trim($content))) {
            return '<p class="text-gray-400 italic">Empty note.</p>';
        }

        $lines   = explode("\n", $content);
        $output  = [];
        $inList  = false;
        $checkIdx = 0;

        foreach ($lines as $line) {
            $trimmed = rtrim($line);

            // Checklist items
            if (preg_match('/^- \[(x| )\] (.+)/i', $trimmed, $m)) {
                $checked = strtolower($m[1]) === 'x';
                $text    = htmlspecialchars($m[2]);
                $mark    = $checked
                    ? '<span class="inline-flex items-center justify-center w-5 h-5 border-2 border-black bg-lime-400 mr-2 shrink-0">✓</span>'
                    : '<span class="inline-flex items-center justify-center w-5 h-5 border-2 border-black bg-white mr-2 shrink-0"></span>';
                $lineClass = $checked ? 'line-through text-gray-400' : '';
                if (!$inList) { $output[] = '<ul class="space-y-2 my-2">'; $inList = true; }
                $output[] = "<li class=\"flex items-start\">{$mark}<span class=\"{$lineClass}\">{$text}</span></li>";
                $checkIdx++;
                continue;
            }

            if ($inList) { $output[] = '</ul>'; $inList = false; }

            // Headings
            if (preg_match('/^(#{1,3}) (.+)/', $trimmed, $m)) {
                $level = strlen($m[1]);
                $sizes = ['text-2xl', 'text-xl', 'text-lg'];
                $text  = self::inlineFormat($m[2]);
                $output[] = "<h{$level} class=\"font-black {$sizes[$level-1]} mt-4 mb-1\">{$text}</h{$level}>";
                continue;
            }

            // Horizontal rule
            if (preg_match('/^---+$/', $trimmed)) {
                $output[] = '<hr class="border-t-2 border-black my-4" />';
                continue;
            }

            // Empty line
            if ($trimmed === '') {
                $output[] = '<br />';
                continue;
            }

            // Regular paragraph with inline formatting
            $output[] = '<p>' . self::inlineFormat($trimmed) . '</p>';
        }

        if ($inList) { $output[] = '</ul>'; }

        return implode("\n", $output);
    }

    private static function inlineFormat(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Images: ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/',
            '<img src="$2" alt="$1" class="max-w-full border-2 border-black my-2" />', $text);

        // YouTube embed shorthand: https://youtube.com/embed/ID
        $text = preg_replace('/(https?:\/\/(?:www\.)?youtube\.com\/embed\/[\w\-]+)/',
            '<div class="my-2"><iframe src="$1" class="w-full border-4 border-black" height="315" allowfullscreen></iframe></div>', $text);

        // Links: [text](url)
        $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/',
            '<a href="$2" target="_blank" class="font-bold underline text-blue-700">$1</a>', $text);

        // Auto-link bare URLs (not already in anchor tags)
        $text = preg_replace('/(?<![">])(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" class="font-bold underline text-blue-700 break-all">$1</a>', $text);

        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Italic
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        // Inline code
        $text = preg_replace('/`([^`]+)`/',
            '<code class="bg-lime-100 border border-black px-1 font-mono text-sm">$1</code>', $text);

        // Hashtags
        $text = preg_replace('/#([a-zA-Z0-9_\-]+)/',
            '<a href="' . url('/hashtags') . '?tag=$1" class="font-bold text-lime-700 hover:underline">#$1</a>', $text);

        return $text;
    }
}
