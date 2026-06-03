<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                ALTER TABLE products ADD COLUMN is_purchasable TINYINT(1) NOT NULL DEFAULT 0;
                
                CREATE INDEX idx_products_active_purchasable_featured_created ON products(active, is_purchasable, featured, created_at);

                UPDATE products 
                SET is_purchasable = CASE 
                    WHEN is_virtual = 1 THEN 1
                    WHEN is_bundle = 0 AND stock > 0 THEN 1
                    WHEN is_bundle = 0 AND EXISTS (
                        SELECT 1 FROM product_variants pv 
                        WHERE pv.product_id = products.id AND pv.stock > 0 AND pv.active = 1
                    ) THEN 1
                    ELSE 0
                END
                WHERE is_bundle = 0 OR is_bundle IS NULL;

                UPDATE products
                SET is_purchasable = CASE
                    WHEN NOT EXISTS (
                        SELECT 1 
                        FROM product_bundle_items bi
                        JOIN (SELECT id, stock, is_virtual FROM products) component ON bi.product_id = component.id
                        WHERE bi.bundle_id = products.id
                          AND (
                              component.is_virtual = 0
                              AND (component.stock + COALESCE((SELECT SUM(pv.stock) FROM product_variants pv WHERE pv.product_id = component.id AND pv.active = 1), 0)) < bi.qty
                          )
                    ) THEN 1
                    ELSE 0
                END
                WHERE is_bundle = 1;
            ";
        }
        return "
            ALTER TABLE products ADD COLUMN is_purchasable TINYINT(1) NOT NULL DEFAULT 0;
            
            CREATE INDEX IF NOT EXISTS idx_products_active_purchasable_featured_created ON products(active, is_purchasable, featured, created_at);

            UPDATE products 
            SET is_purchasable = CASE 
                WHEN is_virtual = 1 THEN 1
                WHEN is_bundle = 0 AND stock > 0 THEN 1
                WHEN is_bundle = 0 AND EXISTS (
                    SELECT 1 FROM product_variants pv 
                    WHERE pv.product_id = products.id AND pv.stock > 0 AND pv.active = 1
                ) THEN 1
                ELSE 0
            END
            WHERE is_bundle = 0 OR is_bundle IS NULL;

            UPDATE products
            SET is_purchasable = CASE
                WHEN NOT EXISTS (
                    SELECT 1 
                    FROM product_bundle_items bi
                    JOIN products component ON bi.product_id = component.id
                    WHERE bi.bundle_id = products.id
                      AND (
                          component.is_virtual = 0
                          AND (component.stock + COALESCE((SELECT SUM(pv.stock) FROM product_variants pv WHERE pv.product_id = component.id AND pv.active = 1), 0)) < bi.qty
                      )
                ) THEN 1
                ELSE 0
            END
            WHERE is_bundle = 1;
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_products_active_purchasable_featured_created ON products;
                ALTER TABLE products DROP COLUMN is_purchasable;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_products_active_purchasable_featured_created;
            -- SQLite doesn't support dropping columns easily, so we just drop index.
        ";
    }
};
