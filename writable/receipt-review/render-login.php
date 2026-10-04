<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
foreach (['normal', 'error', 'success', 'subfolder'] as $state) {
    session()->remove(['error', 'success', '_ci_old_input']);
    $_SERVER['SCRIPT_NAME'] = $state === 'subfolder' ? '/pharmacy/index.php' : '/index.php';
    if ($state === 'error') {
        session()->setFlashdata('error', 'Invalid username or password. Please check your details and try again.');
        session()->setFlashdata('_ci_old_input', ['post' => ['username' => 'staff"<test>'], 'get' => []]);
    }
    if ($state === 'success') session()->setFlashdata('success', 'You have been signed out successfully.');
    $html = view('auth/login', [], ['saveData' => false]);
    $html = preg_replace('~https?://[^"\s]+/assets/~', '/assets/', $html);
    file_put_contents(__DIR__ . '/login-' . $state . '.html', $html);
}
echo "Rendered four login states.\n";
