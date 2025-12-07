<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FilterHash extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('hash', [$this, 'getHash']),
        ];
    }

    public function getHash($fileToBeFound)
    {
        try {
            $path = __DIR__ . '/../../../view/assets/dist/manifest.json';
            $jsonContents = file_get_contents($path);
            $filesCashed = json_decode($jsonContents, true);
            foreach($filesCashed as $name => $file){
                if($fileToBeFound === $name){
                    return $file;
                }
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
