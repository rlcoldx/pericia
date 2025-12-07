<?php

namespace Agencia\Close\Helpers\User;

class UserIdentification
{
    private Identification $identification;

    public function processIdentification($identificationParam): Identification
    {
        $this->identification = new Identification();
        if (filter_var($identificationParam, FILTER_VALIDATE_EMAIL)) {
            $this->identification->setType('email');
            $this->identification->setColumn('email');
            $this->identification->setIdentification($identificationParam);
        } else {
            $this->identification->setType('phone');
            $this->identification->setColumn('telefone');
            $this->identification->setIdentification(preg_replace('/[^0-9]/', '', $identificationParam));
        }
        return $this->identification;
    }

    public function getIdentification(): Identification
    {
        return $this->identification;
    }

    public function getFakeEmail(): Identification
    {
        $secondIdentification = new Identification();
        $secondIdentification->setType('email');
        $secondIdentification->setColumn('email');
        $secondIdentification->setIdentification('fake-'.time().'@buscanarede.com.br');

        return $secondIdentification;
    }
}