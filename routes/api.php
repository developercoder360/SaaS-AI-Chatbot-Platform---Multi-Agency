<?php

use App\Http\Controllers\OnboardingController;
use App\Models\Conversation;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->group(function () {
    Route::post('/chatbot/conversation/start', function (Request $request) {
        $conversation = Conversation::create([
            'page_url' => $request->input('page_url'),
        ]);

        return response()->json(['conversation_id' => $conversation->id]);
    });
    Route::post('/chatbot/message', function (Request $request, ChatbotService $service) {
        $validated = $request->validate([
            'conversation_id' => 'required|uuid',
            'message' => 'required|string',
        ]);
        $response = $service->chat($validated['conversation_id'], $validated['message']);

        return response()->json($response);
    });
});
Route::post('/onboarding/register', [OnboardingController::class, 'register']);
Route::post('/onboarding/ingest', [OnboardingController::class, 'triggerIngestion'])->middleware(['api', 'tenant']);
