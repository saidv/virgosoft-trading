-- PostgreSQL initialization script for Trading Platform
-- This script runs automatically when the database container starts for the first time

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Grant privileges to the application user
GRANT ALL PRIVILEGES ON DATABASE trading_platform TO postgres;

-- Create schema for better organization (optional)
-- CREATE SCHEMA IF NOT EXISTS trading;
-- ALTER ROLE postgres SET search_path TO trading, public;
