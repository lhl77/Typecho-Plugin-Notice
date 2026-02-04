<?php

namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once "FormElement/MDFormElements.php";
require_once "FormElement/MDCheckbox.php";
require_once "FormElement/MDRadio.php";
require_once "FormElement/MDSelect.php";
require_once "FormElement/MDText.php";
require_once "FormElement/MDTextarea.php";

use Typecho;
use TypechoPlugin\Notice\libs\FormElement\MDCheckbox;
use TypechoPlugin\Notice\libs\FormElement\MDRadio;
use TypechoPlugin\Notice\libs\FormElement\MDSelect;
use TypechoPlugin\Notice\libs\FormElement\MDText;
use TypechoPlugin\Notice\libs\FormElement\MDTextarea;
use TypechoPlugin\Notice\libs\FormElement\MDTitle;
use TypechoPlugin\Notice\libs\FormElement\MDCustomLabel;
use Utils;
use TypechoPlugin\Notice;
use const TypechoPlugin\Notice\__TYPECHO_PLUGIN_NOTICE_VERSION__;

class Config
{
    public static function style(Typecho\Widget\Helper\Form $form)
    {
        $option = Utils\Helper::options();
        echo '<link href="https://cdn.jsdelivr.net/npm/mdui@0.4.3/dist/css/mdui.min.css" rel="stylesheet">';
        echo '<script src="https://cdn.jsdelivr.net/npm/mdui@0.4.3/dist/js/mdui.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/jquery@2.2.4/dist/jquery.min.js" type="text/javascript"></script>';
        echo '<link href="' . $option->pluginUrl . '/Notice/assets/notice.css" rel="stylesheet" type="text/css"/>';
        echo '<script src="' . $option->pluginUrl . '/Notice/assets/notice.js"></script>';
    }

    public static function header(Typecho\Widget\Helper\Form $form)
    {
        $db = Typecho\Db::get();
        if ($db->fetchRow($db->select()->from('table.options')->where('name = ?', 'plugin:Notice-Backup'))) {
            $backupExist = '<div class="mdui-chip"><span class
        class="mdui-chip-title mdui-text-color-light-blue">数据库中存在插件配置备份</span></div>';
        } else {
            $backupExist = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-red"><i class="mdui-icon material-icons">backup</i></span><span 
        class="mdui-chip-title mdui-text-color-red">数据库没有插件配置备份</span></div>';
        }
        $tag = Notice\libs\Version::getNewRelease();
        $tag_compare = version_compare(__TYPECHO_PLUGIN_NOTICE_VERSION__, $tag);
        if ($tag_compare < 0) {
            $update = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-red"><i class="mdui-icon material-icons">system_update_alt</i></span>
                <span class="mdui-chip-title mdui-text-color-red">新版本' . $tag . '已可用</span></div>';
        } elseif ($tag_compare == 0) {
            $update = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-green"><i class="mdui-icon material-icons">cloud_done</i></span>
                <span class="mdui-chip-title mdui-text-color-light-blue">当前是最新版本</span></div>';
        } else {
            $update = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-amber"><i class="mdui-icon material-icons">warning</i></span>
                <span class="mdui-chip-title mdui-text-color-cyan">您当前正在使用测试版</span></div>';
        }

        echo <<<EOF
<div class="mdui-card">
  <div class="mdui-card-media">
    <img src="https://i.loli.net/2020/11/20/17Sg53qNMmPDJsv.jpg"/>
    <div class="mdui-card-media-covered mdui-card-media-covered-transparent">
      <div class="mdui-card-primary">
        <div class="mdui-card-primary-title">Notice</div>
        <div class="mdui-card-primary-subtitle">欢迎使用 Notice 插件</div>
      </div>
    </div>
  </div>
  
  <div class="mdui-card-content">
  {$update}
  {$backupExist}
  </div>
  <div class="mdui-card-actions">
    <button class="mdui-btn mdui-ripple" mdui-tooltip="{content: '唯一指定发布源'}"><a href = "https://github.com/imzrme/Typecho-Plugin-Notice">Github</a></button>
    <button class="mdui-btn mdui-ripple" mdui-tooltip="{content: '欢迎来踩博客～'}"><a href = "https://mzrme.com/">作者博客</a></button>
    <button class="mdui-btn mdui-ripple showSettings" mdui-tooltip="{content: '展开所有设置后，使用 ctrl + F 可以快速搜索某一设置项'}">展开所有设置</button>
    <button class="mdui-btn mdui-ripple hideSettings">折叠所有设置</button>
    <br>
    <button class = "mdui-btn mdui-ripple mdui-color-light-green recover_backup" mdui-tooltip="{content: '从数据库插件配置备份恢复数据'}">从备份恢复配置</button>
    <button class = "mdui-btn mdui-ripple mdui-color-yellow-100 backup" mdui-tooltip="{content: '1. 仅仅是备份Notice的设置</br>2. 禁用插件的时候，设置数据会清空但是备份设置不会被删除。</br>3. 所以当你重启启用插件时，可以恢复备份设置。</br>4. 备份设置同样是备份到数据库中。</br>5. 如果已有备份设置，再次备份会覆盖之前备份<br/>6. 插件开发过程中会尽量保证配置项不发生较大改变～'}">备份插件配置</button>
    <button class = "mdui-btn mdui-ripple mdui-color-red-200 del_backup" mdui-tooltip="{content:'删除handsome备份数据'}">删除现有Notice插件配置备份</button>
  </div>
  
</div>
EOF;

    }

