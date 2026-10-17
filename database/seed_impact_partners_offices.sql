-- Seed Impact Numbers, Partners, Office Locations (run after migration_impact_partners_offices.sql)
USE tulima_solar;

-- Impact Numbers: only insert if table is empty (with icon keys for card display)
INSERT INTO impact_numbers (label, value, sublabel, icon, sort_order, is_active)
SELECT * FROM (
  SELECT 'Farmers Empowered' AS label, '0' AS value, 'Lives transformed through solar power' AS sublabel, 'farmers' AS icon, 10 AS sort_order, 1 AS is_active
  UNION ALL SELECT 'Acres Irrigated', '0', 'Land brought to life sustainably', 'acres', 20, 1
  UNION ALL SELECT 'Income Increase', '0%', 'Average boost in farmer earnings', 'income', 30, 1
  UNION ALL SELECT 'Regional Offices', '0', 'Serving communities across Africa', 'offices', 40, 1
) t
WHERE (SELECT COUNT(*) FROM impact_numbers) = 0;

-- Partners: no default rows (add via Admin)

-- Office Locations: one sample office if empty
INSERT INTO office_locations (name, region_or_city, address, phone, email, sort_order, is_active)
SELECT * FROM (
  SELECT 'Head Office' AS name, 'Kampala' AS region_or_city, '' AS address, NULL AS phone, NULL AS email, 10 AS sort_order, 1 AS is_active
) t
WHERE (SELECT COUNT(*) FROM office_locations) = 0;
