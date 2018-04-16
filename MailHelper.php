<?php

namespace app\components;


use yii\base\Component;

class MailHelper extends Component
{
    public $smtp_username = 'richef.by.send.mail@gmail.com';  //Смените на адрес своего почтового ящика.
    private $smtp_port = '465'; // Порт работы.
    private $smtp_host =  'ssl://smtp.gmail.com';  //сервер для отправки почты
    private $smtp_password = 'richef123321';  //Измените пароль
    private $smtp_from = 'МегаСервис'; //Ваше имя - или имя Вашего сайта. Будет показывать при прочтении в поле "От кого"

    private $smtp_debug = true;  //Если Вы хотите видеть сообщения ошибок, укажите true вместо false
    private $smtp_charset = 'utf-8';	//кодировка сообщений. (windows-1251 или utf-8, итд)

    public function send($to='', $mail_to, $subject, $message, $headers='') {
        $SEND =	"Date: ".date("D, d M Y H:i:s") . " UT\r\n";
        $SEND .= 'Subject: =?'.$this->smtp_charset.'?B?'.base64_encode($subject)."=?=\r\n";
        if ($headers) $SEND .= $headers."\r\n\r\n";
        else
        {
            $SEND .= "Reply-To: ".$this->smtp_username."\r\n";
            $SEND .= "To: \"=?".$this->smtp_charset."?B?".base64_encode($to)."=?=\" <$mail_to>\r\n";
            $SEND .= "MIME-Version: 1.0\r\n";
            $SEND .= "Content-Type: text/html; charset=\"".$this->smtp_charset."\"\r\n";
            $SEND .= "Content-Transfer-Encoding: 8bit\r\n";
            $SEND .= "From: \"=?".$this->smtp_charset."?B?".base64_encode($this->smtp_from)."=?=\" <".$this->smtp_username.">\r\n";
            $SEND .= "X-Priority: 3\r\n\r\n";
        }
        $SEND .=  $message."\r\n";
        if( !$socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30) ) {
            if ($this->smtp_debug) echo $errno."<br>".$errstr;
            return false;
        }

        if (!$this->server_parse($socket, "220", __LINE__)) return false;

        fputs($socket, "HELO " . $this->smtp_host . "\r\n");
        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Не могу отправить HELO!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "AUTH LOGIN\r\n");
        if (!$this->server_parse($socket, "334", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Не могу найти ответ на запрос авторизаци.</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, base64_encode($this->smtp_username) . "\r\n");
        if (!$this->server_parse($socket, "334", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Логин авторизации не был принят сервером!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, base64_encode($this->smtp_password) . "\r\n");
        if (!$this->server_parse($socket, "235", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Пароль не был принят сервером как верный! Ошибка авторизации!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "MAIL FROM: <".$this->smtp_username.">\r\n");
        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Не могу отправить комманду MAIL FROM: </p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "RCPT TO: <" . $mail_to . ">\r\n");

        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Не могу отправить комманду RCPT TO: </p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "DATA\r\n");

        if (!$this->server_parse($socket, "354", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Не могу отправить комманду DATA</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, $SEND."\r\n.\r\n");

        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($this->smtp_debug) echo '<p>Не смог отправить тело письма. Письмо не было отправленно!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        return TRUE;
    }

    private function server_parse($socket, $response, $line = __LINE__) {
        global $config;
        while (@substr($server_response, 3, 1) != ' ') {
            if (!($server_response = fgets($socket, 256))) {
                if ($config['smtp_debug']) echo "<p>Проблемы с отправкой почты!</p>$response<br>$line<br>";
                return false;
            }
        }
        if (!(substr($server_response, 0, 3) == $response)) {
            if ($config['smtp_debug']) echo "<p>Проблемы с отправкой почты!</p>$response<br>$line<br>";
            return false;
        }
        return true;
    }

}