    public static function script(Typecho\Widget\Helper\Form $form)
    {
        $blog_url = Utils\Helper::options()->siteUrl;
        $action_url = $blog_url . 'action/' . Notice\Plugin::$action_setting;

        echo <<<EOF
<script>
    $(function(){
         $('.showSettings').bind('click',function() {
           $('.mdui-panel-item').addClass('mdui-panel-item-open');
         });
         $('.hideSettings').bind('click',function() {
            $('.mdui-panel-item').removeClass('mdui-panel-item-open');
         });
     });
    
    $('.backup').click(function() {
         mdui.confirm("确认要备份数据吗", "备份数据", function() {
           $.ajax({
            url: '$action_url',
            data: {"do":"backup"},
            success: function(data) {
                if (data !== "-1"){
                    mdui.snackbar({
                    message: '备份成功，操作码:' + data +',正在刷新页面……',
                    position: 'bottom'
                });
                    setTimeout(function (){
                    location.reload();
                },1000);
                }else {
                    mdui.snackbar({
                    message: '备份失败,错误码' + data,
                    position: 'bottom'
                });
                }
            }
        })
         },null , {"confirmText":"确认","cancelText":"取消"})

     });
     
     
     $('.del_backup').click(function() {
         
         mdui.confirm("确认要删除备份数据吗", "删除备份", function() {
            $.ajax({
            url: '$action_url',
            data: {"do":"del_backup"},
            success: function(data) {
                if (data !== "-1"){
                    mdui.snackbar({
                    message: '删除备份成功，操作码:' + data +',正在刷新页面……',
                    position: 'bottom'
                });
                    setTimeout(function (){
                    location.reload();
                },1000);
                }else {
                    var message = "没有备份，你删什么删，别问我为什么这么冲，因为总有问我为啥删除失败，对不起。";
                    mdui.snackbar({
                    message: message,
                    position: 'bottom'
                });
                }
            }
        })
},null , {"confirmText":"确认","cancelText":"取消"});
         
});
     
     $('.recover_backup').click(function() {
         
         
        mdui.confirm("确认要恢复备份数据吗", "恢复备份", function() {
    $.ajax({
        url: '$action_url',
        data: {"do":"recover_backup"},
        success: function(data) {
            if (data !== "-1"){
                mdui.snackbar({
                message: '恢复备份成功，操作码:' + data +',正在刷新页面……',
                position: 'bottom'
            });
                setTimeout(function (){
                    location.reload();
                },1000);
            }else {
                mdui.snackbar({
                    message: '恢复备份失败,错误码' + data,
                    position: 'bottom'
                });
            }
        }
    })

},null , {"confirmText":"确认","cancelText":"取消"})
     });
</script>
EOF;

    }

    public static function Setting(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('插件配置', '推送服务开关、插件更新提示、数据库配置、日志配置', false));

        $setting = new MDCheckbox(
            'setting',
            array(
                'serverchan' => '启用Server酱',
                'qmsg' => '启用Qmsg酱',
                'mail' => '启用邮件',
                'msgraph' => '启用Microsoft Graph邮件',
                'telegram' => '启用Telegram',
                'updatetip' => '启用更新提示',
            ),
            array('updatetip'),
            '插件设置',
            _t('请选择您要启用的通知方式。<br/>' .
                '当勾选"启用更新提示"时，在本插件更新后，您会在后台界面看到一条更新提示～')
        );
        $form->addInput($setting->multiMode());

