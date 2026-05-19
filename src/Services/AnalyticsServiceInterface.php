<?php
namespace App\Services;

interface AnalyticsServiceInterface {
    /**
     * Get daily sales (revenue) for the last X days.
     * Returns array of [date => amount]
     */
    public function getDailySales(int $days = 30): array;

    /**
     * Get top categories by total sales.
     * Returns array of [category_name => amount]
     */
    public function getTopCategories(int $limit = 5): array;

    /**
     * Render an SVG line chart for sales.
     */
    public function renderSalesChart(array $data, int $width = 800, int $height = 300): string;

    /**
     * Render an SVG bar chart for categories.
     */
    public function renderCategoryChart(array $data, int $width = 800, int $height = 300): string;
}
