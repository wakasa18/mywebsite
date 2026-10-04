<?php

namespace App\Controllers\Cashier;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\SalesModel;
use App\Models\SaleItemModel;
use App\Models\StockLogModel;
use App\Models\DiscountModel;
use App\Models\BranchProductModel;
use App\Models\ActivityLogModel;
use App\Models\RefundItemModel;

class SalesController extends BaseController
{
    protected $productModel;
    protected $salesModel;
    protected $saleItemModel;
    protected $stockLogModel;
    protected $discountModel;
    protected $branchProductModel;
    protected $db;
    protected $activityLogModel;
    protected $refundItemModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->salesModel = new SalesModel();
        $this->saleItemModel = new SaleItemModel();
        $this->stockLogModel = new StockLogModel();
        $this->discountModel = new DiscountModel();
        $this->branchProductModel = new BranchProductModel();
        $this->db = \Config\Database::connect();
        $this->activityLogModel = new ActivityLogModel();
        $this->refundItemModel = new RefundItemModel();
    }

    /** Read-only customer referral lookup; never changes the cart or selling branch. */
    public function branchAvailability()
    {
        $role = (string) session('role');
        $branchId = (int) session('branch_id');
        if (!session('isLoggedIn') || !in_array($role, ['admin', 'cashier'], true)
            || ($role === 'cashier' && $branchId <= 0)) {
            return $this->response->setStatusCode(403)->setBody('Branch availability is not available for this account.');
        }
        $raw = $this->request->getGet('q');
        $keyword = is_string($raw) ? mb_substr(trim($raw), 0, 80) : '';
        $rawPage = $this->request->getGet('page');
        $page = is_scalar($rawPage) && ctype_digit((string) $rawPage) ? max(1, min(100000, (int) $rawPage)) : 1;
        $rows = [];
        $total = 0;
        if ($keyword !== '') {
            $builder = $this->db->table('branch_products bp')
                ->select('p.product_name, p.sku, p.unit, b.branch_name, b.address, b.contact_number, bp.stock')
                ->join('products p', 'p.id = bp.product_id')
                ->join('branches b', 'b.id = bp.branch_id')
                ->where('p.status', 'active')->where('p.deleted_at', null)->where('p.is_deleted', 0)->where('p.is_permanently_deleted', 0)
                ->where('bp.status', 'active')->where('bp.deleted_at', null)->where('bp.is_deleted', 0)->where('bp.is_permanently_deleted', 0)->where('b.status', 'active')->where('bp.stock >', 0)
                ->groupStart()->where('bp.expiration_date', null)->orWhere('bp.expiration_date >=', date('Y-m-d'))->groupEnd()
                ->groupStart()->like('p.product_name', $keyword)->orLike('p.sku', $keyword)->groupEnd();
            if ($branchId > 0) $builder->where('bp.branch_id !=', $branchId);
            $total = $builder->countAllResults(false);
            $page = min($page, max(1, (int) ceil($total / 20)));
            $rows = $builder->orderBy('p.product_name', 'ASC')->orderBy('b.branch_name', 'ASC')
                ->orderBy('bp.id', 'ASC')->get(20, ($page - 1) * 20)->getResultArray();
        }
        return view('cashier/sales/branch_availability', [
            'title' => 'Other branch availability', 'keyword' => $keyword, 'availability' => $rows,
            'total' => $total, 'page' => $page,
        ]);
    }

    public function index()
    {
        $branchId = session('branch_id');
        $keyword = trim((string) $this->request->getGet('q'));
        $today = date('Y-m-d');

        if (empty($branchId)) {
            return redirect()->back()->with('error', 'No branch assigned to this user.');
        }

        $builder = $this->branchProductModel
            ->select('
                branch_products.id AS branch_product_id,
                branch_products.branch_id,
                branch_products.product_id,
                branch_products.stock,
                branch_products.reorder_level,
                branch_products.price,
                branch_products.cost_price,
                branch_products.expiration_date,
                branch_products.status AS branch_status,
                products.product_name,
                products.sku,
                products.unit,
                products.category_id
            ')
            ->join('products', 'products.id = branch_products.product_id')
            ->where('branch_products.branch_id', $branchId)
            ->where('branch_products.status', 'active')->where('branch_products.deleted_at', null)->where('branch_products.is_deleted', 0)->where('branch_products.is_permanently_deleted', 0)
            ->where('products.status', 'active')->where('products.deleted_at', null)->where('products.is_deleted', 0)->where('products.is_permanently_deleted', 0)
            ->where('branch_products.stock >', 0)
            ->groupStart()
            ->where('branch_products.expiration_date IS NULL', null, false)
            ->orWhere('branch_products.expiration_date >=', $today)
            ->groupEnd();

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('products.product_name', $keyword)
                ->orLike('products.sku', $keyword)
                ->groupEnd();
        }

        $products = $builder
            ->orderBy('branch_products.expiration_date', 'ASC')
            ->orderBy('products.product_name', 'ASC')
            ->findAll();

        $cart = session()->get('cart') ?? [];

        // Older carts may not contain category_id yet. Enrich them so the
        // checkout preview can correctly calculate category-scoped discounts.
        if (!empty($cart)) {
            $productIds = array_values(array_unique(array_map(
                static fn ($item) => (int) ($item['product_id'] ?? 0),
                $cart
            )));
            $productIds = array_values(array_filter($productIds));

            if (!empty($productIds)) {
                $categoryRows = $this->productModel
                    ->select('id, category_id')
                    ->whereIn('id', $productIds)
                    ->findAll();

                $categoryByProduct = [];
                foreach ($categoryRows as $row) {
                    $categoryByProduct[(int) $row['id']] = (int) $row['category_id'];
                }

                foreach ($cart as &$cartItem) {
                    $cartProductId = (int) ($cartItem['product_id'] ?? 0);
                    $cartItem['category_id'] = $categoryByProduct[$cartProductId] ?? null;
                }
                unset($cartItem);

                session()->set('cart', $cart);
            }
        }

        $discounts = $this->discountModel
            ->select('discounts.*, categories.category_name, target_products.product_name AS target_product_name')
            ->join('categories', 'categories.id = discounts.category_id', 'left')
            ->join('products AS target_products', 'target_products.id = discounts.product_id', 'left')
            ->where('discounts.status', 'active')
            ->groupStart()
            ->where('discounts.start_date IS NULL', null, false)
            ->orWhere('discounts.start_date <=', $today)
            ->groupEnd()
            ->groupStart()
            ->where('discounts.end_date IS NULL', null, false)
            ->orWhere('discounts.end_date >=', $today)
            ->groupEnd()
            ->groupStart()
                ->where('discounts.applies_to', 'all')
                ->orGroupStart()
                    ->where('discounts.applies_to', 'category')
                    ->where('discounts.category_id IS NOT NULL', null, false)
                    ->where('categories.deleted_at IS NULL', null, false)->where('categories.is_deleted', 0)->where('categories.is_permanently_deleted', 0)
                ->groupEnd()
                ->orGroupStart()
                    ->where('discounts.applies_to', 'product')
                    ->where('discounts.product_id IS NOT NULL', null, false)
                    ->where('target_products.status', 'active')
                    ->where('target_products.deleted_at IS NULL', null, false)->where('target_products.is_deleted', 0)->where('target_products.is_permanently_deleted', 0)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('discounts.discount_name', 'ASC')
            ->findAll();

        return view('cashier/sales/index', [
            'products' => $products,
            'cart' => $cart,
            'keyword' => $keyword,
            'discounts' => $discounts,
            'heldSales' => session()->get('held_sales') ?? [],
        ]);
    }

    public function addToCart()
    {
        $branchId = session('branch_id');
        $productId = (int) $this->request->getPost('product_id');
        $qty = (int) $this->request->getPost('quantity');
        $today = date('Y-m-d');

        if (empty($branchId)) {
            return redirect()->back()->with('error', 'No branch assigned to this user.');
        }

        if ($qty <= 0) {
            return redirect()->back()->with('error', 'Invalid quantity.');
        }

        $branchProduct = $this->branchProductModel
            ->select('
                branch_products.*,
                products.product_name,
                products.sku,
                products.unit,
                products.category_id,
                products.status AS product_status
            ')
            ->join('products', 'products.id = branch_products.product_id')
            ->where('branch_products.branch_id', $branchId)
            ->where('branch_products.product_id', $productId)
            ->where('branch_products.status', 'active')->where('branch_products.deleted_at', null)->where('branch_products.is_deleted', 0)->where('branch_products.is_permanently_deleted', 0)
            ->where('products.status', 'active')->where('products.deleted_at', null)->where('products.is_deleted', 0)->where('products.is_permanently_deleted', 0)
            ->first();

        if (!$branchProduct) {
            return redirect()->back()->with('error', 'Product not found for this branch.');
        }

        if (!empty($branchProduct['expiration_date'])) {
            if ($branchProduct['expiration_date'] < $today) {
                return redirect()->back()->with('error', 'Product "' . $branchProduct['product_name'] . '" is already expired and cannot be sold.');
            }

            $daysLeft = floor((strtotime($branchProduct['expiration_date']) - strtotime($today)) / 86400);

            if ($daysLeft <= 30) {
                session()->setFlashdata(
                    'warning',
                    'Warning: Product "' . $branchProduct['product_name'] . '" is near expiry (' . $daysLeft . ' day(s) left).'
                );
            }
        }

        if ((int) $branchProduct['stock'] < $qty) {
            return redirect()->back()->with('error', 'Not enough stock.');
        }

        $cart = session()->get('cart') ?? [];

        if (isset($cart[$productId])) {
            $newQty = $cart[$productId]['quantity'] + $qty;

            if ($newQty > (int) $branchProduct['stock']) {
                return redirect()->back()->with('error', 'Quantity exceeds stock.');
            }

            $cart[$productId]['quantity'] = $newQty;
            $cart[$productId]['subtotal'] = $newQty * $cart[$productId]['price'];
            $cart[$productId]['stock'] = (int) $branchProduct['stock'];
        } else {
            $cart[$productId] = [
                'product_id' => (int) $branchProduct['product_id'],
                'branch_product_id' => (int) $branchProduct['id'],
                'product_name' => $branchProduct['product_name'],
                'category_id' => (int) $branchProduct['category_id'],
                'cost_price' => (float) $branchProduct['cost_price'],
                'price' => (float) $branchProduct['price'],
                'quantity' => $qty,
                'subtotal' => (float) $branchProduct['price'] * $qty,
                'stock' => (int) $branchProduct['stock'],
            ];
        }

        session()->set('cart', $cart);

        return redirect()->to(site_url('cashier/sales'))->with('success', 'Item added to cart.');
    }

    public function updateCart()
    {
        $branchId = session('branch_id');
        $productId = (int) $this->request->getPost('product_id');
        $qty = (int) $this->request->getPost('quantity');
        $today = date('Y-m-d');

        if (empty($branchId)) {
            return redirect()->back()->with('error', 'No branch assigned to this user.');
        }

        $cart = session()->get('cart') ?? [];

        if (!isset($cart[$productId])) {
            return redirect()->back()->with('error', 'Cart item not found.');
        }

        $branchProduct = $this->branchProductModel
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->first();

        if (!$branchProduct) {
            return redirect()->back()->with('error', 'Product no longer exists in this branch.');
        }

        if (!empty($branchProduct['expiration_date']) && $branchProduct['expiration_date'] < $today) {
            return redirect()->back()->with('error', 'Cannot update cart. Product is already expired.');
        }

        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            if ($qty > (int) $branchProduct['stock']) {
                return redirect()->back()->with('error', 'Quantity exceeds stock.');
            }

            $cart[$productId]['quantity'] = $qty;
            $cart[$productId]['subtotal'] = $qty * $cart[$productId]['price'];
            $cart[$productId]['stock'] = (int) $branchProduct['stock'];
        }

        session()->set('cart', $cart);

        return redirect()->to(site_url('cashier/sales'))->with('success', 'Cart updated.');
    }

    public function syncCart()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $cart = session()->get('cart') ?? [];
        $items = $this->request->getPost('items') ?? [];

        foreach ($items as $productId => $qty) {
            $productId = (int) $productId;
            $qty       = (int) $qty;
            if (isset($cart[$productId])) {
                if ($qty <= 0) {
                    unset($cart[$productId]);
                } else {
                    $cart[$productId]['quantity'] = $qty;
                    $cart[$productId]['subtotal'] = $qty * $cart[$productId]['price'];
                }
            }
        }

        session()->set('cart', $cart);

        // Return a fresh CSRF token so the JS on the POS page can update
        // all embedded form inputs before the next submission.
        $security  = \Config\Services::security();
        $tokenName = $security->getTokenName();
        $tokenHash = $security->getHash();

        return $this->response->setJSON([
            'success'    => true,
            'csrf_name'  => $tokenName,
            'csrf_hash'  => $tokenHash,
        ]);
    }

    public function clearCart()
    {
        session()->remove('cart');
        return redirect()->to(site_url('cashier/sales'))->with('success', 'Cart cleared.');
    }

    /**
     * Parks the current cart aside (into session held_sales) and empties
     * the active cart, so the counter is immediately ready for the next
     * customer without losing what was already rung up.
     */
    public function holdSale()
    {
        $cart = session()->get('cart') ?? [];

        if (empty($cart)) {
            return redirect()->to(site_url('cashier/sales'))->with('error', 'Cart is empty — nothing to hold.');
        }

        $label = trim((string) $this->request->getPost('label'));
        $heldSales = session()->get('held_sales') ?? [];

        $heldSales[] = [
            'cart' => $cart,
            'label' => $label !== '' ? $label : null,
            'held_at' => date('Y-m-d H:i:s'),
        ];

        session()->set('held_sales', $heldSales);
        session()->remove('cart');

        return redirect()->to(site_url('cashier/sales'))->with('success', 'Sale held. Cart is ready for the next customer.');
    }

    /**
     * Swaps a held sale back into the active cart. Requires the active
     * cart to already be empty — rather than silently auto-holding
     * whatever's currently in progress, this forces an explicit choice
     * so nothing ever gets parked without the cashier meaning to.
     */
    public function resumeHeldSale($index)
    {
        $heldSales = session()->get('held_sales') ?? [];
        $index = (int) $index;

        if (!isset($heldSales[$index])) {
            return redirect()->to(site_url('cashier/sales'))->with('error', 'That held sale no longer exists.');
        }

        $currentCart = session()->get('cart') ?? [];
        if (!empty($currentCart)) {
            return redirect()->to(site_url('cashier/sales'))->with('error', 'Hold or clear your current cart before resuming a held sale.');
        }

        session()->set('cart', $heldSales[$index]['cart']);
        unset($heldSales[$index]);
        session()->set('held_sales', array_values($heldSales));

        return redirect()->to(site_url('cashier/sales'))->with('success', 'Held sale resumed.');
    }

    public function discardHeldSale($index)
    {
        $heldSales = session()->get('held_sales') ?? [];
        $index = (int) $index;

        if (isset($heldSales[$index])) {
            unset($heldSales[$index]);
            session()->set('held_sales', array_values($heldSales));
        }

        return redirect()->to(site_url('cashier/sales'))->with('success', 'Held sale discarded.');
    }

    public function removeCartItem()
    {
        $productId = (int) $this->request->getPost('product_id');
        $cart = session()->get('cart') ?? [];

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            session()->set('cart', $cart);
        }

        return redirect()->to(site_url('cashier/sales'))->with('success', 'Item removed from cart.');
    }

    public function checkout()
    {
        $branchId = session('branch_id');
        $userId = session('user_id');
        $today = date('Y-m-d');

        if (empty($branchId)) {
            return redirect()->back()->with('error', 'No branch assigned to this user.');
        }
        $activeBranch = $this->db->table('branches')
            ->where('id', (int) $branchId)
            ->where('status', 'active')
            ->countAllResults() > 0;
        if (!$activeBranch) {
            return redirect()->back()->with('error', 'Your assigned branch is inactive. Ask an administrator to review your account.');
        }

        $sessionCart = session()->get('cart') ?? [];
        $postedItems = $this->request->getPost('items');

        if (empty($sessionCart)) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        if (empty($postedItems) || !is_array($postedItems)) {
            return redirect()->back()->with('error', 'No checkout items submitted.');
        }

        // ── Idempotency and interrupted-checkout recovery ────────────────
        // The raw token never enters the database. Only its SHA-256 hash is
        // stored, allowing a browser that lost its connection to confirm
        // whether this exact checkout already completed before retrying.
        $postedToken  = trim((string) $this->request->getPost('_checkout_token'));
        $sessionToken = (string) (session()->get('checkout_token') ?? '');

        if (!preg_match('/^[a-f0-9]{32}$/i', $postedToken)) {
            return redirect()->to(site_url('cashier/sales'))
                ->with('error', 'The checkout request is invalid. Refresh the POS page and try again.');
        }

        $recoveryAvailable = $this->db->fieldExists('checkout_token_hash', 'sales');
        $checkoutTokenHash = $recoveryAvailable ? hash('sha256', $postedToken) : null;

        if ($checkoutTokenHash !== null) {
            $completedSale = $this->salesModel
                ->select('id')
                ->where('checkout_token_hash', $checkoutTokenHash)
                ->where('user_id', (int) $userId)
                ->where('branch_id', (int) $branchId)
                ->first();

            if ($completedSale) {
                session()->remove('cart');
                session()->set('checkout_token', bin2hex(random_bytes(16)));

                return redirect()->to(site_url('cashier/sales/completed/' . (int) $completedSale['id']))
                    ->with('success', 'This sale was already completed. No duplicate sale was created.');
            }
        }

        if ($sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
            return redirect()->to(site_url('cashier/sales'))
                ->with('error', 'This checkout request is no longer active. Check Sales History before trying again.');
        }

        // On older databases without the migration, retain the previous
        // one-time behavior. After migration, the unique token hash safely
        // handles retries and simultaneous submissions at database level.
        if (!$recoveryAvailable) {
            session()->remove('checkout_token');
        }
        // ──────────────────────────────────────────────────────────────────

        $cart = [];

        foreach ($postedItems as $productId => $rawQty) {
            $productId = (int) $productId;
            $rawQty = is_scalar($rawQty) ? trim((string) $rawQty) : '';
            $validatedQty = filter_var($rawQty, FILTER_VALIDATE_INT);
            if ($validatedQty === false || $validatedQty < 0) {
                return redirect()->back()->with('error', 'Cart quantities must be whole numbers of 0 or greater.');
            }
            $qty = (int) $validatedQty;

            if ($qty === 0) {
                continue;
            }

            if (!isset($sessionCart[$productId])) {
                continue;
            }

            $branchProduct = $this->branchProductModel
                ->select('
                    branch_products.*,
                    products.product_name,
                    products.category_id,
                    products.status AS product_status
                ')
                ->join('products', 'products.id = branch_products.product_id')
                ->where('branch_products.branch_id', $branchId)
                ->where('branch_products.product_id', $productId)
                ->where('branch_products.status', 'active')->where('branch_products.deleted_at', null)->where('branch_products.is_deleted', 0)->where('branch_products.is_permanently_deleted', 0)
                ->where('products.status', 'active')->where('products.deleted_at', null)->where('products.is_deleted', 0)->where('products.is_permanently_deleted', 0)
                ->first();

            if (!$branchProduct) {
                return redirect()->back()->with('error', 'A product no longer exists in this branch.');
            }

            if (!empty($branchProduct['expiration_date']) && $branchProduct['expiration_date'] < $today) {
                return redirect()->back()->with('error', 'Cannot checkout. Product "' . $branchProduct['product_name'] . '" is expired.');
            }

            if ($qty > (int) $branchProduct['stock']) {
                return redirect()->back()->with('error', 'Quantity exceeds stock for ' . $branchProduct['product_name']);
            }

            $cart[$productId] = [
                'product_id' => (int) $branchProduct['product_id'],
                'branch_product_id' => (int) $branchProduct['id'],
                'product_name' => $branchProduct['product_name'],
                'category_id' => (int) $branchProduct['category_id'],
                'cost_price' => (float) $branchProduct['cost_price'],
                'price' => (float) $branchProduct['price'],
                'quantity' => $qty,
                'subtotal' => (float) $branchProduct['price'] * $qty,
                'stock' => (int) $branchProduct['stock'],
            ];
        }

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        $paymentMethod = trim((string) $this->request->getPost('payment_method'));
        $amountPaidRaw = trim((string) $this->request->getPost('amount_paid'));
        $referenceNo = trim((string) $this->request->getPost('reference_no'));
        $notes = trim((string) $this->request->getPost('notes'));
        $discountId = $this->request->getPost('discount_id') ? (int) $this->request->getPost('discount_id') : null;

        if (!in_array($paymentMethod, ['cash', 'gcash', 'card'], true)) {
            return redirect()->back()->with('error', 'Please select a valid payment method.');
        }
        if ($amountPaidRaw === '' || !is_numeric($amountPaidRaw) || (float) $amountPaidRaw < 0) {
            return redirect()->back()->with('error', 'Enter a valid non-negative amount paid.');
        }
        if (mb_strlen($referenceNo) > 100 || mb_strlen($notes) > 500) {
            return redirect()->back()->with('error', 'The payment reference or notes are too long.');
        }
        if ($paymentMethod === 'cash') {
            $referenceNo = '';
        }
        $amountPaid = round((float) $amountPaidRaw, 2);

        $totalAmount = 0;
        foreach ($cart as $item) {
            $totalAmount += (float) $item['subtotal'];
        }

        $discountAmount = 0.0;
        $eligibleSubtotal = 0.0;
        $eligibleItemIds = [];
        $itemDiscounts = array_fill_keys(array_keys($cart), 0.0);
        $finalTotal = round($totalAmount, 2);

        if ($discountId) {
            $selectedDiscount = $this->discountModel->find($discountId);

            if (!$selectedDiscount || $selectedDiscount['status'] !== 'active') {
                return redirect()->back()->with('error', 'The selected discount is no longer active.');
            }

            $startOk = empty($selectedDiscount['start_date']) || $selectedDiscount['start_date'] <= $today;
            $endOk = empty($selectedDiscount['end_date']) || $selectedDiscount['end_date'] >= $today;

            if (!$startOk || !$endOk) {
                return redirect()->back()->with('error', 'The selected discount is not valid on this date.');
            }

            $scope = $selectedDiscount['applies_to'] ?? 'all';

            foreach ($cart as $productId => $item) {
                $isEligible = false;

                if ($scope === 'all') {
                    $isEligible = true;
                } elseif ($scope === 'category') {
                    $discountCategoryId = (int) ($selectedDiscount['category_id'] ?? 0);
                    $isEligible = $discountCategoryId > 0
                        && (int) ($item['category_id'] ?? 0) === $discountCategoryId;
                } elseif ($scope === 'product') {
                    $discountProductId = (int) ($selectedDiscount['product_id'] ?? 0);
                    $isEligible = $discountProductId > 0
                        && (int) $item['product_id'] === $discountProductId;
                } else {
                    return redirect()->back()->with('error', 'The selected discount has an invalid scope.');
                }

                if ($isEligible) {
                    $eligibleItemIds[] = $productId;
                    $eligibleSubtotal += (float) $item['subtotal'];
                }
            }

            $eligibleSubtotal = round($eligibleSubtotal, 2);

            if ($eligibleSubtotal <= 0 || empty($eligibleItemIds)) {
                return redirect()->back()->with(
                    'error',
                    'The selected discount does not apply to any item in the cart.'
                );
            }

            $minimumPurchase = (float) ($selectedDiscount['minimum_purchase'] ?? 0);
            if ($eligibleSubtotal < $minimumPurchase) {
                return redirect()->back()->with(
                    'error',
                    'The matching items do not meet the discount minimum purchase of ₱'
                    . number_format($minimumPurchase, 2) . '.'
                );
            }

            $rule = \App\Libraries\DiscountPolicy::fromDiscount($selectedDiscount);
            if ($rule === null) {
                return redirect()->back()->with('error', 'The discount values are invalid. Ask an administrator to correct this discount.');
            }
            $discountAmount = \App\Libraries\DiscountPolicy::amount($eligibleSubtotal, $rule);
            $finalTotal = round($totalAmount - $discountAmount, 2);

            $eligibleSubtotals = [];
            foreach ($eligibleItemIds as $eligibleProductId) {
                $eligibleSubtotals[$eligibleProductId] = (float) $cart[$eligibleProductId]['subtotal'];
            }
            foreach (\App\Libraries\DiscountAllocator::allocate($eligibleSubtotals, $discountAmount) as $id => $allocated) {
                $itemDiscounts[$id] = $allocated;
            }
        }

        if ($amountPaid < $finalTotal) {
            return redirect()->back()->with('error', 'Insufficient payment.');
        }

        $changeAmount = $amountPaid - $finalTotal;
        // Microsecond-precision + random suffix prevents collisions even
        // if two requests land in the same second on a busy server.
        $invoiceNo = 'INV-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $this->db->transStart();
        $changedProductIds = [];

        $saleData = [
            'invoice_no' => $invoiceNo,
            'user_id' => $userId,
            'branch_id' => $branchId,
            'discount_id' => $discountId,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'payment_method' => $paymentMethod,
            'reference_no' => $referenceNo ?: null,
            'amount_paid' => $amountPaid,
            'change_amount' => $changeAmount,
            'status' => 'completed',
            'notes' => $notes,
            'sale_date' => date('Y-m-d H:i:s')
        ];

        if ($checkoutTokenHash !== null) {
            $saleData['checkout_token_hash'] = $checkoutTokenHash;
        }

        try {
            $saleId = $this->salesModel->insert($saleData, true);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            // A simultaneous retry can reach the unique token constraint
            // while the first request is completing. Return the first sale
            // instead of showing an error or creating another transaction.
            if ($checkoutTokenHash !== null) {
                $completedSale = $this->salesModel
                    ->select('id')
                    ->where('checkout_token_hash', $checkoutTokenHash)
                    ->where('user_id', (int) $userId)
                    ->where('branch_id', (int) $branchId)
                    ->first();

                if ($completedSale) {
                    session()->remove('cart');
                    session()->set('checkout_token', bin2hex(random_bytes(16)));

                    return redirect()->to(site_url('cashier/sales/completed/' . (int) $completedSale['id']))
                        ->with('success', 'This sale was already completed. No duplicate sale was created.');
                }
            }

            log_message('error', 'Checkout insert failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'The sale could not be saved. Your cart was kept so you can try again.');
        }

        if (!$saleId) {
            $this->db->transRollback();
            return redirect()->back()->with('error', 'Failed to save sale. Your cart was kept so you can try again.');
        }

        foreach ($cart as $item) {
            $branchProduct = $this->db->query(
                'SELECT * FROM branch_products WHERE id = ? AND branch_id = ? FOR UPDATE',
                [(int) $item['branch_product_id'], (int) $branchId]
            )->getRowArray();

            if (!$branchProduct) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'Branch inventory record not found.');
            }

            if ($branchProduct['status'] !== 'active' || (!empty($branchProduct['deleted_at']) || !empty($branchProduct['is_deleted']) || !empty($branchProduct['is_permanently_deleted']))) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'This product was deactivated in your branch. Remove it from the cart before checkout.');
            }

            if (!empty($branchProduct['expiration_date']) && $branchProduct['expiration_date'] < $today) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'Cannot checkout. Product "' . $item['product_name'] . '" is expired.');
            }

            if ((int) $branchProduct['stock'] < (int) $item['quantity']) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'Insufficient stock for ' . $item['product_name']);
            }

            $itemSubtotal = (float) $item['subtotal'];
            $itemDiscount = round((float) ($itemDiscounts[$item['product_id']] ?? 0), 2);

            $profit = (($item['price'] - $item['cost_price']) * $item['quantity']) - $itemDiscount;

            $this->saleItemModel->insert([
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'product_name_snapshot' => $item['product_name'],
                'cost_price_at_sale' => $item['cost_price'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $itemSubtotal,
                'discount_applied' => $itemDiscount,
                'profit' => $profit
            ]);

            $previousStock = (int) $branchProduct['stock'];
            $newStock = $previousStock - (int) $item['quantity'];

            $this->branchProductModel->update($branchProduct['id'], [
                'stock' => $newStock,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $changedProductIds[(int) $item['product_id']] = true;

            $this->stockLogModel->insert([
                'product_id' => $item['product_id'],
                'branch_id' => $branchId,
                'user_id' => $userId,
                'action_type' => 'stock_out',
                'quantity' => $item['quantity'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'remarks' => 'Sold via POS Invoice ' . $invoiceNo,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        foreach (array_keys($changedProductIds) as $changedProductId) {
            $this->syncMasterStock((int) $changedProductId);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Transaction failed.');
        }

        $this->activityLogModel->insert([
            'user_id' => $userId,
            'activity' => 'Completed sale: ' . $invoiceNo . ' — Total: ₱' . number_format($finalTotal, 2) . ' via ' . $paymentMethod,
            'log_time' => date('Y-m-d H:i:s'),
        ]);

        session()->remove('cart');

        // Issue a fresh token so the cashier can start the next sale
        session()->set('checkout_token', bin2hex(random_bytes(16)));

        return redirect()->to(site_url('cashier/sales/completed/' . $saleId))
            ->with('success', 'Sale completed successfully.');
    }

    /**
     * Check whether an interrupted checkout completed on the server.
     * Only the cashier/admin who submitted it in the same branch can see it.
     */
    public function checkoutStatus()
    {
        $token = trim((string) $this->request->getGet('token'));
        $userId = (int) session('user_id');
        $branchId = (int) session('branch_id');

        if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false,
                'found' => false,
                'message' => 'Invalid checkout recovery token.',
            ]);
        }

        if (!$this->db->fieldExists('checkout_token_hash', 'sales')) {
            return $this->response->setStatusCode(503)->setJSON([
                'ok' => false,
                'found' => false,
                'migration_required' => true,
                'message' => 'Checkout recovery is not ready. Run the latest database migration.',
            ]);
        }

        $sale = $this->salesModel
            ->select('id, invoice_no, status')
            ->where('checkout_token_hash', hash('sha256', $token))
            ->where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$sale) {
            return $this->response->setJSON([
                'ok' => true,
                'found' => false,
                'message' => 'No completed sale was found for this checkout yet.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'found' => true,
            'sale_id' => (int) $sale['id'],
            'invoice_no' => (string) ($sale['invoice_no'] ?? ''),
            'status' => (string) ($sale['status'] ?? 'completed'),
            'redirect_url' => site_url('cashier/sales/completed/' . (int) $sale['id']),
        ]);
    }

    /**
     * Show a simple transaction confirmation after checkout.
     * The printable receipt remains on its own dedicated page.
     */
    public function completed($saleId)
    {
        $branchId = session('branch_id');
        $role = session('role');

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left')
            ->where('sales.id', (int) $saleId);

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $branchId);
        }

        $sale = $builder->first();

        if (!$sale) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale record not found.');
        }

        if (\App\Libraries\ExchangeRecord::identity($sale)) {
            return redirect()->to(site_url('cashier/sales/receipt/' . (int) $saleId));
        }

        $itemSummary = $this->db->table('sale_items')
            ->select('COUNT(*) AS line_count, COALESCE(SUM(quantity), 0) AS total_quantity', false)
            ->where('sale_id', (int) $saleId)
            ->get()
            ->getRowArray();

        return view('cashier/sales/completed', [
            'sale' => $sale,
            'lineCount' => (int) ($itemSummary['line_count'] ?? 0),
            'totalQuantity' => (int) ($itemSummary['total_quantity'] ?? 0),
        ]);
    }

    /**
     * Show a normal sale-details page for Sales History.
     * Printing is intentionally kept in the separate receipt page.
     */
    public function details($saleId)
    {
        $saleId = (int) $saleId;
        $branchId = session('branch_id');
        $role = session('role');

        if ($saleId < 1) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale record not found.');
        }

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name, branches.address AS branch_address, branches.contact_number AS branch_contact, discounts.discount_name')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left')
            ->join('discounts', 'discounts.id = sales.discount_id', 'left')
            ->where('sales.id', $saleId);

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $branchId);
        }

        $sale = $builder->first();

        if (!$sale) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale record not found or you do not have access to it.');
        }

        $items = $this->saleItemModel
            ->select('sale_items.*, products.sku, products.unit')
            ->join('products', 'products.id = sale_items.product_id', 'left')
            ->where('sale_items.sale_id', $saleId)
            ->orderBy('sale_items.id', 'ASC')
            ->findAll();

        $refundHistory = $this->refundItemModel->getRefundHistory($saleId);
        $refundByItem = [];
        $totalRefundedAmount = 0.0;
        $totalRefundedQuantity = 0;

        foreach ($refundHistory as $refund) {
            $saleItemId = (int) ($refund['sale_item_id'] ?? 0);
            $qty = (int) ($refund['quantity_refunded'] ?? 0);
            $amount = (float) ($refund['refund_subtotal'] ?? 0);

            if (!isset($refundByItem[$saleItemId])) {
                $refundByItem[$saleItemId] = [
                    'quantity' => 0,
                    'amount' => 0.0,
                ];
            }

            $refundByItem[$saleItemId]['quantity'] += $qty;
            $refundByItem[$saleItemId]['amount'] += $amount;
            $totalRefundedQuantity += $qty;
            $totalRefundedAmount += $amount;
        }

        foreach ($items as &$item) {
            $itemId = (int) ($item['id'] ?? 0);
            $refund = $refundByItem[$itemId] ?? ['quantity' => 0, 'amount' => 0.0];
            $lineTotal = max(0, (float) ($item['subtotal'] ?? 0) - (float) ($item['discount_applied'] ?? 0));

            $item['line_total_after_discount'] = round($lineTotal, 2);
            $item['refunded_quantity'] = (int) $refund['quantity'];
            $item['refunded_amount'] = round((float) $refund['amount'], 2);
            $item['remaining_quantity'] = max(0, (int) ($item['quantity'] ?? 0) - (int) $refund['quantity']);
        }
        unset($item);

        return view('cashier/sales/details', [
            'sale' => $sale,
            'items' => $items,
            'refundHistory' => $refundHistory,
            'totalRefundedAmount' => round($totalRefundedAmount, 2),
            'totalRefundedQuantity' => $totalRefundedQuantity,
            'netAmountAfterRefunds' => round(max(0, (float) ($sale['final_total'] ?? 0) - $totalRefundedAmount), 2),
        ]);
    }

    public function receipt($saleId)
    {
        $branchId = session('branch_id');
        $role = session('role');

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name, branches.address AS branch_address, branches.contact_number AS branch_contact')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left')
            ->where('sales.id', $saleId);

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $branchId);
        }

        $sale = $builder->first();

        if (!$sale) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale record not found.');
        }

        $items = $this->saleItemModel
            ->where('sale_id', $saleId)
            ->findAll();

        // If the sale has been (partially) refunded, find the latest refund event
        // so the receipt view can link to the correct refund slip.
        $latestRefundTs = null;
        if (in_array($sale['status'], ['refunded', 'partially_refunded'], true)) {
            $latest = $this->refundItemModel
                ->selectMax('created_at', 'latest_ts')
                ->where('sale_id', $saleId)
                ->first();
            $latestRefundTs = $latest['latest_ts'] ?? null;
        }

        return view('cashier/sales/receipt', [
            'sale'           => $sale,
            'items'          => $items,
            'isReprint'      => false,
            'latestRefundTs' => $latestRefundTs,
            'autoPrint'      => $this->request->getGet('print') === '1',
        ]);
    }

    public function searchProducts()
    {
        $branchId = session('branch_id');
        $keyword = trim((string) $this->request->getGet('q'));
        $today = date('Y-m-d');

        if (empty($branchId)) {
            return view('cashier/sales/product_rows', [
                'products' => []
            ]);
        }

        $builder = $this->branchProductModel
            ->select('
                branch_products.id AS branch_product_id,
                branch_products.branch_id,
                branch_products.product_id,
                branch_products.stock,
                branch_products.reorder_level,
                branch_products.price,
                branch_products.cost_price,
                branch_products.expiration_date,
                branch_products.status AS branch_status,
                products.product_name,
                products.sku,
                products.unit,
                products.category_id
            ')
            ->join('products', 'products.id = branch_products.product_id')
            ->where('branch_products.branch_id', $branchId)
            ->where('branch_products.status', 'active')->where('branch_products.deleted_at', null)->where('branch_products.is_deleted', 0)->where('branch_products.is_permanently_deleted', 0)
            ->where('products.status', 'active')->where('products.deleted_at', null)->where('products.is_deleted', 0)->where('products.is_permanently_deleted', 0)
            ->where('branch_products.stock >', 0)
            ->groupStart()
            ->where('branch_products.expiration_date IS NULL', null, false)
            ->orWhere('branch_products.expiration_date >=', $today)
            ->groupEnd();

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('products.product_name', $keyword)
                ->orLike('products.sku', $keyword)
                ->groupEnd();
        }

        $products = $builder
            ->orderBy('branch_products.expiration_date', 'ASC')
            ->orderBy('products.product_name', 'ASC')
            ->findAll();

        return view('cashier/sales/product_rows', [
            'products' => $products
        ]);
    }

    public function history()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));
        $filterBranch = trim((string) $this->request->getGet('branch_id'));
        $filterPaymentMethod = trim((string) $this->request->getGet('payment_method'));
        $filterStatus = trim((string) $this->request->getGet('status'));

        $sessionBranchId = session('branch_id');
        $role = session('role');

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left');

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $sessionBranchId);
        } else {
            if ($filterBranch !== '') {
                $builder->where('sales.branch_id', (int) $filterBranch);
            }
        }

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('sales.invoice_no', $keyword)
                ->orLike('users.full_name', $keyword)
                ->orLike('sales.payment_method', $keyword)
                ->groupEnd();
        }

        if ($filterPaymentMethod !== '') {
            $builder->where('sales.payment_method', $filterPaymentMethod);
        }

        if ($filterStatus !== '') {
            $builder->where('sales.status', $filterStatus);
        }

        if ($dateFrom !== '') {
            $builder->where('DATE(sales.sale_date) >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $builder->where('DATE(sales.sale_date) <=', $dateTo);
        }

        $sales = $builder
            ->orderBy('sales.id', 'DESC')
            ->paginate(10);

        $pager = $this->salesModel->pager;

        $branches = [];
        if ($role === 'admin') {
            // Keep a selected inactive branch visible when reviewing its history.
            $branchOptions = $this->db->table('branches')->groupStart()
                ->where('status', 'active');
            if ($filterBranch !== '' && ctype_digit($filterBranch)) {
                $branchOptions->orWhere('id', (int) $filterBranch);
            }
            $branches = $branchOptions->groupEnd()
                ->orderBy('branch_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('cashier/sales/history', [
            'sales' => $sales,
            'pager' => $pager,
            'keyword' => $keyword,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branches' => $branches,
            'filterBranch' => $filterBranch,
            'filterPaymentMethod' => $filterPaymentMethod,
            'filterStatus' => $filterStatus,
        ]);
    }

    public function reprint($saleId)
    {
        $branchId = session('branch_id');
        $role = session('role');

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name, branches.address AS branch_address, branches.contact_number AS branch_contact')
            ->join('users', 'users.id = sales.user_id', 'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left')
            ->where('sales.id', $saleId);

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $branchId);
        }

        $sale = $builder->first();

        if (!$sale) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale record not found.');
        }

        $items = $this->saleItemModel
            ->where('sale_id', $saleId)
            ->findAll();

        $latestRefundTs = null;
        if (in_array($sale['status'], ['refunded', 'partially_refunded'], true)) {
            $latest = $this->refundItemModel
                ->selectMax('created_at', 'latest_ts')
                ->where('sale_id', $saleId)
                ->first();
            $latestRefundTs = $latest['latest_ts'] ?? null;
        }

        return view('cashier/sales/receipt', [
            'sale'           => $sale,
            'items'          => $items,
            'isReprint'      => true,
            'latestRefundTs' => $latestRefundTs,
            'autoPrint'      => $this->request->getGet('print') === '1',
        ]);
    }

    /**
     * Show the partial-refund form for a given sale.
     * Loads each sale item together with how many units have already been refunded
     * so the UI can clamp the max-refundable quantity per item.
     */
    public function refundForm(int $saleId)
    {
        $sessionBranchId = session('branch_id');
        $role            = session('role');

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name, branches.address AS branch_address, branches.contact_number AS branch_contact')
            ->join('users',    'users.id = sales.user_id',       'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left')
            ->where('sales.id', $saleId);

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $sessionBranchId);
        }

        $sale = $builder->first();

        if (!$sale) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale not found.');
        }

        if (!in_array($sale['status'], ['completed', 'partially_refunded'], true)) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Only completed or partially refunded sales can be refunded.');
        }

        $items = $this->saleItemModel->where('sale_id', $saleId)->findAll();

        // Annotate each item with the remaining refundable quantity and the
        // net amount actually paid per unit after item-level discounts.
        foreach ($items as &$item) {
            $item['already_refunded'] = $this->refundItemModel->totalRefundedQty((int) $item['id']);
            $item['refundable_qty'] = max(0, (int) $item['quantity'] - (int) $item['already_refunded']);
            $refundedAmountRow = $this->db->table('refund_items')
                ->selectSum('refund_subtotal', 'total_refunded_amount')
                ->where('sale_item_id', (int) $item['id'])
                ->get()
                ->getRowArray();
            $netItemTotal = round(max(0, (float) $item['subtotal'] - (float) ($item['discount_applied'] ?? 0)), 2);
            $remainingRefundAmount = round(max(0, $netItemTotal - (float) ($refundedAmountRow['total_refunded_amount'] ?? 0)), 2);
            $item['refund_unit_price'] = (int) $item['refundable_qty'] > 0
                ? round($remainingRefundAmount / (int) $item['refundable_qty'], 4)
                : 0.0;
        }
        unset($item);

        $refundHistory = $this->refundItemModel->getRefundHistory($saleId);
        $refundToken = bin2hex(random_bytes(24));
        session()->set('refund_token_' . $saleId, $refundToken);

        return view('cashier/sales/refund_partial', [
            'sale'          => $sale,
            'items'         => $items,
            'refundHistory' => $refundHistory,
            'refundToken'   => $refundToken,
        ]);
    }

    /**
     * Process a partial (or full) refund submission.
     *
     * Accepts an array of refund[item_id] => qty values.
     * Only items with qty > 0 are processed.
     * After processing, the sale status is set to:
     *   - 'refunded'           if every refundable unit has now been returned
     *   - 'partially_refunded' if at least one unit remains unreturned
     */
    public function refundPartial(int $saleId)
    {
        $sessionBranchId = session('branch_id');
        $role            = session('role');
        $userId          = session('user_id');

        $saleBuilder = $this->salesModel->where('id', $saleId);
        if ($role !== 'admin') {
            $saleBuilder->where('branch_id', $sessionBranchId);
        }
        $sale = $saleBuilder->first();

        if (!$sale) {
            return redirect()->back()->with('error', 'Sale not found.');
        }

        if (!in_array($sale['status'], ['completed', 'partially_refunded'], true)) {
            return redirect()->back()->with('error', 'Only completed or partially refunded sales can be refunded.');
        }

        $tokenKey = 'refund_token_' . $saleId;
        $postedToken = trim((string) $this->request->getPost('_refund_token'));
        $sessionToken = (string) (session()->get($tokenKey) ?? '');
        if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
            return redirect()->to(site_url('cashier/sales/refund-form/' . $saleId))
                ->with('error', 'This refund request was already submitted or expired. Review the quantities and submit again.');
        }
        session()->remove($tokenKey);

        // Validate input
        $rules = [
            'reason' => 'required|min_length[3]|max_length[500]',
            'refund_method' => 'required|in_list[cash,gcash,card]',
        ];
        if (!$this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', 'Enter a reason (at least 3 characters) and select the refund payment method.');
        }

        $reason       = trim($this->request->getPost('reason'));
        $refundQtys   = $this->request->getPost('refund') ?? [];
        $conditions = $this->request->getPost('return_condition');
        $conditions = is_array($conditions) ? $conditions : [];
        $refundMethod = (string) $this->request->getPost('refund_method');
        $saleBranchId = (int) $sale['branch_id'];

        if (empty($refundQtys) || !is_array($refundQtys)) {
            return redirect()->back()->with('error', 'No refund quantities submitted.');
        }

        // Validate each quantity before touching the DB
        $allSaleItems = $this->saleItemModel->where('sale_id', $saleId)->findAll();
        $saleItemMap  = array_column($allSaleItems, null, 'id');
        $toProcess    = [];

        foreach ($refundQtys as $itemId => $rawQty) {
            $itemId = (int) $itemId;
            $rawQty = is_scalar($rawQty) ? trim((string) $rawQty) : '';
            $validatedQty = filter_var($rawQty, FILTER_VALIDATE_INT);
            if ($validatedQty === false || $validatedQty < 0) {
                return redirect()->back()->withInput()->with('error', 'Refund quantities must be whole numbers of 0 or greater.');
            }
            $qty = (int) $validatedQty;

            if ($qty === 0) {
                continue;
            }

            if (!isset($saleItemMap[$itemId])) {
                return redirect()->back()->with('error', 'Invalid sale item submitted.');
            }

            $condition = $conditions[$itemId] ?? '';
            if (!is_string($condition) || !isset(\App\Libraries\ReturnCondition::LABELS[$condition])) {
                return redirect()->back()->withInput()->with('error', 'Select a return condition for every refunded item.');
            }
            $item = $saleItemMap[$itemId];
            $alreadyRefund = $this->refundItemModel->totalRefundedQty($itemId);
            $refundable = max(0, (int) $item['quantity'] - $alreadyRefund);

            if ($qty > $refundable) {
                return redirect()->back()->with('error',
                    'Refund qty for "' . $item['product_name_snapshot'] . '" exceeds the refundable amount (' . $refundable . ' remaining).'
                );
            }

            $refundedAmountRow = $this->db->table('refund_items')
                ->selectSum('refund_subtotal', 'total_refunded_amount')
                ->where('sale_item_id', $itemId)
                ->get()
                ->getRowArray();
            $alreadyRefundedAmount = round((float) ($refundedAmountRow['total_refunded_amount'] ?? 0), 2);
            $netItemTotal = round(max(0, (float) $item['subtotal'] - (float) ($item['discount_applied'] ?? 0)), 2);
            $remainingRefundAmount = round(max(0, $netItemTotal - $alreadyRefundedAmount), 2);
            $remainingUnitPrice = $refundable > 0 ? $remainingRefundAmount / $refundable : 0.0;
            $refundSubtotal = $qty === $refundable
                ? $remainingRefundAmount
                : round(min($remainingRefundAmount, $remainingUnitPrice * $qty), 2);

            $toProcess[] = [
                'item' => $item,
                'return_condition' => $condition,
                'itemId' => $itemId,
                'qty' => $qty,
                'refund_unit_price' => $qty > 0 ? round($refundSubtotal / $qty, 2) : 0.0,
                'refund_subtotal' => $refundSubtotal,
            ];
        }

        if (empty($toProcess)) {
            return redirect()->back()->with('error', 'Please enter at least one item quantity to refund.');
        }

        $this->db->transStart();

        // Serialize refunds and sale corrections for the same invoice. The
        // quantities and refundable peso amounts are recalculated after this
        // lock so a second browser session cannot refund the same units twice.
        $lockedSale = $this->db->query(
            'SELECT id, status FROM sales WHERE id = ? FOR UPDATE',
            [$saleId]
        )->getRowArray();
        if (!$lockedSale || !in_array($lockedSale['status'], ['completed', 'partially_refunded'], true)) {
            $this->db->transRollback();
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'This sale changed while the refund page was open. Review its latest status before trying again.');
        }

        foreach ($toProcess as &$entry) {
            $lockedItem = $this->db->query(
                'SELECT * FROM sale_items WHERE id = ? AND sale_id = ? FOR UPDATE',
                [(int) $entry['itemId'], $saleId]
            )->getRowArray();
            if (!$lockedItem) {
                $this->db->transRollback();
                return redirect()->to(site_url('cashier/sales/refund-form/' . $saleId))
                    ->with('error', 'A sale item is no longer available. Review the refund and try again.');
            }

            $alreadyRefund = $this->refundItemModel->totalRefundedQty((int) $entry['itemId']);
            $refundable = max(0, (int) $lockedItem['quantity'] - $alreadyRefund);
            if ((int) $entry['qty'] > $refundable) {
                $this->db->transRollback();
                return redirect()->to(site_url('cashier/sales/refund-form/' . $saleId))
                    ->with('error', 'The refundable quantity changed while this page was open. Review the remaining quantities and try again.');
            }

            $refundedAmountRow = $this->db->table('refund_items')
                ->selectSum('refund_subtotal', 'total_refunded_amount')
                ->where('sale_item_id', (int) $entry['itemId'])
                ->get()
                ->getRowArray();
            $alreadyRefundedAmount = round((float) ($refundedAmountRow['total_refunded_amount'] ?? 0), 2);
            $netItemTotal = round(max(0, (float) $lockedItem['subtotal'] - (float) ($lockedItem['discount_applied'] ?? 0)), 2);
            $remainingRefundAmount = round(max(0, $netItemTotal - $alreadyRefundedAmount), 2);
            $remainingUnitPrice = $refundable > 0 ? $remainingRefundAmount / $refundable : 0.0;
            $refundSubtotal = (int) $entry['qty'] === $refundable
                ? $remainingRefundAmount
                : round(min($remainingRefundAmount, $remainingUnitPrice * (int) $entry['qty']), 2);

            $entry['item'] = $lockedItem;
            $entry['refund_subtotal'] = $refundSubtotal;
            $entry['refund_unit_price'] = (int) $entry['qty'] > 0 ? round($refundSubtotal / (int) $entry['qty'], 2) : 0.0;
        }
        unset($entry);

        $changedProductIds = [];
        $now = date('Y-m-d H:i:s');
        $refundEventId = bin2hex(random_bytes(16));

        foreach ($toProcess as $entry) {
            $item = $entry['item'];
            $qty  = $entry['qty'];

            // Restore stock in branch inventory
            $branchProduct = $this->db->query(
                'SELECT * FROM branch_products WHERE product_id = ? AND branch_id = ? FOR UPDATE',
                [(int) $item['product_id'], $saleBranchId]
            )->getRowArray();

            if (!$branchProduct) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'Branch inventory record not found for "' . $item['product_name_snapshot'] . '".');
            }

            $previousStock = (int) $branchProduct['stock'];
            try {
                $restockQty = \App\Libraries\ReturnCondition::restockQuantity(
                    $entry['return_condition'], $qty, $branchProduct['expiration_date'] ?? null, date('Y-m-d')
                );
            } catch (\InvalidArgumentException $error) {
                $this->db->transRollback();
                return redirect()->back()->withInput()->with('error', $error->getMessage());
            }
            $newStock = $previousStock + $restockQty;

            if ($restockQty > 0) {

                $this->branchProductModel->update($branchProduct['id'], [
                    'stock'      => $newStock,
                    'updated_at' => $now,
                ]);
                $changedProductIds[(int) $item['product_id']] = true;

                $this->stockLogModel->insert([
                    'product_id'     => $item['product_id'],
                    'branch_id'      => $saleBranchId,
                    'user_id'        => $userId,
                    'action_type'    => 'returned',
                    'quantity'       => $qty,
                    'previous_stock' => $previousStock,
                    'new_stock'      => $newStock,
                    'remarks'        => 'Partial refund of ' . $sale['invoice_no'] . ' — ' . $qty . ' unit(s) of "' . $item['product_name_snapshot'] . '". Reason: ' . $reason,
                    'created_at'     => $now,
                ]);

            }

            // Non-resellable returns stay in the refund ledger, outside available stock.
            // Record the refund line item
            $this->refundItemModel->insert([
                'sale_id'               => $saleId,
                'sale_item_id'          => $entry['itemId'],
                'product_id'            => $item['product_id'],
                'product_name_snapshot' => $item['product_name_snapshot'],
                'quantity_refunded'     => $qty,
                'price_at_sale'         => $entry['refund_unit_price'],
                'refund_subtotal'       => $entry['refund_subtotal'],
                'refunded_by'           => $userId,
                'return_condition'      => $entry['return_condition'],
                'refund_method'         => $refundMethod,
                'refund_event_id'       => $refundEventId,
                'reason'                => $reason,
                'created_at'            => $now,
            ]);
        }

        foreach (array_keys($changedProductIds) as $changedProductId) {
            $this->syncMasterStock((int) $changedProductId);
        }

        // Recalculate whether all refundable units have now been returned
        // to determine the correct sale status
        $allSaleItems = $this->saleItemModel->where('sale_id', $saleId)->findAll();
        $fullyRefunded = true;
        foreach ($allSaleItems as $item) {
            $totalRefunded = $this->refundItemModel->totalRefundedQty((int) $item['id']);
            if ($totalRefunded < (int) $item['quantity']) {
                $fullyRefunded = false;
                break;
            }
        }

        $newStatus = $fullyRefunded ? 'refunded' : 'partially_refunded';
        $this->salesModel->update($saleId, [
            'status'     => $newStatus,
            'updated_at' => $now,
        ]);

        $refundAmountSum = array_reduce(
            $toProcess,
            static fn (float $carry, array $entry): float => $carry + (float) $entry['refund_subtotal'],
            0.0
        );

        $this->activityLogModel->insert([
            'user_id'  => $userId,
            'activity' => ($fullyRefunded ? 'Full' : 'Partial') . ' refund on ' . $sale['invoice_no']
                . ' — ₱' . number_format($refundAmountSum, 2)
                . ' — ' . count($toProcess) . ' item type(s)'
                . ' — Reason: ' . $reason,
            'log_time' => $now,
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Refund failed. Please try again.');
        }

        // The event ID keeps distinct payouts separate even within the same second.
        return redirect()->to(site_url('cashier/sales/refund-slip/' . $saleId . '?event=' . $refundEventId));
    }

    /**
     * Show the printable refund slip for a specific refund event.
     * ?ts=YYYY-MM-DD HH:MM:SS  — the created_at timestamp of the event,
     * set by refundPartial() immediately after it commits.
     */
    public function refundSlip(int $saleId)
    {
        $sessionBranchId = session('branch_id');
        $role            = session('role');

        $builder = $this->salesModel
            ->select('sales.*, users.full_name, branches.branch_name, branches.address AS branch_address, branches.contact_number AS branch_contact')
            ->join('users',    'users.id = sales.user_id',       'left')
            ->join('branches', 'branches.id = sales.branch_id', 'left')
            ->where('sales.id', $saleId);

        if ($role !== 'admin') {
            $builder->where('sales.branch_id', $sessionBranchId);
        }

        $sale = $builder->first();

        if (!$sale) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Sale not found.');
        }

        // New payouts use an event ID; timestamps remain supported for older links.
        $ts = $this->request->getGet('ts') ?? '';
        $refundItemsQuery = $this->refundItemModel
            ->select('refund_items.*, users.full_name AS refunded_by_name')
            ->join('users', 'users.id = refund_items.refunded_by', 'left')
            ->where('refund_items.sale_id', $saleId);

        $eventId = (string) ($this->request->getGet('event') ?? '');
        if ($eventId !== '') {
            $refundItemsQuery->where('refund_items.refund_event_id', $eventId);
        } elseif ($ts) {
            $refundItemsQuery->where('refund_items.created_at', $ts);
        } else {
            // Use a separate builder so the sale/branch scope above is preserved.
            $latest = $this->db->table('refund_items')
                ->select('created_at, refund_event_id')
                ->where('sale_id', $saleId)
                ->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (!empty($latest['refund_event_id'])) {
                $refundItemsQuery->where('refund_items.refund_event_id', $latest['refund_event_id']);
            } elseif ($latest) {
                $refundItemsQuery->where('refund_items.created_at', $latest['created_at'])
                    ->where('refund_items.refund_event_id', null);
            }
        }

        $refundItems = $refundItemsQuery->findAll();

        if (empty($refundItems)) {
            return redirect()->to(site_url('cashier/sales/history'))
                ->with('error', 'Refund slip not found.');
        }

        $refundTotal = array_sum(array_column($refundItems, 'refund_subtotal'));
        $reason      = $refundItems[0]['reason'] ?? '';
        $refundedBy  = $refundItems[0]['refunded_by_name'] ?? '—';
        $refundedAt  = $refundItems[0]['created_at'] ?? date('Y-m-d H:i:s');

        $exchangeSale = null;
        $exchange = null;
        if (!empty($refundItems[0]['refund_event_id'])) {
            $exchangeSale = $this->salesModel->where('invoice_no', 'EXC-' . $saleId . '-' . $refundItems[0]['refund_event_id'])
                ->where('branch_id', $sale['branch_id'])->first();
            if ($exchangeSale) $exchange = \App\Libraries\ExchangeRecord::forSale($exchangeSale, $this->db);
        }

        return view('cashier/sales/refund_slip', [
            'sale'        => $sale,
            'refundItems' => $refundItems,
            'refundTotal' => $refundTotal,
            'exchange' => $exchange,
            'exchangeSale' => $exchangeSale,
            'reason'      => $reason,
            'refundedBy'  => $refundedBy,
            'refundedAt'  => $refundedAt,
        ]);
    }

    private function syncMasterStock(int $productId): void
    {
        $row = $this->db->table('branch_products')
            ->selectSum('stock', 'total_stock')
            ->where('product_id', $productId)
            ->get()
            ->getRowArray();

        $this->db->table('products')->where('id', $productId)->update([
            'stock' => (int) ($row['total_stock'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Legacy full-refund kept for backward compatibility.
     * Now redirects to the partial-refund form so all refunds go through
     * the same item-selection flow.
     */
    public function refund($saleId)
    {
        return redirect()->to(site_url('cashier/sales/refund-form/' . $saleId));
    }
}
