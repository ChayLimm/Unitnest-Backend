<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OllamaService;

class AgentController extends Controller
{
    protected $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    public function generateResponse(Request $request)
    {
        $prompt = $request->input('prompt');
        $model = $request->input('model', null); 

        if (!$prompt) {
            return response()->json(['error' => 'Prompt is required'], 400);
        }

        $response = $this->ollamaService->generate($prompt, $model);

        return response()->json($response);
    }


}
