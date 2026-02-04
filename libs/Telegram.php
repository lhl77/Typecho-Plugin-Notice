<?php

namespace TypechoPlugin\Notice\libs;

use Typecho;
use Utils;
use Widget;

class TelegramWebhook
{
    private $token;

    public function __construct($token = null)
    {
        $this->token = $token ?? Utils\Helper::options()->plugin('Notice')->tgToken;
    }

    /**
     * 调用 Telegram API
     */
    private function callApi($method, $param = [])
    {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }

    /**
     * 调用 Telegram API (JSON 方式)
     */
    private function callApiJson($method, $param = [])
    {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }

    /**
     * 编辑消息（更新按钮状态）
     */
    private function editMessage($chatId, $messageId, $text, $replyMarkup = null)
    {
        $param = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $param['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->callApi('editMessageText', $param);
    }

    /**
     * 回复回调查询
     * 改为 protected：允许本类内部/子类调用（内部依然正常）
     */
    protected function answerCallback($callbackId, $text, $showAlert = false)
    {
        $this->callApi('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert ? 'true' : 'false'
        ]);
    }

    /**
     * 生成“查看评论”URL：文章永久链接 + #comment-{coid}
     */
    private function getCommentUrl(int $coid): string
    {
        $siteUrl = rtrim((string)Utils\Helper::options()->siteUrl, '/') . '/';

        try {
            $db = Typecho\Db::get();
            $row = $db->fetchRow(
                $db->select('cid')
                    ->from('table.comments')
                    ->where('coid = ?', $coid)
                    ->limit(1)
            );

            $cid = isset($row['cid']) ? (int)$row['cid'] : 0;
            if ($cid <= 0) {
                return $siteUrl;
            }

            /** @var \Widget\Archive $archive */
            $archive = \Typecho\Widget::widget('Widget_Archive@commentUrl', 'type=post', 'cid=' . $cid);

            if ($archive && isset($archive->permalink) && is_string($archive->permalink) && $archive->permalink !== '') {
                return $archive->permalink . '#comment-' . $coid;
            }
        } catch (\Throwable $e) {
            error_log('[Notice/TelegramWebhook] getCommentUrl failed: ' . $e->getMessage());
        }

        return $siteUrl;
    }

    /**
     * 根据评论状态构建按钮
     * waiting/其他：显示 通过审核/标记垃圾/删除评论/查看评论
     * approved：显示 标记垃圾/删除评论/查看评论
     */
    private function buildKeyboard(int $coid, string $status): array
    {
        $keyboard = [];

        if ($status !== 'approved') {
            $keyboard[] = [
                ['text' => '✓ 通过审核', 'callback_data' => "tg_approve_{$coid}"],
                ['text' => '⚠️ 标记为垃圾', 'callback_data' => "tg_spam_{$coid}"]
            ];
            $keyboard[] = [
                ['text' => '🗑️ 删除评论', 'callback_data' => "tg_delete_{$coid}"]
            ];
        } else {
            $keyboard[] = [
                ['text' => '⚠️ 标记为垃圾', 'callback_data' => "tg_spam_{$coid}"],
                ['text' => '🗑️ 删除评论', 'callback_data' => "tg_delete_{$coid}"]
            ];
        }

        $keyboard[] = [
            ['text' => '👁️ 查看评论', 'url' => $this->getCommentUrl($coid)]
        ];

        return [
            'inline_keyboard' => $keyboard
        ];
    }

    /**
     * 给消息追加/替换“操作状态”区块，避免重复追加
     */
    private function withOpStatus(string $originalText, string $statusText): string
    {
        // 去掉旧的“操作状态”段落（如果已有）
        $text = preg_replace('/\n\n<b>操作状态：<\/b>.*$/s', '', $originalText) ?? $originalText;
        return $text . "\n\n<b>操作状态：</b> {$statusText}";
    }

