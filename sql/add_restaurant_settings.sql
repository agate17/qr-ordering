-- Add restaurant settings table for dynamic configuration
USE restaurant_demo;

-- Create settings table
CREATE TABLE IF NOT EXISTS restaurant_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  description TEXT
);

-- Insert default table count
INSERT INTO restaurant_settings (setting_key, setting_value, description) 
VALUES ('table_count', '3', 'Number of tables in the restaurant')
ON DUPLICATE KEY UPDATE setting_value = setting_value;