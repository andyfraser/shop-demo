<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\ProductServiceInterface;
use App\Services\CategoryServiceInterface;
use App\Services\AttributeServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\ImageServiceInterface;
use RuntimeException;

class AdminProductsController {
    public function __construct(
        private ProductServiceInterface $productService,
        private CategoryServiceInterface $categoryService,
        private AttributeServiceInterface $attributeService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private SettingsServiceInterface $settings,
        private ImageServiceInterface $imageService,
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
            'all_attributes' => $allAttributes,
            'product_attribute_ids' => [],
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $product_id = (int)$request->getQuery('id', 0);
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
            'categories' => $this->categoryService->getFlat(),
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

            // Handle variants
            if (isset($post['variants']) && is_array($post['variants'])) {
                foreach ($post['variants'] as $v) {
                    if (!empty($v['delete']) && !empty($v['id'])) {
                        $this->productService->deleteVariant((int)$v['id']);
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
                        'sort_order' => (int)($v['sort_order'] ?? 0)
                    ];
                    $vId = !empty($v['id']) ? (int)$v['id'] : 0;
                    $savedVId = $this->productService->saveVariant($vData, $vId);
                    
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
            return new RedirectResponse('/admin/products');
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
            'categories' => $this->categoryService->getFlat(),
            'all_attributes' => $allAttributes ?? [], 
            'product_attribute_ids' => $post['attribute_value_ids'] ?? [],
            'errors'     => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $product_id = (int)$request->getQuery('id', 0);
        if ($product_id) {
            $this->productService->deactivate($product_id);
            $this->logger->info("Admin deactivated product: (ID: {id})", [
                'id' => $product_id
            ]);
            flash('msg', 'Product deactivated.');
        }
        return new RedirectResponse('/admin/products');
    }
}
