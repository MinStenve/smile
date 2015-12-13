<?php

namespace App\Model;

class UserModel
{
    public static $db = null;

    public function __construct()
    {
        if(!self::$db)
        {
            self::$db = $sqlitedb = new \DB('sqlite:blog.db');
        }
    }

    public function login($email, $password)
    {
        return (bool)self::$db->get('SELECT * FROM user WHERE email = ? AND password = ?', array($email, $password));
    }
}
