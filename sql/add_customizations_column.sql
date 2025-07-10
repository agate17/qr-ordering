-- Add customizations column to existing order_items table
USE restaurant_demo;

-- Add customizations column if it doesn't exist
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS customizations TEXT; 