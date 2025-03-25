<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Recipe; 

class GeminiController extends Controller
{
    
    public function chat(Request $request)
{
    $userMessage = $request->input('message');
    $apiKey = env('GEMINI_API_KEY');
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

    // System instruction to ensure AI focuses on recipes
    $systemInstruction = "You are an AI chef. When asked for a recipe, provide a structured response 
                          with 'Title', 'Ingredients', and 'Instructions'. Do not include unnecessary details.";

    $fullPrompt = $systemInstruction . "\nUser: " . $userMessage;

    $response = Http::post($url, [
        'contents' => [
            ['parts' => [['text' => $fullPrompt]]]
        ]
    ]);

    $data = $response->json();
    $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? "I couldn't find a recipe for that.";

    return response()->json(['recipe' => $aiResponse]);
}
public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'title' => 'required|string',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
        ]);

        // Save recipe
        Recipe::create([
            'title' => $request->title,
            'ingredients' => $request->ingredients,
            'instructions' => $request->instructions,
        ]);

        return response()->json(['message' => 'Recipe saved successfully!']);
    }
public function index()
{
    $recipes = Recipe::latest()->get(); // Get recipes ordered by latest
    return response()->json($recipes);
}
public function destroy($id)
{
    $recipe = Recipe::find($id);
    
    if ($recipe) {
        $recipe->delete();
        return response()->json(['message' => 'Recipe deleted successfully']);
    } else {
        return response()->json(['message' => 'Recipe not found'], 404);
    }
}
public function update(Request $request, $id)
{
    $recipe = Recipe::find($id);

    if (!$recipe) {
        return response()->json(['message' => 'Recipe not found'], 404);
    }

    $recipe->update([
        'title' => $request->title,
        'ingredients' => $request->ingredients,
        'instructions' => $request->instructions
    ]);

    return response()->json(['message' => 'Recipe updated successfully']);
}
    
}
