<?php

namespace Agencia\Close\Helpers\Link;

class LinkRecover
{
    private string $email;
    private string $data;

    public static function generate(string $email)
    {
        $data = date('Y-m-d H:i:s');
        $data = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($data)));
        $link = base64_encode($email . '|' . $data);
        return str_replace('=', '', $link);
    }

    public function decrypt(string $key)
    {
        $data = base64_decode($key);
        $data = explode('|', $data);
        $this->email = $data[0];
        $this->data = $data[1];
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isValidData(): bool
    {
        $date = strtotime($this->data);
        $now = strtotime(date("Y-m-d H:i:s"));
        if($date > $now) {
            return true;
        } else {
            return false;
        }
    }

}