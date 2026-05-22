<?php

namespace App\Services;

class CommunicationService
{
    /**
     * Send a message via channel.
     * Channel: email|sms|whatsapp|push
     * Returns array with status and response details.
     */
    public function send(string $channel, $to, string $message, array $meta = []): array
    {
        // This is a pluggable adapter point. Integrate with real providers.
        // For now, we simulate and return a standardized response.
        switch ($channel) {
            case 'email':
                // dispatch mail
                $status = 'sent';
                $response = ['message_id' => uniqid('mail_')];
                break;
            case 'whatsapp':
            case 'sms':
                $status = 'sent';
                $response = ['external_id' => uniqid('sms_')];
                break;
            default:
                $status = 'queued';
                $response = [];
        }

        return [
            'status' => $status,
            'response' => $response,
        ];
    }
}
