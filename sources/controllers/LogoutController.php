<?php

class LogoutController
{
    public static function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header("Location: /login");
        exit();
    }
}