<?php
namespace App\Services;

use App\Models\Order;
use Psr\Log\LoggerInterface;

class AnalyticsService implements AnalyticsServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger,
        private PricingServiceInterface $pricingService
    ) {}

    public function getDailySales(int $days = 30): array {
        $driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $sql = "SELECT date(created_at) as date, SUM(total) as revenue 
                    FROM orders 
                    WHERE status != ? AND created_at >= date('now', ?)
                    GROUP BY date(created_at)
                    ORDER BY date(created_at) ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([Order::STATUS_CANCELLED, "-$days days"]);
        } else {
            $sql = "SELECT DATE(created_at) as date, SUM(total) as revenue 
                    FROM orders 
                    WHERE status != ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY DATE(created_at) ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, Order::STATUS_CANCELLED);
            $stmt->bindValue(2, $days, \PDO::PARAM_INT);
            $stmt->execute();
        }

        $results = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Fill in missing dates with zero
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = (float)($results[$date] ?? 0.0);
        }

        return $data;
    }

    public function getTopCategories(int $limit = 5): array {
        $sql = "SELECT c.name, SUM(oi.unit_price * oi.quantity) as revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE o.status != ?
                GROUP BY c.id
                ORDER BY revenue DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, Order::STATUS_CANCELLED);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function renderSalesChart(array $data, int $width = 800, int $height = 400): string {
        if (empty($data)) return '';

        $max = max($data) ?: 10;
        $padding = 50;
        $chartWidth = $width - ($padding * 2);
        $chartHeight = $height - ($padding * 2);
        
        $points = [];
        $count = count($data);
        $i = 0;
        foreach ($data as $date => $value) {
            $x = $padding + ($i * ($chartWidth / max(1, $count - 1)));
            $y = ($height - $padding) - ($value / $max * $chartHeight);
            $points[] = "$x,$y";
            $i++;
        }

        $polyPoints = implode(' ', $points);
        $svg = "<svg viewBox=\"0 0 $width $height\" class=\"analytics-chart line-chart\" preserveAspectRatio=\"xMidYMid meet\">";
        
        // Grid lines (horizontal)
        for ($j = 0; $j <= 4; $j++) {
            $gy = ($height - $padding) - ($j / 4 * $chartHeight);
            $val = $max * ($j / 4);
            $svg .= "<line x1=\"$padding\" y1=\"$gy\" x2=\"" . ($width - $padding) . "\" y2=\"$gy\" stroke=\"var(--line)\" stroke-dasharray=\"4\" />";
            $svg .= "<text x=\"" . ($padding - 10) . "\" y=\"" . ($gy + 4) . "\" text-anchor=\"end\" font-size=\"12\" fill=\"var(--ink-3)\">" . money($val) . "</text>";
        }

        // The line
        $svg .= "<polyline points=\"$polyPoints\" fill=\"none\" stroke=\"var(--primary)\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\" />";
        
        // Area under the line
        $firstX = $padding;
        $lastX = $width - $padding;
        $svg .= "<polygon points=\"$padding," . ($height - $padding) . " $polyPoints $lastX," . ($height - $padding) . "\" fill=\"var(--primary)\" fill-opacity=\"0.1\" />";

        // Dots and Tooltips (invisible till hover)
        $i = 0;
        foreach ($data as $date => $value) {
            [$x, $y] = explode(',', $points[$i]);
            $svg .= "<g class=\"chart-point\">";
            $svg .= "<circle cx=\"$x\" cy=\"$y\" r=\"4\" fill=\"var(--primary)\" />";
            $svg .= "<text x=\"$x\" y=\"" . ($y - 10) . "\" text-anchor=\"middle\" font-size=\"12\" font-weight=\"bold\" class=\"tooltip\" fill=\"var(--ink-1)\">" . money($value) . "</text>";
            $svg .= "</g>";
            
            // X-axis labels (every 5th day or first/last)
            if ($i % 5 === 0 || $i === $count - 1) {
                $displayDate = date('j M', strtotime($date));
                $svg .= "<text x=\"$x\" y=\"" . ($height - $padding + 25) . "\" text-anchor=\"middle\" font-size=\"12\" fill=\"var(--ink-3)\">$displayDate</text>";
            }
            $i++;
        }

        $svg .= "</svg>";
        return $svg;
    }

    public function renderCategoryChart(array $data, int $width = 800, int $height = 400): string {
        if (empty($data)) return '';

        $max = max($data) ?: 10;
        $padding = 60;
        $chartWidth = $width - ($padding * 2);
        $chartHeight = $height - ($padding * 2);
        
        $count = count($data);
        $barWidth = ($chartWidth / $count) * 0.6;
        $gap = ($chartWidth / $count) * 0.4;
        
        $svg = "<svg viewBox=\"0 0 $width $height\" class=\"analytics-chart bar-chart\" preserveAspectRatio=\"xMidYMid meet\">";
        
        // Horizontal grid lines
        for ($j = 0; $j <= 4; $j++) {
            $gy = ($height - $padding) - ($j / 4 * $chartHeight);
            $val = $max * ($j / 4);
            $svg .= "<line x1=\"$padding\" y1=\"$gy\" x2=\"" . ($width - $padding) . "\" y2=\"$gy\" stroke=\"var(--line)\" stroke-dasharray=\"4\" />";
            $svg .= "<text x=\"" . ($padding - 10) . "\" y=\"" . ($gy + 4) . "\" text-anchor=\"end\" font-size=\"12\" fill=\"var(--ink-3)\">" . money($val) . "</text>";
        }

        $i = 0;
        foreach ($data as $label => $value) {
            $x = $padding + ($i * ($barWidth + $gap)) + ($gap / 2);
            $h = ($value / $max) * $chartHeight;
            $y = ($height - $padding) - $h;
            
            $svg .= "<rect x=\"$x\" y=\"$y\" width=\"$barWidth\" height=\"$h\" fill=\"var(--secondary)\" rx=\"4\">";
            $svg .= "<title>$label: " . money($value) . "</title>";
            $svg .= "</rect>";
            
            // Labels
            $svg .= "<text x=\"" . ($x + $barWidth/2) . "\" y=\"" . ($height - $padding + 25) . "\" text-anchor=\"middle\" font-size=\"12\" font-weight=\"bold\" fill=\"var(--ink-2)\">" . h($label) . "</text>";
            
            // Values on top
            $svg .= "<text x=\"" . ($x + $barWidth/2) . "\" y=\"" . ($y - 10) . "\" text-anchor=\"middle\" font-size=\"12\" font-weight=\"bold\" fill=\"var(--ink-1)\">" . money($value) . "</text>";
            
            $i++;
        }

        $svg .= "</svg>";
        return $svg;
    }
}