    /**
     * 处理回调查询
     */
    public function handleCallback($callbackQuery)
    {
        $callbackId = $callbackQuery['id'] ?? '';

        if ($callbackId !== '') {
            $this->answerCallback($callbackId, '处理中...', false);
        }

        $chatId = $callbackQuery['from']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $messageId = $callbackQuery['message']['message_id'] ?? null;

        preg_match('/^tg_(\w+)_(\d+)$/', $data, $matches);
        if (!$matches) {
            if ($callbackId !== '') {
                $this->answerCallback($callbackId, '按钮数据无效: ' . $data, true);
            }
            return;
        }

        $action = $matches[1];
        $coid = intval($matches[2]);

        try {
            $db = Typecho\Db::get();
            $comment = $db->fetchRow($db->select()->from('table.comments')->where('coid = ?', $coid));

            if (!$comment) {
                $this->answerCallback($callbackId, '评论不存在', true);
                return;
            }

            $statusText = '';
            $newStatus = null;

            switch ($action) {
                case 'approve':
                    $newStatus = 'approved';
                    $statusText = '✓ 已通过审核';

                    try {
                        $userRow = null;

                        if (method_exists($this, 'getOwnerUserRow')) {
                            $userRow = $this->getOwnerUserRow();
                        }

                        if (method_exists($this, 'getBindings')
                            && method_exists($this, 'findEmailByChatId')
                            && method_exists($this, 'getUserRowByEmail')
                            && $chatId !== null
                        ) {
                            $bindings = $this->getBindings();
                            if (!empty($bindings)) {
                                $email = $this->findEmailByChatId((string)$chatId);
                                if ($email) {
                                    $bindUser = $this->getUserRowByEmail($email);
                                    if ($bindUser) {
                                        $userRow = $bindUser;
                                    }
                                }
                            }
                        }

                        if ($userRow && method_exists($this, 'replyToComment')) {
                            $this->replyToComment($coid, $userRow, '已处理：通过审核');
                        }
                    } catch (\Throwable $e) {
                        error_log('[Notice/TelegramWebhook] approve extra failed: ' . $e->getMessage());
                    }
                    break;

                case 'spam':
                    $newStatus = 'spam';
                    $statusText = '⚠️ 已标记为垃圾';
                    break;

                case 'delete':
                    $db->query($db->delete('table.comments')->where('coid = ?', $coid));
                    $statusText = '🗑️ 已删除';

                    $originalText = (string)($callbackQuery['message']['text'] ?? '');
                    $newText = $this->withOpStatus($originalText, $statusText);

                    $this->editMessage($chatId, $messageId, $newText, null);
                    $this->answerCallback($callbackId, $statusText, true);
                    return;

                default:
                    $this->answerCallback($callbackId, '未知操作', true);
                    return;
            }

            // 更新评论状态
            if (!empty($newStatus)) {
                $db->query(
                    $db->update('table.comments')
                        ->rows(['status' => $newStatus])
                        ->where('coid = ?', $coid)
                );
            }

            // 编辑原消息：追加操作状态
            $originalText = (string)($callbackQuery['message']['text'] ?? '');
            $newText = $this->withOpStatus($originalText, $statusText);

            // spam/delete 后不显示按钮；approve 后保留（垃圾/删除/查看）
            if ($newStatus === 'spam') {
                $this->editMessage($chatId, $messageId, $newText, null);
            } else {
                $replyMarkup = $this->buildKeyboard($coid, (string)$newStatus);
                $this->editMessage($chatId, $messageId, $newText, $replyMarkup);
            }

            $this->answerCallback($callbackId, $statusText, true);
        } catch (\Throwable $e) {
            error_log('Telegram Callback Error: ' . $e->getMessage());
            $this->answerCallback($callbackId, '操作失败', true);
        }
    }

    /**
     * 发送消息
     */
    public function sendMessage($chatId, $text, $replyMarkup = null)
    {
        $param = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $param['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->callApi('sendMessage', $param);
    }

    /**
     * 设置 Webhook
     */
    public static function setupWebhook()
    {
        try {
            $pluginOptions = Utils\Helper::options()->plugin('Notice');
            $token = $pluginOptions->tgToken ?? '';

            if (empty($token)) {
                return ['success' => false, 'message' => '未设置 Bot Token'];
            }

            $siteUrl = Utils\Helper::options()->siteUrl;
            if (empty($siteUrl)) {
                return ['success' => false, 'message' => '无法获取站点 URL'];
            }

            $webhookUrl = $siteUrl . 'action/telegram_webhook';
            
            $webhook = new self($token);
            $response = $webhook->callApiJson('setWebhook', [
                'url' => $webhookUrl,
                'allowed_updates' => ['callback_query']
            ]);
            
            if (!is_array($response)) {
                return ['success' => false, 'message' => '无效的 API 响应: ' . var_export($response, true)];
            }
            
            if (isset($response['ok']) && $response['ok'] === true) {
                return ['success' => true, 'message' => 'Webhook 已设置成功', 'url' => $webhookUrl];
            } else {
                $errorMsg = $response['description'] ?? '设置失败';
                return ['success' => false, 'message' => $errorMsg];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '异常: ' . $e->getMessage()];
        }
    }
}

