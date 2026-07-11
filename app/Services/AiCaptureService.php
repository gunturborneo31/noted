<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiCaptureService
{
    public function analyze(string $textInput = '', ?UploadedFile $audioFile = null, string $mode = 'mixed'): array
    {
        $transcript = '';

        if ($audioFile) {
            $transcript = $this->transcribeAudio($audioFile);
        }

        $combinedText = trim(implode("\n\n", array_filter([
            $transcript !== '' ? "Transkrip audio:\n{$transcript}" : '',
            trim($textInput) !== '' ? "Catatan tambahan user:\n".trim($textInput) : '',
        ])));

        if ($combinedText === '') {
            throw new RuntimeException('Tidak ada teks atau audio yang bisa diproses.');
        }

        $analysis = $this->analyzeText($combinedText, $mode);
        $analysis['transcript'] = $transcript !== '' ? $transcript : trim($textInput);

        return $analysis;
    }

    private function transcribeAudio(UploadedFile $audioFile): string
    {
        $response = $this->client()
            ->attach('file', file_get_contents($audioFile->getRealPath()), $audioFile->getClientOriginalName())
            ->post('/audio/transcriptions', [
                'model' => config('services.ai.transcription_model', 'gpt-4o-mini-transcribe'),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal melakukan transkripsi audio. Periksa API key dan model transkripsi.');
        }

        return trim((string) $response->json('text', ''));
    }

    private function analyzeText(string $text, string $mode): array
    {
        $modeInstruction = match ($mode) {
            'tasks' => 'Fokus utama: ekstrak task, client, dan project seakurat mungkin. Data account dan note hanya jika benar-benar jelas.',
            'accounts' => 'Fokus utama: ekstrak akun, platform, dan login type. Task hanya jika benar-benar jelas.',
            'detail_note' => 'Fokus utama: susun catatan detail yang rapi dan ringkasan. Task/account hanya jika benar-benar jelas.',
            default => 'Gunakan mode campuran: ekstrak task, account, dan note bila tersedia.',
        };

        $response = $this->client()->post('/chat/completions', [
            'model' => config('services.ai.chat_model', 'gpt-4.1-mini'),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'Anda adalah AI operasional untuk aplikasi produktivitas.',
                        'Tugas Anda adalah mengekstrak data operasional dari input user.',
                        'Kembalikan JSON valid tanpa markdown.',
                        'Gunakan schema berikut:',
                        '{',
                        '  "summary": string,',
                        '  "classification": "tasks" | "accounts" | "detail_note" | "mixed",',
                        '  "client_name": string,',
                        '  "project_name": string,',
                        '  "tasks": [{"name": string, "detail": string, "status": "todo" | "in_progress" | "done", "due_date": "YYYY-MM-DD" | ""}],',
                        '  "accounts": [{"platform": string, "login_type": "credentials" | "google" | "email", "username": string, "password": string, "detail": string}],',
                        '  "detail_note_title": string,',
                        '  "detail_note_body": string',
                        '}',
                        'Jika tidak ada field tertentu, kirim string kosong atau array kosong.',
                        'Jangan mengarang password jika tidak disebut. Jangan mengarang due_date jika tidak ada petunjuk.',
                        $modeInstruction,
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ],
            'temperature' => 0.2,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal meminta analisis AI. Periksa API key, base URL, dan model chat.');
        }

        $content = (string) $response->json('choices.0.message.content', '');
        $decoded = json_decode($this->extractJson($content), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Respons AI tidak bisa dibaca sebagai JSON.');
        }

        return [
            'summary' => (string) ($decoded['summary'] ?? ''),
            'classification' => (string) ($decoded['classification'] ?? 'mixed'),
            'client_name' => (string) ($decoded['client_name'] ?? ''),
            'project_name' => (string) ($decoded['project_name'] ?? ''),
            'tasks' => is_array($decoded['tasks'] ?? null) ? $decoded['tasks'] : [],
            'accounts' => is_array($decoded['accounts'] ?? null) ? $decoded['accounts'] : [],
            'detail_note_title' => (string) ($decoded['detail_note_title'] ?? ''),
            'detail_note_body' => (string) ($decoded['detail_note_body'] ?? ''),
        ];
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content) ?? $content;
        }

        return trim($content);
    }

    private function client(): PendingRequest
    {
        $apiKey = config('services.ai.api_key');
        $baseUrl = rtrim((string) config('services.ai.base_url', 'https://api.openai.com/v1'), '/');

        if (!$apiKey) {
            throw new RuntimeException('AI belum dikonfigurasi. Isi AI_API_KEY di file .env.');
        }

        return Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.ai.timeout', 120));
    }
}
