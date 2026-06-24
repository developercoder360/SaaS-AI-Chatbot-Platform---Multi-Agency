<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IngestKnowledgeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Tenant $tenant,
        public array $urls
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $docs = [];
        
        foreach ($this->urls as $url) {
            $docs[] = KnowledgeDocument::create([
                'tenant_id' => $this->tenant->id,
                'source_url' => $url,
                'type' => 'url',
                'status' => 'processing',
            ]);
        }

        try {
            $ragUrl = config('services.rag.url', 'http://127.0.0.1:8001');

            $response = Http::timeout(300)->post("{$ragUrl}/ingest", [
                'tenant_id' => $this->tenant->id,
                'tenant_slug' => $this->tenant->slug,
                'urls' => $this->urls,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $chunks = $data['chunks'] ?? 0;
                
                foreach ($docs as $doc) {
                    $doc->update([
                        'status' => 'done',
                        'chunk_count' => $chunks, // Ideally, RAG service should return chunks per URL, but we simplify here
                        'last_ingested_at' => now(),
                    ]);
                }
            } else {
                Log::error("RAG Service Ingest Error for Tenant {$this->tenant->id}: " . $response->body());
                foreach ($docs as $doc) {
                    $doc->update(['status' => 'failed']);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to ingest knowledge for Tenant {$this->tenant->id}: " . $e->getMessage());
            foreach ($docs as $doc) {
                $doc->update(['status' => 'failed']);
            }
        }
    }
}
