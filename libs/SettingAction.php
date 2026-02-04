<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Widget;
use Utils;

// 导入 Telegram 相关类
require_once __DIR__ . '/Telegram.php';

class SettingAction extends Typecho\Widget implements Widget\ActionInterface
{
    private Typecho\Db $_db;
    private string $_pluginName='plugin:Notice';
    private string $_pluginBackupName='plugin:Notice-Backup';

    /**
     * @throws Typecho\Db\Exception
     */
    private function backup()
    {
        $setting = $this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginName));
        $value = $setting['value'];
        if ($this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName))) {
            $update = $this->_db->update('table.options')->rows(array('value' => $value))->where('name = ?', $this->_pluginBackupName);
            $updateRows = $this->_db->query($update);
            echo 1;
        } else {
            $insert = $this->_db->insert('table.options')->rows(array('name' => $this->_pluginBackupName, 'user' => '0', 'value' => $value));
            $this->_db->query($insert);
            echo 2;
        }
    }

    /**
     * @throws Typecho\Db\Exception
     */
    private function del_backup()
    {
        if ($this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName))) {
            $delete = $this->_db->delete('table.options')->where('name = ?', $this->_pluginBackupName);
            $deletedRows = $this->_db->query($delete);
            echo 1;
        } else {
            echo -1;
        }
    }

    /**
     * @throws Typecho\Db\Exception
     */
    private function recover_backup()
    {
        if ($this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName))) {
            $setting = $this->_db->fetchRow($this->_db->select()->from('table.options')->where('name = ?', $this->_pluginBackupName));
            $value = $setting['value'];
            $update = $this->_db->update('table.options')->rows(array('value' => $value))->where('name = ?', $this->_pluginName);
            $updateRows = $this->_db->query($update);
            echo 1;
        } else {
            echo -1;
        }
    }

    /**
     * 设置 Telegram Webhook
     */
    private function setup_webhook()
    {
        // 清空输出缓冲
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $result = TelegramWebhook::setupWebhook();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '异常：' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    protected function init()
    {
        $this->_db = Typecho\Db::get();
    }

    public function action()
    {
        Typecho\Widget::widget('Widget_User')->pass('administrator');
        
        if ($this->request->is('do=setup_webhook')) {
            $this->setup_webhook();
            return;
        }
        
        $this->init();
        
        $this->on($this->request->is('do=backup'))->backup();
        $this->on($this->request->is('do=del_backup'))->del_backup();
        $this->on($this->request->is('do=recover_backup'))->recover_backup();
    }
}
