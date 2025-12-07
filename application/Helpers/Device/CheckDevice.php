<?php

namespace Agencia\Close\Helpers\Device;

class CheckDevice
{
    private string $nameMobile = 'Mobile';
    private string $nameDesktop = 'Desktop';

    public function getName(): string
    {
        if ($this->isMobileDevice()) {
            return $this->nameMobile;
        } else {
            return $this->nameDesktop;
        }
    }

    public function isMobileDevice(): bool
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo 
            |fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }
}