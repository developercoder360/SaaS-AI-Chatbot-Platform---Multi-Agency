<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;

class CreateStripeCustomerJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Tenant $tenant)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $apiKey = config('services.stripe.secret');
            
            if (!$apiKey) {
                Log::warning('Stripe API key not set. Skipping customer creation for Tenant: ' . $this->tenant->id);
                return;
            }

            Stripe::setApiKey($apiKey);

            $adminUser = $this->tenant->users()->where('role', 'owner')->first();
            $email = $adminUser ? $adminUser->email : null;

            $customer = Customer::create([
                'name' => $this->tenant->name,
                'email' => $email,
                'metadata' => [
                    'tenant_id' => $this->tenant->id,
                    'slug' => $this->tenant->slug,
                    'plan' => $this->tenant->plan,
                ],
            ]);

            $this->tenant->update([
                'stripe_customer_id' => $customer->id,
            ]);

            Log::info("Created Stripe Customer {$customer->id} for Tenant {$this->tenant->id}");

            // Note: Subscription creation is often better done after a card is added, 
            // but we can create a trial subscription if a default price ID is configured.

        } catch (\Exception $e) {
            Log::error('Failed to create Stripe Customer for Tenant: ' . $this->tenant->id . '. Error: ' . $e->getMessage());
        }
    }
}
