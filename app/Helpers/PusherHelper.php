<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class PusherHelper
{
    public static function trigger(string $channel, string $event, array $data = [])
    {
        $appId = "1388511";
        $key = "0b758fd17aaeea982810";
        $secret = "87ff378a1ae4e6614f6a";
        $cluster = "ap2";

        $payload = [
            "name" => $event,
            "channel" => $channel,
            "data" => json_encode($data),
        ];

        $body = json_encode($payload);

        // Create body_md5
        $bodyMd5 = md5($body);

        // Auth params
        $params = [
            "body_md5" => $bodyMd5,
            "auth_key" => $key,
            "auth_timestamp" => time(),
            "auth_version" => "1.0",
        ];

        ksort($params);

        $queryString = http_build_query($params);
        $stringToSign = "POST\n/apps/{$appId}/events\n{$queryString}";

        // Signature
        $signature = hash_hmac('sha256', $stringToSign, $secret);

        // Final URL
        $url = "https://api-{$cluster}.pusher.com/apps/{$appId}/events?{$queryString}&auth_signature={$signature}";

        // Send request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        return $response->json();
    }
}
