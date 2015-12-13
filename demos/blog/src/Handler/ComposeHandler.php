<?php

namespace App\Handler;
use App\Model;
class ComposeHandler
{
    public function get()
    {
        \TPL::assign('title', '登录');
        \TPL::render('compose.html');
    }
    public function post()
    {
        $archiveModel = new Model\ArchiveModel();
        $title = \Request::post('title');
        $content = \Request::post('content');
        var_dump($content);
        $result = $archiveModel->compose(1, $title, $content);
        var_dump($result);
    }
}

