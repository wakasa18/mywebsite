<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }
    public function dbTest()
    {
        try {
            $db = \Config\Database::connect();
            $db->initialize();

            if ($db->connID) {
                return 'Database connected successfully!';
            }

            return 'Database connection failed.';
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
