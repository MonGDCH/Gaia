<?php

declare(strict_types=1);

namespace app\service;

use mon\env\Config;
use mon\util\Instance;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * 邮件工具
 * 
 * @required phpmailer/phpmailer
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class MailerService
{
    use Instance;

    /**
     * 邮箱配置信息
     *
     * @var array
     */
    protected $config = [];

    /**
     * 错误信息
     *
     * @var mixed
     */
    protected $error;

    /**
     * 邮件实例
     *
     * @var PHPMailer
     */
    protected $mailer;

    /**
     * 构造方法
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $config = Config::instance()->get('email', []);
        }

        $this->config = array_merge($this->config, $config);
        $this->mailer = new PHPMailer(true);
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return MailerService
     */
    public function setConfig(array $config): MailerService
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return array 配置信息
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = null;
        return $error;
    }

    /**
     * 发送邮件
     *
     * @param string $title     邮件标题
     * @param string $content   邮件内容
     * @param array $to         接收人
     * @param array $cc         抄送人
     * @param array $bcc        秘密抄送人
     * @param array $attachment 附件
     * @param array $config     独立使用配置信息
     * @return boolean
     */
    public function send(string $title, string $content, array $to, array $cc = [], array $bcc = [], array $attachment = [], array $config = []): bool
    {
        $config = empty($config) ? $this->config : $config;
        try {
            // 设定邮件编码
            $this->mailer->CharSet = "UTF-8";
            // 关闭调试模式输出                 
            $this->mailer->SMTPDebug = 0;
            // SMTP服务器
            $this->mailer->isSMTP();
            $this->mailer->Host = $config['host'];
            // 启用SMTP身份验证
            $this->mailer->SMTPAuth = true;
            // SMTP用户名
            $this->mailer->Username = $config['user'];
            // SMTP密码
            $this->mailer->Password = $config['password'];
            // 启用 TLS 或者 ssl 协议         
            if ($config['ssl']) {
                $this->mailer->SMTPSecure = 'ssl';
            }
            // 服务器端口 25 或者465 具体要看邮箱服务器支持
            $this->mailer->Port = $config['port'];
            // 发件人
            $this->mailer->setFrom($config['from'], $config['name']);
            // 收件人
            $this->mailer->clearAddresses();
            foreach ($to as $item) {
                if (is_array($item)) {
                    // 设置收件人，及收件人名称
                    $this->mailer->addAddress($item['email'], $item['name']);
                } else {
                    $this->mailer->addAddress($item);
                }
            }
            // 回复的时候回复给哪个邮箱, 建议和发件人一致
            $this->mailer->clearReplyTos();
            $this->mailer->addReplyTo($config['from'], $config['name']);
            // 抄送
            $this->mailer->clearCCs();
            foreach ($cc as $item) {
                $this->mailer->addCC($item);
            }
            // 密抄
            $this->mailer->clearBCCs();
            foreach ($bcc as $item) {
                $this->mailer->addBCC($item);
            }
            // 添加附件
            $this->mailer->clearAttachments();
            foreach ($attachment as $file) {
                if (is_array($file)) {
                    // 发送附件并且重命名  
                    $this->mailer->addAttachment($file['path'], $file['name']);
                } else {
                    $this->mailer->addAttachment($file);
                }
            }
            // 是否以HTML文档格式发送
            $this->mailer->isHTML(true);
            // 邮件标题
            $this->mailer->Subject = $title;
            // 邮件内容
            $this->mailer->Body = $content;
            // 如果邮件客户端不支持HTML则显示此内容
            $this->mailer->AltBody = '当前邮件客户端不支持邮件内容显示，请更换客户端查看';
            // 发送邮件
            $this->mailer->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->error = $this->mailer->ErrorInfo;
            return false;
        }
    }
}
