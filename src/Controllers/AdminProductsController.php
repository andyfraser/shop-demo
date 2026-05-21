<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\ProductServiceInterface;
use App\Services\ProductVariantServiceInterface;
use App\Services\CategoryServiceInterface;
use App\Services\AttributeServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\ImageServiceInterface;
use RuntimeException;

class AdminProductsController {
    public function __construct(
        private ProductServiceInterface $productService,
        private ProductVariantServiceInterface $variantService,
        private CategoryServiceInterface $categoryService,
        private AttributeServiceInterface $attributeService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private SettingsServiceInterface $settings,
        private ImageServiceInterface $imageService,
        private \App\Services\CsvServiceInterface $csvService,
        private \Psr\Log\LoggerInterface $logger
    ) {}
    
    public function list(Request $request): Response {
        $criteria = \App\Core\QueryCriteria::fromRequest($request->getQuery());
        if ($request->getQuery('search')) {
            $criteria = new \App\Core\QueryCriteria(['search' => $request->getQuery('search')]);
        }
        $products = $this->productService->getAllForAdmin($criteria);

        return new HtmlResponse($this->renderer->adminRender('products_list', [
            'page_title' => 'Products',
            'active'     => 'products',
            'products'   => $products,
            'search'     => $criteria->getSearchTerm(),
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function lowStock(Request $request): Response {
        $threshold = (int)$this->settings->get('low_stock_threshold');
        // We want all low stock items, so we use a high limit or better, we can modify getLowStock to accept a very high limit
        // Or we could add a method for this, but let's just use 1000 for now.
        $items = $this->productService->getLowStock($threshold, 1000);

        return new HtmlResponse($this->renderer->adminRender('products_low_stock', [
            'page_title' => 'Low Stock Management',
            'active'     => 'products',
            'items'      => $items,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function updateLowStock(Request $request): Response {
        $post = $request->getPost();
        $stock = $post['stock'] ?? [];
        $vStock = $post['variant_stock'] ?? [];

        foreach ($stock as $id => $val) {
            $this->productService->updateStock((int)$id, (int)$val);
        }

        foreach ($vStock as $id => $val) {
            $this->productService->updateVariantStock((int)$id, (int)$val);
        }

        $this->logger->info("Admin updated low stock inventory. Products: {p_count}, Variants: {v_count}", [
            'p_count' => count($stock),
            'v_count' => count($vStock)
        ]);

        flash('msg', 'Stock levels updated.');
        return new RedirectResponse('/admin/products/low-stock');
    }

    public function create(Request $request): Response {
        $allAttributes = $this->attributeService->getAll();
        foreach ($allAttributes as &$attr) {
            $attr['values'] = $this->attributeService->getValues($attr['id']);
        }

        return new HtmlResponse($this->renderer->adminRender('products_form', [
            'page_title' => 'Add Product',
            'active'     => 'products',
            'is_new'     => true,
            'product'    => [
                'vat_rate' => $this->settings->get('default_vat_rate')
            ],
            'product_id' => 0,
            'categories' => $this->categoryService->getFlat(),
            'all_products' => $this->productService->getAllActive(new \App\Core\QueryCriteria(['sort' => 'name'])),
            'all_attributes' => $allAttributes,
            'product_attribute_ids' => [],
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $product_id = (int)$request->getQuery('id', 0);
        $return_to = $request->getQuery('return_to');
        $product = $this->productService->findById($product_id);

        $allAttributes = $this->attributeService->getAll();
        foreach ($allAttributes as &$attr) {
            $attr['values'] = $this->attributeService->getValues($attr['id']);
        }

        return new HtmlResponse($this->renderer->adminRender('products_form', [
            'page_title' => 'Edit Product',
            'active'     => 'products',
            'is_new'     => !$product_id,
            'product'    => $product,
            'product_id' => $product_id,
            'return_to'  => $return_to,
            'categories' => $this->categoryService->getFlat(),
            'all_products' => $this->productService->getAllActive(new \App\Core\QueryCriteria(['sort' => 'name'])),
            'all_attributes' => $allAttributes,
            'product_attribute_ids' => $this->attributeService->getProductAttributeValues($product_id),
            'errors'     => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $data = [
            'name'        => trim($post['name'] ?? ''),
            'sku'         => trim($post['sku'] ?? ''),
            'description' => trim($post['description'] ?? ''),
            'price'       => (float)($post['price'] ?? 0),
            'vat_rate'    => (float)($post['vat_rate'] ?? 0),
            'stock'       => (int)($post['stock'] ?? 0),
            'category_id' => !empty($post['category_id']) ? (int)$post['category_id'] : null,
            'image'       => $post['existing_image'] ?? null,
            'active'      => isset($post['active']) ? 1 : 0,
            'featured'    => isset($post['featured']) ? 1 : 0,
            'force_variant' => isset($post['force_variant']) ? 1 : 0,
            'is_bundle'   => isset($post['is_bundle']) ? 1 : 0,
            'is_virtual'  => isset($post['is_virtual']) ? 1 : 0,
            'virtual_type'=> !empty($post['virtual_type']) ? trim($post['virtual_type']) : null,
            'file_path'   => !empty($post['file_path']) ? trim($post['file_path']) : null,
            'granted_role'=> !empty($post['granted_role']) ? trim($post['granted_role']) : null,
        ];
        $product_id = (int)($post['id'] ?? 0);

        $errors = $this->validator->check($post, [
            'name'  => 'required',
            'price' => 'required|positive',
        ]);

        if (!$errors) {
            try {
                $file = $_FILES['image'] ?? null;
                if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploaded = $this->imageService->processUpload($file);
                    if ($uploaded) {
                        if ($data['image']) {
                            $this->imageService->delete($data['image']);
                        }
                        $data['image'] = $uploaded;
                    }
                } elseif (isset($post['remove_image'])) {
                    if ($data['image']) {
                        $this->imageService->delete($data['image']);
                    }
                    $data['image'] = null;
                }

                // Handle virtual file upload for digital downloads
                $virtualFile = $_FILES['virtual_file'] ?? null;
                if ($virtualFile && $virtualFile['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($virtualFile['error'] === UPLOAD_ERR_OK) {
                        $originalName = basename($virtualFile['name']);
                        
                        // Strict extension check (Defense in depth)
                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $blockedExtensions = ['php', 'phtml', 'php5', 'php7', 'phps', 'phar', 'sh', 'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd'];
                        if (in_array($ext, $blockedExtensions)) {
                            throw new RuntimeException('Disallowed file extension for secure digital downloads.');
                        }
                        
                        // Strict filename sanitization
                        $cleanName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
                        $cleanName = trim($cleanName, '. ');
                        if ($cleanName === '') {
                            $cleanName = 'download_' . time();
                        }
                        
                        // Prevent guessability and filename collisions using high-entropy random prefix
                        $randomPrefix = bin2hex(random_bytes(16));
                        $uniqueName = $randomPrefix . '_' . $cleanName;
                        
                        $downloadsDir = __DIR__ . '/../../storage/downloads/';
                        if (!is_dir($downloadsDir)) {
                            if (!mkdir($downloadsDir, 0755, true)) {
                                throw new RuntimeException('Failed to create secure downloads directory.');
                            }
                        }
                        
                        $targetPath = $downloadsDir . $uniqueName;
                        if (move_uploaded_file($virtualFile['tmp_name'], $targetPath)) {
                            // Update file path to reference the secure upload
                            $data['file_path'] = 'storage/downloads/' . $uniqueName;
                        } else {
                            throw new RuntimeException('Failed to move uploaded digital file to secure storage.');
                        }
                    } else {
                        // Standard file upload error mapping
                        $uploadErrors = [
                            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
                            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
                        ];
                        $errMsg = $uploadErrors[$virtualFile['error']] ?? 'Unknown upload error.';
                        throw new RuntimeException('Digital file upload failed: ' . $errMsg);
                    }
                }
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            $saved_id = $this->productService->save($data, $product_id);
            $final_id = $product_id ?: $saved_id;

            // Handle attributes
            $attrValueIds = isset($post['attribute_value_ids']) && is_array($post['attribute_value_ids']) 
                ? array_map('intval', $post['attribute_value_ids']) 
                : [];
            $this->attributeService->saveProductAttributeValues($final_id, $attrValueIds);

            // Handle variant-defining attributes
            $variantAttrIds = isset($post['variant_attribute_ids']) && is_array($post['variant_attribute_ids'])
                ? array_map('intval', $post['variant_attribute_ids'])
                : [];
            $this->attributeService->saveVariantAttributes($final_id, $variantAttrIds);

            // Handle quantity tiers
            $tiers = isset($post['tiers']) && is_array($post['tiers']) ? $post['tiers'] : [];
            $this->productService->syncTiers($final_id, $tiers);

            // Handle bundle items
            $bundleItems = isset($post['bundle_items']) && is_array($post['bundle_items']) ? $post['bundle_items'] : [];
            $this->productService->syncBundleItems($final_id, $bundleItems);

            // Handle variants
            if (isset($post['variants']) && is_array($post['variants'])) {
                foreach ($post['variants'] as $v) {
                    if (!empty($v['delete']) && !empty($v['id'])) {
                        $this->variantService->delete((int)$v['id']);
                        continue;
                    }

                    if (empty($v['name']) && empty($v['attr_values'])) continue;

                    $vData = [
                        'product_id' => $final_id,
                        'name'       => trim($v['name'] ?? ''),
                        'sku'        => trim($v['sku'] ?? ''),
                        'price'      => isset($v['price']) && $v['price'] !== '' ? (float)$v['price'] : null,
                        'stock'      => (int)($v['stock'] ?? 0),
                        'active'     => 1,
                        'sort_order' => (int)($v['sort_order'] ?? 0),
                        'file_path'  => !empty($v['file_path']) ? trim($v['file_path']) : null,
                        'granted_role' => !empty($v['granted_role']) ? trim($v['granted_role']) : null,
                    ];
                    $vId = !empty($v['id']) ? (int)$v['id'] : 0;
                    $savedVId = $this->variantService->save($vData, $vId);
                    
                    // Save variant attribute values
                    $vAttrValueIds = isset($v['attr_values']) && is_array($v['attr_values'])
                        ? array_values(array_filter(array_map('intval', $v['attr_values'])))
                        : [];
                    $this->attributeService->saveVariantAttributeValues($vId ?: $savedVId, $vAttrValueIds);
                }
            }

            if ($product_id) {
                $this->logger->info("Admin updated product: {name} (ID: {id})", [
                    'name' => $data['name'],
                    'id' => $product_id
                ]);
                flash('msg', 'Product updated.');
            } else {
                $this->logger->info("Admin created product: {name} (ID: {id})", [
                    'name' => $data['name'],
                    'id' => $saved_id
                ]);
                flash('msg', 'Product created.');
            }

            $redirectUrl = !empty($post['return_to']) ? $post['return_to'] : '/admin/products';
            return new RedirectResponse($redirectUrl);
        }

        $allAttributes = $this->attributeService->getAll();
        foreach ($allAttributes as &$attr) {
            $attr['values'] = $this->attributeService->getValues($attr['id']);
        }

        return new HtmlResponse($this->renderer->adminRender('products_form', [
            'page_title' => ($product_id ? 'Edit' : 'Add') . ' Product',
            'active'     => 'products',
            'is_new'     => !$product_id,
            'product'    => $data, 
            'product_id' => $product_id,
            'return_to'  => $post['return_to'] ?? null,
            'categories' => $this->categoryService->getFlat(),
            'all_products' => $this->productService->getAllActive(new \App\Core\QueryCriteria(['sort' => 'name'])),
            'all_attributes' => $allAttributes ?? [], 
            'product_attribute_ids' => $post['attribute_value_ids'] ?? [],
            'errors'     => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $product_id = (int)$request->getPost('id', 0);
        if ($product_id) {
            $this->productService->deactivate($product_id);
            $this->logger->info("Admin deactivated product: (ID: {id})", [
                'id' => $product_id
            ]);
            flash('msg', 'Product deactivated.');
        }
        return new RedirectResponse('/admin/products');
    }

    public function batchUpdate(Request $request): Response {
        $post = $request->getPost();
        $ids = $post['ids'] ?? [];
        $action = $post['action'] ?? '';

        if (empty($ids) || empty($action)) {
            flash('msg_error', 'No products or action selected.');
            return new RedirectResponse('/admin/products');
        }

        $count = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($action === 'activate') {
                $this->productService->save(['active' => 1], $id);
                $count++;
            } elseif ($action === 'deactivate') {
                $this->productService->deactivate($id);
                $count++;
            }
        }

        $this->logger->info("Admin performed batch action {action} on {count} products", [
            'action' => $action,
            'count' => $count,
            'ids' => $ids
        ]);

        flash('msg', "Batch action '{$action}' completed for {$count} products.");
        return new RedirectResponse('/admin/products');
    }

    public function import(Request $request): Response {
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            flash('msg_error', 'Please upload a valid CSV file.');
            return new RedirectResponse('/admin/products');
        }

        try {
            $rows = $this->csvService->import($file['tmp_name']);
            $count = 0;
            foreach ($rows as $row) {
                if (empty($row['name'])) continue;

                $data = [
                    'name'        => trim($row['name']),
                    'sku'         => trim($row['sku'] ?? ''),
                    'price'       => (float)($row['price'] ?? 0),
                    'stock'       => (int)($row['stock'] ?? 0),
                    'category_id' => !empty($row['category_id']) ? (int)$row['category_id'] : null,
                    'active'      => 1,
                    'vat_rate'    => (float)($this->settings->get('default_vat_rate') ?? 20)
                ];

                $this->productService->save($data);
                $count++;
            }

            $this->logger->info("Admin imported products from CSV. Count: {count}", ['count' => $count]);
            flash('msg', "Successfully imported {$count} products.");

        } catch (\Exception $e) {
            $this->logger->error("Product import failed: " . $e->getMessage());
            flash('msg_error', 'Import failed: ' . $e->getMessage());
        }

        return new RedirectResponse('/admin/products');
    }
}
