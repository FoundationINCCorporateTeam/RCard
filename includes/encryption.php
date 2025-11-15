<?php
/**
 * RCard Encryption Module
 * 
 * Provides AES-256-CBC encryption/decryption for sensitive JSON data
 * Uses random IV per encryption and base64 encoding for storage
 * 
 * @package RCard
 * @version 1.0.0
 */

// Define encryption key constant (should be set in environment/config)
if (!defined('R_JSON_KEY')) {
    define('R_JSON_KEY', getenv('RCARD_ENCRYPTION_KEY') ?: 'default-key-change-in-production-min-32-chars-required');
}

/**
 * Encrypt an array/object to encrypted JSON blob
 * 
 * @param array|object $data Data to encrypt
 * @param string $key Encryption key (min 32 chars for AES-256)
 * @return array|false Array with 'blob' and 'iv' keys, or false on failure
 */
function r_encrypt($data, string $key = R_JSON_KEY) {
    try {
        // Convert data to JSON
        $json = json_encode($data);
        if ($json === false) {
            error_log("r_encrypt: JSON encoding failed - " . json_last_error_msg());
            return false;
        }
        
        // Ensure key is at least 32 bytes for AES-256
        $key = hash('sha256', $key, true);
        
        // Generate random IV
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        // Encrypt the JSON
        $encrypted = openssl_encrypt(
            $json,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            error_log("r_encrypt: Encryption failed - " . openssl_error_string());
            return false;
        }
        
        // Return base64-encoded blob and IV
        return [
            'blob' => base64_encode($encrypted),
            'iv' => base64_encode($iv)
        ];
        
    } catch (Exception $e) {
        error_log("r_encrypt: Exception - " . $e->getMessage());
        return false;
    }
}

/**
 * Decrypt an encrypted JSON blob back to array/object
 * 
 * @param array|string $payload Either array with 'blob' and 'iv', or JSON string
 * @param string $key Encryption key (min 32 chars for AES-256)
 * @return array|object|false Decrypted data, or false on failure
 */
function r_decrypt($payload, string $key = R_JSON_KEY) {
    try {
        // Handle both array and JSON string input
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            if ($payload === null) {
                error_log("r_decrypt: Invalid JSON payload");
                return false;
            }
        }
        
        // Validate payload structure
        if (!isset($payload['blob']) || !isset($payload['iv'])) {
            error_log("r_decrypt: Missing blob or iv in payload");
            return false;
        }
        
        // Ensure key is at least 32 bytes for AES-256
        $key = hash('sha256', $key, true);
        
        // Decode base64
        $encrypted = base64_decode($payload['blob'], true);
        $iv = base64_decode($payload['iv'], true);
        
        if ($encrypted === false || $iv === false) {
            error_log("r_decrypt: Base64 decode failed");
            return false;
        }
        
        // Decrypt
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            error_log("r_decrypt: Decryption failed - " . openssl_error_string());
            return false;
        }
        
        // Parse JSON
        $data = json_decode($decrypted, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("r_decrypt: JSON decode failed - " . json_last_error_msg());
            return false;
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("r_decrypt: Exception - " . $e->getMessage());
        return false;
    }
}

/**
 * Encrypt and write data to file
 * 
 * @param string $filepath Path to file
 * @param array|object $data Data to encrypt and save
 * @param string $key Encryption key
 * @return bool Success status
 */
function r_encrypt_to_file(string $filepath, $data, string $key = R_JSON_KEY): bool {
    $encrypted = r_encrypt($data, $key);
    if ($encrypted === false) {
        return false;
    }
    
    $json = json_encode($encrypted, JSON_PRETTY_PRINT);
    return r_atomic_write($filepath, $json);
}

/**
 * Read and decrypt data from file
 * 
 * @param string $filepath Path to file
 * @param string $key Encryption key
 * @return array|object|false Decrypted data or false on failure
 */
function r_decrypt_from_file(string $filepath, string $key = R_JSON_KEY) {
    if (!file_exists($filepath)) {
        error_log("r_decrypt_from_file: File not found - $filepath");
        return false;
    }
    
    $json = file_get_contents($filepath);
    if ($json === false) {
        error_log("r_decrypt_from_file: Failed to read file - $filepath");
        return false;
    }
    
    return r_decrypt($json, $key);
}

/**
 * Atomic file write with file locking
 * 
 * @param string $filepath Path to file
 * @param string $content Content to write
 * @return bool Success status
 */
function r_atomic_write(string $filepath, string $content): bool {
    // Ensure directory exists
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("r_atomic_write: Failed to create directory - $dir");
            return false;
        }
    }
    
    // Write to temporary file first
    $temp_file = $filepath . '.tmp.' . uniqid();
    
    $fp = fopen($temp_file, 'w');
    if ($fp === false) {
        error_log("r_atomic_write: Failed to open temp file - $temp_file");
        return false;
    }
    
    // Lock file for exclusive writing
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        @unlink($temp_file);
        error_log("r_atomic_write: Failed to acquire lock on temp file");
        return false;
    }
    
    // Write content
    $bytes = fwrite($fp, $content);
    
    // Release lock and close
    flock($fp, LOCK_UN);
    fclose($fp);
    
    if ($bytes === false || $bytes !== strlen($content)) {
        @unlink($temp_file);
        error_log("r_atomic_write: Failed to write content");
        return false;
    }
    
    // Atomic rename
    if (!rename($temp_file, $filepath)) {
        @unlink($temp_file);
        error_log("r_atomic_write: Failed to rename temp file to target");
        return false;
    }
    
    return true;
}
