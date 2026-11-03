CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin', 'uploader') NOT NULL DEFAULT 'uploader',
    token_hash CHAR(64) NULL,
    token_expires DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(16) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_path VARCHAR(255) NOT NULL,
    sha256 CHAR(64) NOT NULL,
    page_count INT UNSIGNED NOT NULL,
    status ENUM('pending', 'completed', 'declined', 'cancelled') NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    signed_path VARCHAR(255) NULL,
    signed_sha256 CHAR(64) NULL,
    UNIQUE KEY uq_documents_public (public_id),
    KEY idx_documents_creator (created_by),
    CONSTRAINT fk_documents_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS signers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    personnummer CHAR(13) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    status ENUM('pending', 'signed', 'declined') NOT NULL DEFAULT 'pending',
    viewed_at DATETIME NULL,
    signed_at DATETIME NULL,
    declined_at DATETIME NULL,
    decline_reason TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    last_sent_at DATETIME NULL,
    UNIQUE KEY uq_signers_token (token_hash),
    KEY idx_signers_document (document_id),
    CONSTRAINT fk_signers_document FOREIGN KEY (document_id) REFERENCES documents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NULL,
    signer_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    event VARCHAR(50) NOT NULL,
    detail VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_audit_document (document_id),
    KEY idx_audit_signer_event (signer_id, event, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_login_ip (ip, created_at),
    KEY idx_login_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
