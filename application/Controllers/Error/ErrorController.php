<?php

namespace Agencia\Close\Controllers\Error;

use Agencia\Close\Controllers\Controller;

class ErrorController extends Controller
{
    public function show($params)
    {
        $this->params = $params;
        if(!isset($this->params['message'])){
            $this->params['message'] = 'Empresa não encontrada!';
        }
        $this->render('pages/error/404.twig', [ 'message' => $this->params['message']]);
    }
}