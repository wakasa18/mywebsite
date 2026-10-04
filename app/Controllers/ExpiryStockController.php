<?php

namespace App\Controllers;

use App\Libraries\ExpiryStockResolution;

class ExpiryStockController extends BaseController
{
    public function show(int $id)
    {
        $action = $this->request->getGet('action') ?? 'history';
        if (!is_string($action)) return $this->response->setStatusCode(400)->setBody('Invalid action.');
        return $this->render($id, $action);
    }

    private function render(int $id, string $action, array $input = [], ?string $error = null)
    {
        $service = new ExpiryStockResolution(db_connect());
        try {
            if ($action !== 'history') ExpiryStockResolution::authorize((string) session('role'), $action);
            $row = $service->inventory($id, (string) session('role'), (int) session('branch_id'));
        } catch (\DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(esc($e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            return redirect()->to(site_url('admin/expiry-report'))->with('error', $e->getMessage());
        }
        $history = $service->history($row);
        $token = '';
        if ($action !== 'history') {
            $token = bin2hex(random_bytes(24));
            session()->set('expiry_action_' . $id . '_' . $action, ['token' => $token, 'revision' => ExpiryStockResolution::revision($row)]);
        }
        return view('admin/reports/expiry_action', compact('row', 'action', 'input', 'error', 'history', 'token'));
    }

    public function save(int $id)
    {
        $action = $this->request->getPost('resolution_action');
        if (!is_string($action)) return $this->response->setStatusCode(400)->setBody('Invalid action.');
        try {
            ExpiryStockResolution::authorize((string) session('role'), $action);
        } catch (\DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(esc($e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
        $draft = session('expiry_action_' . $id . '_' . $action);
        $token = $this->request->getPost('resolution_token');
        if (!is_array($draft) || !is_string($token) || !hash_equals($draft['token'], $token)) {
            return redirect()->to(site_url('admin/expiry-report/item/' . $id . '?action=' . $action))->with('error', 'This form expired. Review the latest stock before saving.');
        }
        try {
            $saved = (new ExpiryStockResolution(db_connect()))->resolve($id, $action, $this->request->getPost(), (string) session('role'), (int) session('branch_id'), (int) session('user_id'), $token, $draft['revision']);
        } catch (\DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(esc($e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            return $this->render($id, $action, $this->request->getPost(), $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Expiry resolution failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('admin/expiry-report/item/' . $id))->with('error', 'The action could not be saved. No stock changes were committed.');
        }
        return redirect()->to(site_url('admin/expiry-report/item/' . $id))->with('success', $saved ? 'Expiry-stock action recorded successfully.' : 'This action was already recorded. No duplicate changes were made.');
    }
}
