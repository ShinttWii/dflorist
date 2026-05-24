-- Tambah kolom media untuk ulasan
ALTER TABLE reviews ADD COLUMN media_files TEXT NULL COMMENT 'JSON array of uploaded file paths' AFTER comment;
