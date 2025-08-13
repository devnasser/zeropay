<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoiceAssistantService;
use Illuminate\Http\Request;

class VoiceController extends Controller
{
    protected $voiceService;

    public function __construct(VoiceAssistantService $voiceService)
    {
        $this->voiceService = $voiceService;
    }

    public function process(Request $request)
    {
        $request->validate([
            'transcript' => 'required|string',
            'audio' => 'nullable|file|mimes:wav,mp3,ogg|max:5120', // 5MB max
            'context' => 'nullable|array'
        ]);

        $user = $request->user();
        $result = $this->voiceService->processCommand(
            $request->transcript,
            $user
        );

        // Log voice interaction for analytics
        if ($user) {
            activity()
                ->performedOn($user)
                ->withProperties([
                    'transcript' => $request->transcript,
                    'command_type' => $result['type'] ?? 'unknown',
                    'success' => !isset($result['error'])
                ])
                ->log('voice_command');
        }

        return response()->json($result);
    }

    public function uploadAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:wav,mp3,ogg,webm|max:10240' // 10MB
        ]);

        // Here you would integrate with a speech-to-text service
        // For now, return a placeholder response
        
        return response()->json([
            'transcript' => 'هذا نص تجريبي للأمر الصوتي',
            'confidence' => 0.95,
            'language' => 'ar'
        ]);
    }

    public function textToSpeech(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'language' => 'nullable|string|in:ar,en',
            'voice' => 'nullable|string'
        ]);

        // Here you would integrate with a TTS service
        // For now, return the text for client-side TTS
        
        return response()->json([
            'text' => $request->text,
            'language' => $request->language ?? 'ar',
            'ssml' => $this->generateSSML($request->text, $request->language ?? 'ar')
        ]);
    }

    protected function generateSSML($text, $language)
    {
        // Generate SSML for better speech synthesis
        $ssml = '<speak>';
        
        if ($language === 'ar') {
            $ssml .= '<prosody rate="medium" pitch="medium">';
            $ssml .= str_replace('ريال', '<say-as interpret-as="currency">SAR</say-as>', $text);
            $ssml .= '</prosody>';
        } else {
            $ssml .= $text;
        }
        
        $ssml .= '</speak>';
        
        return $ssml;
    }
}