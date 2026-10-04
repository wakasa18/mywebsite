<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!\App\Libraries\AccountSession::check()) {
            return redirect()->to(site_url('login'))->with('error', 'Please log in first.');
        }

        if (session()->get('role') !== 'admin') {
            $role = session()->get('role');
            $home = $role === 'cashier' ? site_url('cashier/sales') : site_url('login');
            return redirect()->to($home)->with('error', 'Access denied. Admin only.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
