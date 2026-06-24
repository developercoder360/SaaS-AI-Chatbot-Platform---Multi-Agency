<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\UsageLog;
use Illuminate\Support\Facades\Http;

class ChatbotService
{
    private string $ragUrl;

    public function __construct()
    {
        $this->ragUrl = config('services.rag.url', 'http://localhost:8001');
    }

    public function chat(string $conversationId, string $userMessage): array
    {
        $tenant = tenant(); // resolved from middleware
        $conversation = Conversation::findOrFail($conversationId);

        $history = ChatMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get()
            ->chunk(2)
            ->map(fn($pair) => [
                'user'      => $pair->firstWhere('role', 'user')?->content ?? '',
                'assistant' => $pair->firstWhere('role', 'assistant')?->content ?? '',
            ])
            ->toArray();

        ChatMessage::create([
            'tenant_id'       => $tenant->id,
            'conversation_id' => $conversationId,
            'role'            => 'user',
            'content'         => $userMessage,
        ]);

        $response = Http::post("{$this->ragUrl}/chat", [
            'tenant_id'       => $tenant->id,
            'tenant_slug'     => $tenant->slug,
            'system_prompt'   => $tenant->system_prompt,  // per-tenant prompt
            'message'         => $userMessage,
            'conversation_id' => $conversationId,
            'chat_history'    => $history,
        ])->json();

        $answer = $response['answer'] ?? 'Sorry, something went wrong.';

        ChatMessage::create([
            'tenant_id'       => $tenant->id,
            'conversation_id' => $conversationId,
            'role'            => 'assistant',
            'content'         => $answer,
        ]);

        // Log usage for billing
        UsageLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'date' => today()],
            [],
        )->increment('messages_count');

        $needsHuman = $this->detectEscalation($userMessage, $tenant);
        if ($needsHuman) {
            $conversation->update(['needs_human' => true]);
        }

        return ['answer' => $answer, 'needs_human' => $needsHuman];
    }

    private function detectEscalation(string $msg, $tenant): bool
    {
        // Merge per-tenant keywords from DB with global defaults
        $keywords = array_merge(
            $tenant->escalation_keywords ?? [],
            ['speak to human', 'real agent', 'refund', 'complaint', 'fraud', 'legal']
        );

        foreach ($keywords as $keyword) {
            if (str_contains(strtolower($msg), strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
}
