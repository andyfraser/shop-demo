<?php
namespace App\Core;

class QueryCriteria {
    private array $filters = [];
    private string $sort = '';
    private int $page = 1;
    private ?int $limit = null;
    private string $searchTerm = '';

    public function __construct(array $params = []) {
        if (isset($params['sort'])) {
            $this->sort = (string)$params['sort'];
        }
        if (isset($params['page'])) {
            $this->page = max(1, (int)$params['page']);
        }
        if (isset($params['limit'])) {
            $this->limit = $params['limit'] === 'all' || $params['limit'] === null ? null : (int)$params['limit'];
        }
        if (isset($params['search'])) {
            $this->searchTerm = (string)$params['search'];
        }
        if (isset($params['filters']) && is_array($params['filters'])) {
            $this->filters = $params['filters'];
        }
    }

    public static function fromRequest(array $get, ?int $defaultLimit = null): self {
        $filters = [
            'attributes' => []
        ];
        if (isset($get['price_min'])) $filters['price_min'] = $get['price_min'];
        if (isset($get['price_max'])) $filters['price_max'] = $get['price_max'];
        if (isset($get['attr']) && is_array($get['attr'])) {
            $filters['attributes'] = array_map('intval', $get['attr']);
        }
        if (isset($get['status'])) $filters['status'] = (string)$get['status'];
        if (isset($get['category_id'])) $filters['category_id'] = (int)$get['category_id'];
        if (isset($get['product_ids']) && is_array($get['product_ids'])) {
            $filters['product_ids'] = array_map('intval', $get['product_ids']);
        }

        return new self([
            'sort'   => $get['sort'] ?? '',
            'page'   => $get['page'] ?? 1,
            'limit'  => $get['per_page'] ?? $defaultLimit,
            'search' => $get['q'] ?? '',
            'filters' => $filters
        ]);
    }

    public function getFilters(): array {
        return $this->filters;
    }

    public function getFilter(string $key, $default = null) {
        return $this->filters[$key] ?? $default;
    }

    public function hasFilter(string $key): bool {
        return isset($this->filters[$key]);
    }

    public function addFilter(string $key, $value): self {
        $this->filters[$key] = $value;
        return $this;
    }

    public function getSort(): string {
        return $this->sort;
    }

    public function getPage(): int {
        return $this->page;
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function getOffset(): int {
        if ($this->limit === null) return 0;
        return ($this->page - 1) * $this->limit;
    }

    public function getSearchTerm(): string {
        return $this->searchTerm;
    }

    public function withLimit(?int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function withPage(int $page): self {
        $this->page = max(1, $page);
        return $this;
    }

    public function withSort(string $sort): self {
        $this->sort = $sort;
        return $this;
    }
}
