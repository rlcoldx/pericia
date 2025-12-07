<?php

namespace Agencia\Close\Services\Oauth;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

class FacebookAuth
{
    private string $urlCallback = DOMAIN . '/login-facebook/callback';
    private string $loginUrl;

    public function begin(): void
    {
        $facebook = new Facebook([
            'app_id' => '1775173535968790',
            'app_secret' => 'f8ac0d8863b801b6d036b61c5c1a9811',
            'default_graph_version' => 'v2.10',
            //'default_access_token' => '{access-token}', // optional
        ]);
        $helper = $facebook->getRedirectLoginHelper();

        $permissions = ['email']; // Optional permissions

        try {
            if (isset($_SESSION['face_access_token'])) {
                $accessToken = $_SESSION['face_access_token'];
            } else {
                $accessToken = $helper->getAccessToken($this->urlCallback);
            }

        } catch (FacebookResponseException $e) {
            // CASO NÃO DÊ PERMISSÃO
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            // ERRO DA API
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (!isset($accessToken)) {

            $url_login = $this->urlCallback;
            $this->loginUrl = $helper->getLoginUrl($url_login, $permissions);

        } else {

            $url_login = $this->urlCallback;
            $this->loginUrl = $helper->getLoginUrl($url_login, $permissions);

            //Usuário ja autenticado
            if (isset($_SESSION['face_access_token'])) {

                $facebook->setDefaultAccessToken($_SESSION['face_access_token']);

            } else {

                $_SESSION['face_access_token'] = (string)$accessToken;
                $oAuth2Client = $facebook->getOAuth2Client();
                $_SESSION['face_access_token'] = (string)$oAuth2Client->getLongLivedAccessToken($_SESSION['face_access_token']);
                $facebook->setDefaultAccessToken($_SESSION['face_access_token']);
            }

            try {

                $response = $facebook->get('/me?fields=name, picture, email');
                $user = $response->getGraphUser();

            } catch(FacebookResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
            } catch(FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }
        }
    }

    public function getUrl(): string
    {
        return $this->loginUrl;
    }
}