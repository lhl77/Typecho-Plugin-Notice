<?php

namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/Telegram.php';

use Typecho;
use Utils;
use Widget;

class TelegramWebhookAction extends Typecho\Widget implements Widget\ActionInterface
{
    private function logLine(string $msg, array $ctx = []): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
        if (!empty($ctx)) {
            $line .= ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    public function action()
    {
        $raw = file_get_contents('php://input') ?: '';
        $this->logLine('incoming', ['len' => strlen($raw), 'raw_head' => substr($raw, 0, 300)]);

        $update = json_decode($raw, true);
        if (!$update) {
            http_response_code(400);
            $this->logLine('invalid_json');
            echo 'Invalid JSON';
            return;
        }

        try {
            $pluginOptions = Utils\Helper::options()->plugin('Notice');
            $token = $pluginOptions->tgToken ?? '';
            $this->logLine('options', ['hasToken' => !empty($token)]);

            if (empty($token)) {
                http_response_code(403);
                echo 'Invalid token';
                return;
            }

            $webhook = new TelegramWebhook($token);

            if (isset($update['callback_query'])) {
                $cb = $update['callback_query'];
                $this->logLine('callback_query', [
                    'id' => $cb['id'] ?? null,
                    'from' => $cb['from']['id'] ?? null,
                    'data' => $cb['data'] ?? null,
                    'message_id' => $cb['message']['message_id'] ?? null,
                ]);

                $webhook->handleCallback($cb);

                http_response_code(200);
                echo 'OK';
                return;
            }

            $this->logLine('no_callback_query', ['keys' => array_keys($update)]);
            http_response_code(200);
            echo 'OK';
        } catch (\Throwable $e) {
            $this->logLine('exception', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            http_response_code(200);
            echo 'OK';
        }
    }
}