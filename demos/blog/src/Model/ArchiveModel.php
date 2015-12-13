<?php

namespace App\Model;

class ArchiveModel
{
    public static $db = null;

    public function __construct()
    {
        if(!self::$db)
        {
            self::$db = $sqlitedb = new \DB('sqlite:blog.db');
        }
    }

    public function compose($user, $title, $content)
    {
        $id =self::$db->insert('INSERT INTO archive (author, title, content) VALUES (?, ?, ?)', array($user, $title, $content));
        return $id;
    }
}
