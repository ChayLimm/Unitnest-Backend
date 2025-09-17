<?php

namespace App\Http\Controllers\bot;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class BotController extends BaseController
{
    // const chaylim = env('CHAYLIM');
    // const narong = env('NARONG');
    // const vanda = env('VANDA');
    // const allowedUsers = [self::chaylim, self::narong, self::vanda];
    public static function getAllowedUsers()
    {
        return [
            env('CHAYLIM'),
            env('NARONG'), 
            env('VANDA')
        ];
    }
    public function handleAccess()
    {
        $update = json_decode(file_get_contents("php://input"), true);
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot$token/sendMessage";

        $chatId = null;
        $type = null;

        if (isset($update["message"])) {
            $userId = $update["message"]["from"]["id"];
            $chatId = $update["message"]["chat"]["id"];
            $type = $update["message"]["chat"]["type"];
        } else if (isset($update["callback_query"])) {
            $userId = $update["callback_query"]["from"]["id"]; // User ID for access control
            $chatId = $update["callback_query"]["message"]["chat"]["id"]; // Chat ID for sending messages
            $type = $update["callback_query"]["message"]["chat"]["type"];
        }

        if ($chatId === null || $type === null) {
            return;
        }

        if ($type == "group") {
            log::info('Group chat access granted: ' . $chatId);
            if (!in_array($userId, self::getAllowedUsers())) {
                $postData = [
                    "chat_id" => $chatId,
                    "text" => "You do not have access to this bot.",
                    "parse_mode" => "HTML"
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
                return;
            } else {
                // It is a private chat but the user is allowed
                $this->handle();
                return true;
            }
        } else {
            $postData = [
                "chat_id" => $chatId,
                "text" => "You do not have access to this bot.",
                "parse_mode" => "HTML"
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
            return;
        }

    }
    public function handle()
    {
        Log::info('Telegram bot webhook triggered');
        $token = env('TELEGRAM_BOT_TOKEN');
        $update = json_decode(file_get_contents("php://input"), true);
        Log::info('Update received: ' . json_encode($update));

        $url = "https://api.telegram.org/bot$token/sendMessage";

        if (isset($update["message"])) {
            $chatId = $update["message"]["chat"]["id"];
            $text = $update["message"]["text"];

            if ($text === "/start") {
                $keyboard = [
                    "inline_keyboard" => [
                        [
                            ["text" => "Deploy Dev", "callback_data" => "deploy_dev"]
                        ]
                    ]
                ];

                $postData = [
                    "chat_id" => $chatId,
                    "text" => "Hello kon khmer:",
                    "reply_markup" => json_encode($keyboard)
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        // Handle button callback
        if (isset($update["callback_query"])) {
            Log::info('handle button click');

            $chatId = $update["callback_query"]["message"]["chat"]["id"];
            $data = $update["callback_query"]["data"];

            if ($data === "deploy_dev") {
                $postData = [
                    "chat_id" => $chatId,
                    "text" => "🚀 Deploying development server..."
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);

                $output = shell_exec("git pull origin develop");
                $output .= "\n" . shell_exec("php artisan migrate");

                $postData = [
                    "chat_id" => $chatId,
                    "text" => "Deploy Result:\n<pre>" . substr($output, 0, 4000) . "</pre>",
                    "parse_mode" => "HTML"
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
}