<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service für die Verschlüsselung und Entschlüsselung von Passwörtern
 */
class PasswordEncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'AES-256-CBC';

    public function __construct(ParameterBagInterface $params)
    {
        // Verwende APP_SECRET als Basis für den Verschlüsselungsschlüssel
        $appSecret = $params->get('kernel.secret');
        $this->encryptionKey = hash('sha256', $appSecret . 'password-encryption-salt');
    }

    /**
     * Verschlüsselt ein Passwort
     */
    public function encrypt(?string $password): ?string
    {
        if (empty($password)) {
            return null;
        }

        // Prüfe ob bereits verschlüsselt (beginnt mit unserem Präfix)
        if ($this->isEncrypted($password)) {
            return $password;
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($password, $this->cipher, $this->encryptionKey, 0, $iv);
        
        if ($encrypted === false) {
            throw new \RuntimeException('Fehler beim Verschlüsseln des Passworts');
        }

        // Präfix hinzufügen um verschlüsselte Passwörter zu identifizieren
        return 'enc:' . base64_encode($iv . $encrypted);
    }

    /**
     * Entschlüsselt ein Passwort
     */
    public function decrypt(?string $encryptedPassword): ?string
    {
        if (empty($encryptedPassword)) {
            return null;
        }

        // Wenn nicht verschlüsselt, als Klartext zurückgeben (Rückwärtskompatibilität)
        if (!$this->isEncrypted($encryptedPassword)) {
            return $encryptedPassword;
        }

        // Entferne Präfix
        $encryptedData = substr($encryptedPassword, 4);
        $data = base64_decode($encryptedData);
        
        if ($data === false) {
            throw new \RuntimeException('Fehler beim Dekodieren des verschlüsselten Passworts');
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->encryptionKey, 0, $iv);
        
        if ($decrypted === false) {
            throw new \RuntimeException('Fehler beim Entschlüsseln des Passworts');
        }

        return $decrypted;
    }

    /**
     * Prüft ob ein Passwort bereits verschlüsselt ist
     */
    public function isEncrypted(?string $password): bool
    {
        return !empty($password) && str_starts_with($password, 'enc:');
    }
}