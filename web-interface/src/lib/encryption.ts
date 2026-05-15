import CryptoJS from 'crypto-js';

// The key must be exactly 32 bytes for AES-256
const KEY = import.meta.env.VITE_INTERNAL_ENCRYPTION_KEY || '9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a4b';

/**
 * Encrypts a JSON object into a base64 string: base64(iv + ciphertext)
 * matching the PHP openssl_encrypt implementation.
 */
export const encryptPayload = (data: any): string => {
  const plaintext = JSON.stringify(data);
  
  // Generate a random 16-byte IV
  const iv = CryptoJS.lib.WordArray.random(16);
  const keyHex = CryptoJS.enc.Utf8.parse(KEY);

  const encrypted = CryptoJS.AES.encrypt(plaintext, keyHex, {
    iv: iv,
    mode: CryptoJS.mode.CBC,
    padding: CryptoJS.pad.Pkcs7
  });

  // Prepend IV to ciphertext (raw word arrays)
  const ivAndCiphertext = iv.concat(encrypted.ciphertext);

  // Return base64 encoded string
  return CryptoJS.enc.Base64.stringify(ivAndCiphertext);
};
