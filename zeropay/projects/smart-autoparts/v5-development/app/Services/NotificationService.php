<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Mail\OrderConfirmed;
use App\Mail\OrderShipped;
use App\Mail\PasswordReset;

class NotificationService
{
    protected $templates = [
        'order_confirmed' => [
            'email' => OrderConfirmed::class,
            'sms' => 'تم تأكيد طلبك رقم :order_number بنجاح. المبلغ الإجمالي: :total ريال',
            'push' => 'تم تأكيد طلبك بنجاح ✅'
        ],
        'order_shipped' => [
            'email' => OrderShipped::class,
            'sms' => 'تم شحن طلبك رقم :order_number. رقم التتبع: :tracking_number',
            'push' => 'طلبك في الطريق إليك 🚚'
        ],
        'order_delivered' => [
            'sms' => 'تم تسليم طلبك رقم :order_number بنجاح. شكراً لتسوقك معنا!',
            'push' => 'تم تسليم طلبك بنجاح 📦'
        ],
        'order_failed' => [
            'sms' => 'عذراً، فشل معالجة طلبك رقم :order_number. السبب: :reason',
            'push' => 'فشل معالجة طلبك ❌'
        ],
        'new_order' => [
            'sms' => 'طلب جديد من :customer_name. المبلغ: :total ريال',
            'push' => 'لديك طلب جديد! 🛍️'
        ],
        'password_reset' => [
            'email' => PasswordReset::class,
            'sms' => 'رمز إعادة تعيين كلمة المرور: :code'
        ],
        'otp' => [
            'sms' => 'رمز التحقق الخاص بك: :code',
            'push' => 'رمز التحقق: :code'
        ]
    ];
    
    /**
     * Send notification to user
     */
    public function send($recipient, string $type, array $data = [], array $channels = null)
    {
        if (!isset($this->templates[$type])) {
            Log::warning('Unknown notification type', ['type' => $type]);
            return;
        }
        
        $template = $this->templates[$type];
        $channels = $channels ?? $this->getDefaultChannels($recipient, $type);
        
        foreach ($channels as $channel) {
            if (!isset($template[$channel])) {
                continue;
            }
            
            try {
                switch ($channel) {
                    case 'email':
                        $this->sendEmail($recipient, $template['email'], $data);
                        break;
                    case 'sms':
                        $this->sendSms($recipient, $template['sms'], $data);
                        break;
                    case 'push':
                        $this->sendPush($recipient, $template['push'], $data);
                        break;
                    case 'database':
                        $this->saveToDatabase($recipient, $type, $data);
                        break;
                }
            } catch (\Exception $e) {
                Log::error('Notification failed', [
                    'channel' => $channel,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Send email notification
     */
    protected function sendEmail($recipient, $mailableClass, array $data)
    {
        $email = is_string($recipient) ? $recipient : $recipient->email;
        
        if (!$email) {
            return;
        }
        
        Mail::to($email)->queue(new $mailableClass($data));
    }
    
    /**
     * Send SMS notification
     */
    protected function sendSms($recipient, string $template, array $data)
    {
        $phone = is_string($recipient) ? $recipient : $recipient->phone;
        
        if (!$phone) {
            return;
        }
        
        $message = $this->parseTemplate($template, $data);
        
        // Using Twilio or local SMS gateway
        if (config('services.sms.provider') === 'twilio') {
            $this->sendTwilioSms($phone, $message);
        } else {
            $this->sendLocalSms($phone, $message);
        }
    }
    
    /**
     * Send push notification
     */
    protected function sendPush($recipient, string $message, array $data)
    {
        if (is_string($recipient)) {
            return; // Can't send push to string
        }
        
        $tokens = $recipient->pushTokens()->active()->pluck('token');
        
        if ($tokens->isEmpty()) {
            return;
        }
        
        $payload = [
            'title' => config('app.name'),
            'body' => $this->parseTemplate($message, $data),
            'data' => $data,
            'badge' => $recipient->unread_notifications_count ?? 0
        ];
        
        // Send via FCM
        foreach ($tokens as $token) {
            $this->sendFcmNotification($token, $payload);
        }
    }
    
    /**
     * Save notification to database
     */
    protected function saveToDatabase($recipient, string $type, array $data)
    {
        if (is_string($recipient)) {
            return;
        }
        
        $recipient->notifications()->create([
            'type' => $type,
            'data' => $data,
            'read_at' => null
        ]);
    }
    
    /**
     * Send Twilio SMS
     */
    protected function sendTwilioSms(string $phone, string $message)
    {
        $client = new \Twilio\Rest\Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        
        $client->messages->create($phone, [
            'from' => config('services.twilio.phone_number'),
            'body' => $message
        ]);
    }
    
    /**
     * Send local SMS via gateway
     */
    protected function sendLocalSms(string $phone, string $message)
    {
        Http::post(config('services.sms.gateway_url'), [
            'username' => config('services.sms.username'),
            'password' => config('services.sms.password'),
            'sender' => config('services.sms.sender_name'),
            'numbers' => $phone,
            'message' => $message,
            'unicode' => 'u'
        ]);
    }
    
    /**
     * Send FCM notification
     */
    protected function sendFcmNotification(string $token, array $payload)
    {
        Http::withHeaders([
            'Authorization' => 'key=' . config('services.fcm.server_key'),
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'sound' => 'default',
                'badge' => $payload['badge']
            ],
            'data' => $payload['data'],
            'priority' => 'high'
        ]);
    }
    
    /**
     * Get default channels for notification
     */
    protected function getDefaultChannels($recipient, string $type): array
    {
        $channels = ['database'];
        
        if (is_string($recipient)) {
            return ['email']; // Only email for string recipients
        }
        
        // Check user preferences
        if ($recipient->email_notifications ?? true) {
            $channels[] = 'email';
        }
        
        if ($recipient->sms_notifications ?? false) {
            $channels[] = 'sms';
        }
        
        if ($recipient->push_notifications ?? true) {
            $channels[] = 'push';
        }
        
        // Override for critical notifications
        if (in_array($type, ['order_failed', 'password_reset', 'otp'])) {
            $channels = array_unique(array_merge($channels, ['email', 'sms']));
        }
        
        return $channels;
    }
    
    /**
     * Parse template with data
     */
    protected function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace(':' . $key, $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Send bulk notifications
     */
    public function sendBulk(array $recipients, string $type, array $data = [], array $channels = null)
    {
        foreach ($recipients as $recipient) {
            dispatch(function() use ($recipient, $type, $data, $channels) {
                $this->send($recipient, $type, $data, $channels);
            })->afterResponse();
        }
    }
}