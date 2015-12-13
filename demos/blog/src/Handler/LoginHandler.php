<?php

namespace App\Handler;
use App\Model;
class LoginHandler
{
    public function get()
    {
        \TPL::assign('title', '登录');
        \TPL::render('login.html');
    }
    public function post()
    {
        $userModel = new Model\UserModel();
        $result = $userModel->login(\Request::post('email'), \Request::post('password'));
        if($result)
        {
            \Cookie::set('user', 'admin');
            header("Location: /");
        }
        else
        {
            \TPL::assign('title', '登录');
            \TPL::assign('error', '账号或者密码错误');
            \TPL::render('login.html');
        }
    }
}

