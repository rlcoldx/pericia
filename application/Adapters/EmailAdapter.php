<?php

namespace Agencia\Close\Adapters;

use Agencia\Close\Helpers\Result;
use Agencia\Close\Models\Configuracao\Configuracao;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailAdapter
{
    private PHPMailer $mail;
    private Result $result;
    private array $configuracao;
    const name_site = NAME;

    /**
     * @throws Exception
     */
    public function __construct(?int $empresa = null)
    {
        $this->result = new Result();
        $this->mail = new PHPMailer(false);
        
        // Busca configurações do banco de dados
        if ($empresa === null) {
            $empresa = $_SESSION['pericia_perfil_empresa'] ?? 0;
        }
        
        $configModel = new Configuracao();
        $this->configuracao = $configModel->getConfiguracoesOuPadrao((int) $empresa);
        
        //Server settings
//        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $this->mail->isSMTP();
        $this->mail->CharSet = 'UTF-8';//Send using SMTP
        $this->mail->Host = $this->configuracao['mail_host'] ?? 'smtp.gmail.com';                     //Set the SMTP server to send through
        $this->mail->SMTPAuth = true;                                   //Enable SMTP authentication
        $this->mail->Username = $this->configuracao['mail_user'] ?? '';                     //SMTP username
        $this->mail->Password = $this->configuracao['mail_password'] ?? '';                               //SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $this->mail->Port = 587;
        $this->mail->setFrom($this->configuracao['mail_email'] ?? '', self::name_site);
        $this->mail->isHTML(true);
        
        // Adiciona CC padrão se configurado
        if (!empty($this->configuracao['mail_cc'])) {
            $emailsCc = array_map('trim', explode(',', $this->configuracao['mail_cc']));
            foreach ($emailsCc as $email) {
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->mail->addCC($email);
                }
            }
        }
    }

    public function addAddress(string $email)
    {
        //$this->mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
        $this->mail->addAddress($email); //Name is optional
    }

    public function addCC(string $email)
    {
        $this->mail->addCC($email);
    }

    public function addMultipleCC(array $emails)
    {
        foreach ($emails as $email) {
            $email = trim($email);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->mail->addCC($email);
            }
        }
    }

    public function setSubject($subject)
    {
        $this->mail->Subject = $subject;
    }

    public function setBody(string $file, array $data = [])
    {
        $template = new TemplateAdapter();
        $mail = $template->render($file, $data);
        $this->mail->Body = $mail;
    }

    public function setBodyHtml(string $html)
    {
        $this->mail->Body = $html;
    }

    public function send($result)
    {
        try {
            $this->mail->send();
            $this->result->setError(false);
            $this->result->setMessage($result);
        } catch (Exception $e) {
            $this->result->setError(true);
            $this->result->setMessage('Falha ao enviar o E-mail!!!');
            $this->result->setInfo([
                'message' => "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}"
            ]);
        }
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function o()
    {
//            $this->mail->addReplyTo('info@example.com', 'Information');
//            $this->mail->addCC('cc@example.com');
//            $this->mail->addBCC('bcc@example.com');

//            //Attachments
//            $this->mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
//            $this->mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
    }
}