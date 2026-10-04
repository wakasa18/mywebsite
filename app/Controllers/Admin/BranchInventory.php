<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Legacy redirect for the removed Branch Inventory management page.
 *
 * Branch inventory data and backend stock operations are still retained.
 * Stock management is now handled from the Products page to avoid two
 * separate screens editing the same branch inventory records.
 */
class BranchInventory extends BaseController
{
    public function index(): RedirectResponse
    {
        return $this->redirectToProducts();
    }

    public function edit(?int $id = null): RedirectResponse
    {
        return $this->redirectToProducts();
    }

    public function update(?int $id = null): RedirectResponse
    {
        return $this->redirectToProducts();
    }

    private function redirectToProducts(): RedirectResponse
    {
        return redirect()->to(site_url('products'))->with(
            'info',
            'Branch stock is managed from the Products page.'
        );
    }
}
