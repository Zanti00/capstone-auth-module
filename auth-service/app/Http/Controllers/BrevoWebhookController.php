<?php

namespace App\Http\Controllers;

use App\Services\Email\EmailWebhookService;
use Illuminate\Http\Request;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, EmailWebhookService $emailWebhookService)
    {
        $emailWebhookService->handleBrevoWebhook(
            $request->getContent(),
            $request->headers->all(),
            $request->input()
        );

        return response()->json(['message' => 'Webhook accepted.']);
    }
}
