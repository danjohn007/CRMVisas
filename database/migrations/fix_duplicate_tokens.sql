-- Fix Script: Resolve Duplicate public_token Issue
-- Description: Fixes the duplicate entry error for public_token field
-- Use this if you encounter: "Duplicate entry '' for key 'voucher_code'" or "public_token" errors
-- Date: 2026-02-04

USE `recursos_visas`;

-- Step 1: Remove the unique index if it exists
DROP INDEX IF EXISTS idx_forms_public_token ON forms;

-- Step 2: Update all NULL or empty public_tokens with unique values
-- This ensures each form has a unique 64-character token
UPDATE `forms` 
SET `public_token` = LOWER(CONCAT(
    MD5(CONCAT(id, name, COALESCE(created_at, NOW()), RAND())),
    MD5(CONCAT(created_by, COALESCE(updated_at, NOW()), id * 1000))
))
WHERE `public_token` IS NULL OR `public_token` = '';

-- Step 3: Find and fix any remaining duplicates
-- This query will show if there are any duplicates
SELECT 'Checking for duplicates...' as status;
SELECT public_token, COUNT(*) as count
FROM forms 
GROUP BY public_token 
HAVING COUNT(*) > 1;

-- Step 4: If duplicates exist, update them with unique tokens
-- Run this for each duplicate ID found above
-- UPDATE forms SET public_token = MD5(CONCAT(RAND(), RAND(), id, NOW())) WHERE id = <ID_WITH_DUPLICATE>;

-- Step 5: Verify all forms have unique, non-null tokens
SELECT 'Verifying all forms have tokens...' as status;
SELECT COUNT(*) as forms_without_token 
FROM forms 
WHERE public_token IS NULL OR public_token = '';

-- Step 6: Check for any remaining duplicates
SELECT 'Final duplicate check...' as status;
SELECT COUNT(DISTINCT public_token) as unique_tokens, COUNT(*) as total_forms
FROM forms;

-- Step 7: If all checks pass, recreate the unique index
CREATE UNIQUE INDEX idx_forms_public_token ON forms(public_token);

SELECT 'Fix completed successfully!' as status;

-- Verification: Show sample of tokens
SELECT id, name, LEFT(public_token, 16) as token_prefix, LENGTH(public_token) as token_length
FROM forms 
LIMIT 5;
