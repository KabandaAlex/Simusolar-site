-- Add latitude and longitude to office_locations for map markers (OpenStreetMap/Leaflet)
USE tulima_solar;

ALTER TABLE office_locations
  ADD COLUMN latitude DECIMAL(10, 8) NULL DEFAULT NULL AFTER map_url,
  ADD COLUMN longitude DECIMAL(11, 8) NULL DEFAULT NULL AFTER latitude;
