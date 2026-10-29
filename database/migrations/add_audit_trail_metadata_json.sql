-- Migration: Add metadata_json to audit_trail
-- Date: 2026-08-05
-- Description: Stores structured metadata for audit detail modals while keeping description as readable fallback.

ALTER TABLE `audit_trail`
  ADD COLUMN IF NOT EXISTS `metadata_json` text DEFAULT NULL AFTER `user_agent`;