        $delDB = new MDRadio(
            'delDB',
            array(
                '1' => '是',
                '0' => '否'
            ),
            '1',
            _t('卸载插件时删除数据库'),
            _t('勾选否则表示当您禁用此插件时，插件的历史记录仍将存留在数据库中。')
        );
        $form->addInput($delDB);

        $enable_log = new MDRadio(
            'enableLog',
            array(
                '2' => "调试",
                '1' => "生产",
                '0' => '关闭'
            ),
            '1',
            _t('日志级别'),
            _t('调试方便检查参数配置情况，生产仅记录发信内容，关闭则不会在数据库中存储任何日志。')
        );
        $form->addInput($enable_log);
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function Serverchan(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Server酱Turbo配置', 'SCKEY、Server酱Turbo通知模版<span style="color:red"><a href="https://sc.ftqq.com/9.version">Server酱升级！</a>请重新配置本项</span>', false));
        $scKey = new MDText(
            'scKey',
            NULL,
            NULL,
            _t('Server酱SCKEY'),
            _t('想要获取 SCKEY 则需要在 <a href="https://sct.ftqq.com/">Server酱Turbo版</a> 登录并进行捐赠<br>
                同时，注册后需要在 <a href="http://sct.ftqq.com/">Server酱Turbo版</a> 绑定你的微信号才能收到推送')
        );
        $form->addInput($scKey);

        $scMsg = new MDTextarea(
            'scMsg',
            NULL,
            "评论人：**{author}**\n\n 评论内容:\n> {text}\n\n链接：{permalink}",
            _t("Server酱Turbo通知模版"),
            _t("通过server酱Turbo通知您的内容模版，可使用变量列表见插件说明")
        );
        $form->addInput($scMsg);
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function checkServerchan(array $settings)
    {
        if (in_array('serverchan', $settings['setting'])) {
            if (empty($settings['scKey'])) {
                return _t('请填写SCKEY');
            }
            if (empty($settings['scMsg'])) {
                return _t('请填写Server酱通知模版');
            }
        }
        return '';
    }

    public static function Qmsgchan(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Qmsg酱配置', 'QmsgKEY、QmsgQQ、Qmsg酱通知模版', false));
        $QmsgKey = new MDText(
            'QmsgKey',
            NULL,
            NULL,
            _t('QmsgKey'),
            _t('请进入 <a href="https://qmsg.zendee.cn/api">Qmsg酱文档</a> 获取您的 KEY: https://qmsg.zendee.cn:443/send/{QmsgKey}<br>
                请注意此处只需填写key即可，不要填整个链接！！')
        );
        $form->addInput($QmsgKey);

        $QmsgQQ = new MDText(
            'QmsgQQ',
            NULL,
            NULL,
            _t('QmsgQQ'),
            _t('请进入 <a href="https://qmsg.zendee.cn/user">Qmsg酱</a> 选择机器人QQ号，使用您接收通知的QQ号添加其为好友，并将该QQ号添加到该页面下方QQ号列表中<br/>
                如果您有多个应用，且在该网站上增加了许多QQ号，您可以在这里填写本站点推送的QQ号（用英文逗号分割，最后不需要加逗号），不填则向该网站列表中所有的QQ号发送消息')
        );
        $form->addInput($QmsgQQ);

        $QmsgMsg = new MDTextarea(
            'QmsgMsg',
            NULL,
            "评论人：{author}\n评论内容:\n{text}\n\n链接：{permalink}",
            _t("Qmsg酱通知模版"),
            _t("通过Qmsg酱通知您的内容模版，可使用变量列表见插件说明")
        );
        $form->addInput($QmsgMsg);
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function checkQmsgchan(array $settings)
    {
        if (in_array('qmsg', $settings['setting'])) {
            if (empty($settings['QmsgKey'])) {
                return _t('请填写QmsgKEY');
            }
            if (empty($settings['QmsgMsg'])) {
                return _t('请填写Qmsg酱通知模版');
            }
        }
        return '';
    }

    public static function SMTP(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('SMTP 配置', NULL, false));
        $host = new MDText(
            'host',
            NULL,
            '',
            _t('邮件服务器地址'),
            _t('请填写 SMTP 服务器地址')
        );
        $form->addInput($host);

        $port = new MDText(
            'port',
            null,
            465,
            _t('端口号'),
            _t('端口号必须是数字，一般为465')
        );
        $form->addInput($port->addRule('isInteger', _t('端口号必须是数字')));

        $ssl = new MDSelect(
            'secure',
            array('tls' => 'tls', 'ssl' => 'ssl'),
            'ssl',
            _t('连接加密方式')
        );
        $form->addInput($ssl);

        $auth = new MDRadio(
            'auth',
            array(1 => '是', 0 => '否'),
            1,
            _t('启用身份验证'),
            _t('勾选后必须填写用户名和密码两项')
        );
        $form->addInput($auth);

        $user = new MDText(
            'user',
            NULL,
            '',
            _t('用户名'),
            _t('启用身份验证后有效，一般为 name@domain.com ')
        );
        $form->addInput($user);

        $pwd = new MDText(
            'password',
            NULL,
            '',
            _t('密码'),
            _t('启用身份验证后有效，有些服务商可能需要专用密码，详询服务商客服')
        );
        $form->addInput($pwd);

        $from = new MDText(
            'from',
            NULL,
            '',
            _t('发信人邮箱')
        );
        $form->addInput($from->addRule('email', _t('请输入正确的邮箱地址')));

        $from_name = new MDText(
            'from_name',
            NULL,
            Utils\Helper::options()->title,
            _t('发信人名称'),
            _t('默认为站点标题')
        );
        $form->addInput($from_name);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function EmailSettings(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('邮件通知内容配置', '适用于 SMTP 和 Microsoft Graph', false));

        $titleForOwner = new MDText(
            'titleForOwner',
            null,
            "[{title}] 一文有新的评论",
            _t('博主接收邮件标题')
        );
        $form->addInput($titleForOwner->addRule('required', _t('博主接收邮件标题 不能为空')));

        $titleForGuest = new MDText(
            'titleForGuest',
            null,
            "您在 [{title}] 的评论有了回复",
            _t('访客接收邮件标题')
        );
        $form->addInput($titleForGuest->addRule('required', _t('访客接收邮件标题 不能为空')));

        $titleForApproved = new MDText(
            'titleForApproved',
            null,
            "您在 [{title}] 的评论已被审核通过",
            _t('访客接收邮件标题')
        );
        $form->addInput($titleForApproved->addRule('required', _t('访客接收邮件标题 不能为空')));

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }


    public static function MicrosoftGraph(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Microsoft Graph API 配置', '使用 Microsoft Entra ID 应用发送邮件', false));

        // 租户 ID
        $tenantId = new MDText(
            'msgraphTenantId',
            NULL,
            NULL,
            _t('租户 ID (Tenant ID)'),
            _t('Microsoft Entra ID 中的租户 ID')
        );
        $form->addInput($tenantId);

        // 客户端 ID
        $clientId = new MDText(
            'msgraphClientId',
            NULL,
            NULL,
            _t('客户端 ID (Client ID)'),
            _t('注册应用程序的客户端 ID')
        );
        $form->addInput($clientId);

        // 客户端密钥
        $clientSecret = new MDText(
            'msgraphClientSecret',
            NULL,
            NULL,
            _t('客户端密钥 (Client Secret)'),
            _t('应用程序的客户端密钥（请妥善保管）')
        );
        $form->addInput($clientSecret);

        // 发件人邮箱
        $senderEmail = new MDText(
            'msgraphSenderEmail',
            NULL,
            NULL,
            _t('发件人邮箱'),
            _t('用于发送邮件的用户邮箱地址，该用户需要是租户的正式成员并拥有有效邮箱')
        );
        $form->addInput($senderEmail->addRule('email', _t('请输入正确的邮箱地址')));

        // 发件人名称
        $senderName = new MDText(
            'msgraphSenderName',
            NULL,
            Utils\Helper::options()->title,
            _t('发件人名称'),
            _t('默认为站点标题')
        );
        $form->addInput($senderName);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }
    public static function Telegram(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Telegram Bot 配置', 'Telegram Bot Token、Chat ID、邮箱绑定、Webhook设置 (by <a href="https://lhl.one/" target="_blank">LHL</a>)', false));

        $tgToken = new MDText(
            'tgToken',
            NULL,
            NULL,
            _t('Bot Token'),
            _t('从 <a href="https://t.me/botfather">BotFather</a> 创建机器人后获取')
        );
        $form->addInput($tgToken);

        $blog_url = Utils\Helper::options()->siteUrl;
        $action_url = $blog_url . 'action/' . Notice\Plugin::$action_setting;

        // 检查 Webhook 是否已设置
        $webhookStatus = self::checkTelegramWebhook();

        if (!$webhookStatus['is_set']) {
            $webhookInfo = new MDCustomLabel("<div class='mdui-card' style='margin-bottom:10px;'>
                <div class='mdui-card-content'>
                <strong>⚠️ Webhook 配置：</strong><br/>
                <p>点击按钮自动设置 Telegram Webhook</p>
                <button class='mdui-btn mdui-color-theme-accent mdui-ripple setup_webhook' type='button'>设置 Webhook</button>
                <div id='webhookStatus' style='margin-top:10px;'></div>
                </div>
                </div>
                <script>
                $('.setup_webhook').click(function() {
        mdui.confirm(\"确认要设置 Telegram Webhook 吗？\", \"设置 Webhook\", function() {
            $.ajax({
                url: '$action_url',
                data: {\"do\":\"setup_webhook\"},
                success: function(data) {
                    if (data.success){
                        mdui.snackbar({
                            message: data.message + '，正在刷新页面……',
                            position: 'bottom'
                        });
                        setTimeout(function (){
                            location.reload();
                        },1000);
                    }else {
                        mdui.snackbar({
                            message: '设置失败：' + data.message,
                            position: 'bottom'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    mdui.snackbar({
                        message: '请求出错：' + xhr.status + ' ' + xhr.statusText,
                        position: 'bottom'
                    });
                }
            })
        },null , {\"confirmText\":\"确认\",\"cancelText\":\"取消\"})
    });
                </script>
                
                ");
            $form->addItem($webhookInfo);
        } else {
            $webhookInfo = new MDCustomLabel("<div class='mdui-card mdui-color-green' style='margin-bottom:10px;'>
                <div class='mdui-card-content'>
                <strong>✓ Webhook 已配置</strong><br/>
                <p>URL: " . htmlspecialchars($webhookStatus['url']) . "</p>
                <button class='mdui-btn mdui-color-theme-accent mdui-ripple setup_webhook' type='button'>重新设置 Webhook</button>
                <div id='webhookStatus' style='margin-top:10px;'></div>
                </div>
                </div>
                <script>
                $('.setup_webhook').click(function() {
        mdui.confirm(\"确认要设置 Telegram Webhook 吗？\", \"设置 Webhook\", function() {
            $.ajax({
                url: '$action_url',
                data: {\"do\":\"setup_webhook\"},
                success: function(data) {
                    if (data.success){
                        mdui.snackbar({
                            message: data.message + '，正在刷新页面……',
                            position: 'bottom'
                        });
                        setTimeout(function (){
                            location.reload();
                        },1000);
                    }else {
                        mdui.snackbar({
                            message: '设置失败：' + data.message,
                            position: 'bottom'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    mdui.snackbar({
                        message: '请求出错：' + xhr.status + ' ' + xhr.statusText,
                        position: 'bottom'
                    });
                }
            })
        },null , {\"confirmText\":\"确认\",\"cancelText\":\"取消\"})
    });
                </script>");
            $form->addItem($webhookInfo);
        }

        $tgChatId = new MDText(
            'tgChatId',
            NULL,
            NULL,
            _t('默认 Chat ID'),
            _t('接收通知的 Telegram Chat ID，若评论者邮箱在下方绑定列表中则优先使用绑定的 Chat ID')
        );
        $form->addInput($tgChatId);

        $tgMsg = new MDTextarea(
            'tgMsg',
            NULL,
            "🎉 您的文章 <b>{title}</b> 有新的回复！\n\n<b>{author} ：</b><code>{text}</code>",
            _t("Telegram 通知模板"),
            _t("支持 HTML 标签，可使用变量列表见插件说明。留空时使用默认模板。")
        );
        $form->addInput($tgMsg);

        $tgBindings = new MDTextarea(
            'tgBindings',
            NULL,
            '',
            _t('邮箱 -> Chat ID 绑定'),
            _t('JSON 格式，例如：<code>{"user@example.com":"123456789","admin@example.com":"987654321"}</code><br/>当评论者邮箱匹配时，将使用对应的 Chat ID 发送')
        );
        $form->addInput($tgBindings);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    /**
     * 检查 Telegram Webhook 是否已设置
     */
    private static function checkTelegramWebhook()
    {
        try {
            $pluginOptions = Utils\Helper::options()->plugin('Notice');
            $token = $pluginOptions->tgToken ?? '';

            if (empty($token)) {
                return ['is_set' => false, 'url' => null];
            }

            // 调用 Telegram API 获取 webhook 信息
            $url = "https://api.telegram.org/bot{$token}/getWebhookInfo";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result, true);

            if (isset($response['ok']) && $response['ok'] === true) {
                $webhookUrl = $response['result']['url'] ?? '';
                if (!empty($webhookUrl)) {
                    return ['is_set' => true, 'url' => $webhookUrl];
                }
            }

            return ['is_set' => false, 'url' => null];
        } catch (\Exception $e) {
            return ['is_set' => false, 'url' => null];
        }
    }

    public static function checkMicrosoftGraph(array $settings)
    {
        if (in_array('msgraph', $settings['setting'])) {
            if (empty($settings['msgraphTenantId'])) {
                return _t('请填写 Microsoft Graph 租户 ID');
            }
            if (empty($settings['msgraphClientId'])) {
                return _t('请填写 Microsoft Graph 客户端 ID');
            }
            if (empty($settings['msgraphClientSecret'])) {
                return _t('请填写 Microsoft Graph 客户端密钥');
            }
            if (empty($settings['msgraphSenderEmail'])) {
                return _t('请填写 Microsoft Graph 发件人邮箱');
            }
        }
        return '';
    }

    public static function checkSMTP(array $settings)
    {
        if (in_array('mail', $settings['setting'])) {
            if (empty($settings['host'])) {
                return _t('请填写SMTP服务器地址');
            }
            if (empty($settings['port'])) {
                return _t('请填写端口号');
            }
            if ($settings['auth'] == 1) {
                if (empty($settings['user'])) {
                    return _t('请填写SMTP用户名');
                }
                if (empty($settings['password'])) {
                    return _t('请填写SMTP密码');
                }
            }
            if (empty($settings['from'])) {
                return _t('请填写发信人邮箱');
            }
        }
        return '';
    }

    public static function check(array $settings)
    {
        if (!isset($settings['setting']) || !is_array($settings['setting'])) {
            $settings['setting'] = [];
        }

        $s = self::checkServerchan($settings);
        if ($s !== '')
            return $s;

        $s = self::checkQmsgchan($settings);
        if ($s !== '')
            return $s;

        $s = self::checkSMTP($settings);
        if ($s !== '')
            return $s;

        $s = self::checkMicrosoftGraph($settings);
        if ($s !== '')
            return $s;

        $s = self::checkEmailSettings($settings);
        if ($s !== '')
            return $s;

        $s = self::checkTelegram($settings);
        if ($s !== '')
            return $s;

        return '';
    }

    /**
     * Telegram 配置校验
     * 规则：
     * - 启用 telegram 时：tgToken 必填；tgChatId 必填且为纯数字
     * - tgBindings 允许为空；若填写则必须为有效 JSON（可选但建议）
     */
    public static function checkTelegram(array $settings): string
    {
        if (!in_array('telegram', $settings['setting'], true)) {
            return '';
        }

        if (empty($settings['tgToken'])) {
            return _t('请填写 Telegram Bot Token');
        }

        $chatId = trim((string) ($settings['tgChatId'] ?? ''));
        if ($chatId === '') {
            return _t('请填写 Telegram 默认 Chat ID');
        }

        // 仅允许纯数字（如果要支持群组/频道负数ID，把正则改为：/^-?\d+$/）
        if (!preg_match('/^\d+$/', $chatId)) {
            return _t('Telegram 默认 Chat ID 必须为数字');
        }

        // tgBindings 可为空；如果填了则校验 JSON
        $bindingsRaw = trim((string) ($settings['tgBindings'] ?? ''));
        if ($bindingsRaw !== '') {
            $decoded = json_decode($bindingsRaw, true);
            if (!is_array($decoded)) {
                return _t('“邮箱 -> Chat ID 绑定”必须是有效 JSON');
            }
        }

        return '';
    }
    public static function checkEmailSettings(array $settings)
    {
        if (in_array('mail', $settings['setting']) || in_array('msgraph', $settings['setting'])) {
            if (empty($settings['titleForOwner'])) {
                return _t('请填写博主接收邮件标题');
            }
            if (empty($settings['titleForGuest'])) {
                return _t('请填写访客接收邮件标题');
            }
            if (empty($settings['titleForApproved'])) {
                return _t('请填写审核通过邮件标题');
            }
        }
        return '';
    }
}
