import JSEncrypt from 'jsencrypt'
import api from '@/lib/api'

export interface EncryptionKeyResponse {
  public_key: string
  key_id: string
}

/**
 * Fetches the public key and key ID from the server
 */
export async function fetchEncryptionKey(): Promise<EncryptionKeyResponse> {
  const response = await api.get('/api/encryption-key')
  return response.data
}

/**
 * Encrypts a string payload using the provided public RSA key
 */
export function encryptPayload(payload: string, publicKey: string): string {
  const encryptor = new JSEncrypt()
  encryptor.setPublicKey(publicKey)
  const encrypted = encryptor.encrypt(payload)
  
  if (!encrypted) {
    throw new Error('Encryption failed')
  }
  
  return encrypted as string
}
