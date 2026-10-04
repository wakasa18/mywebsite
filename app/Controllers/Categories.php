<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ActivityLogModel;

class Categories extends BaseController
{
    protected CategoryModel $categoryModel;
    protected ActivityLogModel $activityLogModel;
    protected $db;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
        $builder = $this->categoryModel->orderBy('category_name', 'ASC');

        if ($keyword !== '') {
            $builder->like('category_name', $keyword);
        }

        $categories = $builder->paginate(10);

        foreach ($categories as &$category) {
            $categoryId = (int) $category['id'];
            $countRow = $this->db->table('products')
                ->selectCount('id', 'product_count')
                ->where('category_id', $categoryId)
                ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0)
                ->get()
                ->getRowArray();

            $category['product_count'] = (int) ($countRow['product_count'] ?? 0);
            $samples = $this->db->table('products')
                ->select('product_name')
                ->where('category_id', $categoryId)
                ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0)
                ->orderBy('product_name', 'ASC')
                ->limit(3)
                ->get()
                ->getResultArray();
            $category['sample_products'] = array_column($samples, 'product_name');
        }
        unset($category);

        return view('categories/index', [
            'categories' => $categories,
            'keyword' => $keyword,
            'pager' => $this->categoryModel->pager,
        ]);
    }

    public function create()
    {
        return view('categories/create', [
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function store()
    {
        $categoryName = trim((string) $this->request->getPost('category_name'));
        $rules = [
            'category_name' => 'required|min_length[2]|max_length[100]|is_unique[categories.category_name]',
        ];

        if (!$this->validateData(['category_name' => $categoryName], $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (!$this->categoryModel->insert(['category_name' => $categoryName])) {
            return redirect()->back()->withInput()->with('error', 'The category could not be saved. Please try again.');
        }

        $this->logActivity('Added category: ' . $categoryName);

        return redirect()->to(site_url('categories'))->with('success', 'Category added successfully.');
    }

    public function edit($id)
    {
        $category = $this->categoryModel->find((int) $id);
        if (!$category) {
            return redirect()->to(site_url('categories'))->with('error', 'Category not found.');
        }

        return view('categories/edit', [
            'category' => $category,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return redirect()->to(site_url('categories'))->with('error', 'Category not found.');
        }

        $newName = trim((string) $this->request->getPost('category_name'));
        $rules = ['category_name' => 'required|min_length[2]|max_length[100]'];

        if (strcasecmp($newName, (string) $category['category_name']) !== 0) {
            $rules['category_name'] .= '|is_unique[categories.category_name]';
        }

        if (!$this->validateData(['category_name' => $newName], $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (!$this->categoryModel->update($id, ['category_name' => $newName])) {
            return redirect()->back()->withInput()->with('error', 'The category could not be updated.');
        }

        $this->logActivity('Updated category: ' . $category['category_name'] . ' → ' . $newName);

        return redirect()->to(site_url('categories'))->with('success', 'Category updated successfully.');
    }

    public function delete($id)
    {
        $id = (int) $id;
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return redirect()->to(site_url('categories'))->with('error', 'Category not found.');
        }

        $productCount = $this->db->table('products')
            ->where('category_id', $id)
            ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0)
            ->countAllResults();

        if ($productCount > 0) {
            return redirect()->to(site_url('categories'))->with(
                'error',
                'This category is still used by ' . $productCount . ' product(s). Move or remove those products first.'
            );
        }

        if (!$this->categoryModel->delete($id)) {
            return redirect()->to(site_url('categories'))->with('error', 'The category could not be moved to trash.');
        }

        $this->logActivity('Moved category to trash: ' . $category['category_name']);

        return redirect()->to(site_url('categories'))->with('success', 'Category moved to trash.');
    }

    public function trash()
    {
        $keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
        $builder = $this->categoryModel->onlyDeleted()->orderBy('deleted_at', 'DESC');

        if ($keyword !== '') {
            $builder->like('category_name', $keyword);
        }

        return view('categories/trash', [
            'categories' => $builder->paginate(15),
            'keyword' => $keyword,
            'pager' => $this->categoryModel->pager,
        ]);
    }

    public function restore($id)
    {
        $id = (int) $id;
        $category = $this->categoryModel->onlyDeleted()->find($id);
        if (!$category) {
            return redirect()->to(site_url('categories/trash'))->with('error', 'Category not found in trash.');
        }

        $restored = $this->categoryModel->restoreRecord($id);
        if (!$restored) {
            return redirect()->to(site_url('categories/trash'))->with('error', 'The category could not be restored.');
        }

        $this->logActivity('Restored category: ' . $category['category_name']);

        return redirect()->to(site_url('categories/trash'))->with('success', 'Category restored successfully.');
    }

    public function forceDelete(int $id)
    {
        if (session('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Administrator access required.');
        }
        try {
            $changed = (new \App\Libraries\RetainedRecordDeletion($this->db))->hide('categories', $id, (string)session('role'), (int)session('user_id'));
        } catch (\DomainException|\InvalidArgumentException $error) {
            return redirect()->to(site_url('categories/trash'))->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            log_message('error', 'Permanent hiding failed: {message}', ['message'=>$error->getMessage()]);
            return redirect()->to(site_url('categories/trash'))->with('error', 'The record could not be removed. No changes were saved.');
        }
        return redirect()->to(site_url('categories/trash'))->with('success', $changed ? 'Category removed from the system and Trash. Its database record and history are retained.' : 'Category is already permanently hidden.');
    }

    private function logActivity(string $activity): void
    {
        $this->activityLogModel->insert([
            'user_id' => session('user_id'),
            'activity' => $activity,
            'log_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
