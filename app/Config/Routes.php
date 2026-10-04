<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::attemptLogin', ['filter' => 'login_throttle']);
$routes->get('/logout', 'Auth::logout');


/*
|--------------------------------------------------------------------------
| Shared Routes (Admin + Cashier)
|--------------------------------------------------------------------------
*/
$routes->group('', ['filter' => 'cashier'], function ($routes) {
    // POS
    $routes->get('cashier/sales', 'Cashier\SalesController::index');
    $routes->post('cashier/sales/add-to-cart', 'Cashier\SalesController::addToCart');
    $routes->post('cashier/sales/update-cart', 'Cashier\SalesController::updateCart');
    $routes->post('cashier/sales/remove-cart-item', 'Cashier\SalesController::removeCartItem');
    $routes->post('cashier/sales/clear-cart', 'Cashier\SalesController::clearCart');
    $routes->post('cashier/sales/checkout', 'Cashier\SalesController::checkout');
    $routes->get('cashier/sales/checkout-status', 'Cashier\SalesController::checkoutStatus');
    $routes->post('cashier/sales/sync-cart', 'Cashier\SalesController::syncCart');
    $routes->post('cashier/sales/hold-sale', 'Cashier\SalesController::holdSale');
    $routes->post('cashier/sales/resume-held-sale/(:num)', 'Cashier\SalesController::resumeHeldSale/$1');
    $routes->post('cashier/sales/discard-held-sale/(:num)', 'Cashier\SalesController::discardHeldSale/$1');
    $routes->get('cashier/sales/completed/(:num)', 'Cashier\SalesController::completed/$1');
    $routes->get('cashier/sales/view/(:num)', 'Cashier\SalesController::details/$1');
    $routes->get('cashier/sales/receipt/(:num)', 'Cashier\SalesController::receipt/$1');
    $routes->get('cashier/sales/search-products', 'Cashier\SalesController::searchProducts');
    $routes->get('cashier/sales/branch-availability', 'Cashier\SalesController::branchAvailability');
    $routes->get('cashier/sales/history', 'Cashier\SalesController::history');
    $routes->get('cashier/sales/exchange/(:num)', 'Cashier\ExchangeController::form/$1');
    $routes->post('cashier/sales/exchange/(:num)/review', 'Cashier\ExchangeController::review/$1');
    $routes->post('cashier/sales/exchange/(:num)/complete', 'Cashier\ExchangeController::complete/$1');
    $routes->get('cashier/sales/reprint/(:num)', 'Cashier\SalesController::reprint/$1');
    $routes->post('cashier/sales/refund/(:num)', 'Cashier\SalesController::refund/$1');
    $routes->get('cashier/sales/refund-form/(:num)', 'Cashier\SalesController::refundForm/$1');
    $routes->post('cashier/sales/refund-partial/(:num)', 'Cashier\SalesController::refundPartial/$1');
    $routes->get('cashier/sales/refund-slip/(:num)', 'Cashier\SalesController::refundSlip/$1');

    // Shared products access. Cashiers may create products only for their assigned branch.
    $routes->get('/products', 'Products::index');
    $routes->get('/products/create', 'Products::create');
    $routes->get('/products/catalog', 'Products::catalog');
    $routes->post('/products/add-existing', 'Products::addExisting');
    $routes->post('/products/store', 'Products::store');
    $routes->post('/products/update-stock', 'Products::updateStock');

    // Shared stock logs
    $routes->get('stock-logs', 'StockLogsController::index');
    $routes->get('stock-logs/export-excel', 'StockLogsController::exportExcel');
    $routes->get('stock-logs/export-pdf', 'StockLogsController::exportPdf');

    // Browser-only interface settings are available to both Admin and Cashier.
    $routes->get('settings', 'Admin\Settings::index');

    $routes->get('admin/expiry-report', 'Admin\ReportsController::expiryReport');
    $routes->get('admin/expiry-report/item/(:num)', 'ExpiryStockController::show/$1');
    $routes->post('admin/expiry-report/item/(:num)', 'ExpiryStockController::save/$1');
    $routes->get('admin/expiry-report/export-excel', 'Admin\ReportsController::exportExpiryExcel');
    $routes->get('admin/expiry-report/export-pdf', 'Admin\ReportsController::exportExpiryPdf');
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes
|--------------------------------------------------------------------------
*/
$routes->group('', ['filter' => 'admin'], function ($routes) {
    $routes->get('/dashboard', 'Dashboard::index');

    // Admin-only product management actions
    $routes->get('/products/edit/(:num)', 'Products::edit/$1');
    $routes->post('/products/update/(:num)', 'Products::update/$1');
    $routes->post('/products/delete/(:num)', 'Products::delete/$1');

    $routes->get('/categories', 'Categories::index');
    $routes->get('/categories/create', 'Categories::create');
    $routes->post('/categories/store', 'Categories::store');
    $routes->get('/categories/edit/(:num)', 'Categories::edit/$1');
    $routes->post('/categories/update/(:num)', 'Categories::update/$1');
    $routes->post('/categories/delete/(:num)', 'Categories::delete/$1');

    $routes->get('admin/reports', 'Admin\ReportsController::index');
    $routes->post('admin/reports/update-forecast', 'Admin\ReportsController::updateForecast');
    $routes->get('admin/reports/export-excel', 'Admin\ReportsController::exportExcel');
    $routes->get('admin/reports/pdf', 'Admin\ReportsController::exportPdf');

    $routes->get('admin/users', 'Admin\Users::index');
    $routes->get('admin/users/create', 'Admin\Users::create');
    $routes->post('admin/users/store', 'Admin\Users::store');
    $routes->get('admin/users/edit/(:num)', 'Admin\Users::edit/$1');
    $routes->post('admin/users/update/(:num)', 'Admin\Users::update/$1');
    $routes->post('admin/users/deactivate/(:num)', 'Admin\Users::deactivate/$1');
    $routes->post('admin/users/activate/(:num)', 'Admin\Users::activate/$1');
    $routes->get('admin/users/password/(:num)', 'Admin\Users::password/$1');
    $routes->post('admin/users/password-update/(:num)', 'Admin\Users::passwordUpdate/$1');

    $routes->get('admin/activity-logs', 'Admin\Users::activityLogs');

    // System settings
    $routes->get('admin/settings', 'Admin\Settings::index');

    // Database backup and recovery
    $routes->get('admin/backup-restore', 'Admin\BackupRestore::index');
    $routes->post('admin/backup-restore/create', 'Admin\BackupRestore::create');
    $routes->get('admin/backup-restore/download/(:segment)', 'Admin\BackupRestore::download/$1');
    $routes->post('admin/backup-restore/delete/(:segment)', 'Admin\BackupRestore::delete/$1');
    $routes->post('admin/backup-restore/restore', 'Admin\BackupRestore::restore');

    // Branch stock is managed from the Products page.

    // Expiry report routes are defined once in the shared Admin/Cashier group above.

    $routes->get('admin/branches', 'Admin\Branches::index');
    $routes->get('admin/branches/create', 'Admin\Branches::create');
    $routes->post('admin/branches/store', 'Admin\Branches::store');
    $routes->get('admin/branches/edit/(:num)', 'Admin\Branches::edit/$1');
    $routes->post('admin/branches/update/(:num)', 'Admin\Branches::update/$1');

    // Manual Sale Correction (Override)
    $routes->get('admin/sale-correction/(:num)', 'Admin\SaleCorrection::edit/$1');
    $routes->post('admin/sale-correction/update/(:num)', 'Admin\SaleCorrection::update/$1');

    // Discounts
    $routes->get('admin/discounts',                'Admin\\Discounts::index');
    $routes->get('admin/discounts/create',         'Admin\\Discounts::create');
    $routes->post('admin/discounts/store',         'Admin\\Discounts::store');
    $routes->get('admin/discounts/edit/(:num)',    'Admin\\Discounts::edit/$1');
    $routes->post('admin/discounts/update/(:num)', 'Admin\\Discounts::update/$1');
    $routes->post('admin/discounts/toggle/(:num)',  'Admin\\Discounts::toggleStatus/$1');
    $routes->post('admin/discounts/delete/(:num)',  'Admin\\Discounts::delete/$1');

    // Suppliers
    $routes->get('admin/suppliers', 'Admin\Suppliers::index');
    $routes->get('admin/suppliers/create', 'Admin\Suppliers::create');
    $routes->post('admin/suppliers/store', 'Admin\Suppliers::store');
    $routes->get('admin/suppliers/edit/(:num)', 'Admin\Suppliers::edit/$1');
    $routes->post('admin/suppliers/update/(:num)', 'Admin\Suppliers::update/$1');
    $routes->post('admin/suppliers/delete/(:num)', 'Admin\Suppliers::delete/$1');
    $routes->get('admin/suppliers/trash', 'Admin\Suppliers::trash');
    $routes->post('admin/suppliers/restore/(:num)', 'Admin\Suppliers::restore/$1');
    $routes->post('admin/suppliers/force-delete/(:num)', 'Admin\Suppliers::forceDelete/$1');

    // Trash: Products
    $routes->get('/products/trash', 'Products::trash');
    $routes->post('/products/restore/(:num)', 'Products::restore/$1');
    $routes->post('/products/force-delete/(:num)', 'Products::forceDelete/$1');

    // Trash: Categories
    $routes->get('/categories/trash', 'Categories::trash');
    $routes->post('/categories/restore/(:num)', 'Categories::restore/$1');
    $routes->post('/categories/force-delete/(:num)', 'Categories::forceDelete/$1');

    // Trash: Discounts
    $routes->get('admin/discounts/trash', 'Admin\\Discounts::trash');
    $routes->post('admin/discounts/restore/(:num)', 'Admin\\Discounts::restore/$1');
    $routes->post('admin/discounts/force-delete/(:num)', 'Admin\\Discounts::forceDelete/$1');
});
