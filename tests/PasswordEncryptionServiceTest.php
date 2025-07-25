<?php

namespace App\Tests;

use App\Service\PasswordEncryptionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class PasswordEncryptionServiceTest extends TestCase
{
    private PasswordEncryptionService $encryptionService;

    protected function setUp(): void
    {
        $parameterBag = new ParameterBag(['kernel.secret' => 'test-secret-key']);
        $this->encryptionService = new PasswordEncryptionService($parameterBag);
    }

    public function testEncryptDecryptPassword(): void
    {
        $plainPassword = 'mySecretPassword123';
        
        // Verschlüsselung
        $encrypted = $this->encryptionService->encrypt($plainPassword);
        
        $this->assertNotNull($encrypted);
        $this->assertNotEquals($plainPassword, $encrypted);
        $this->assertTrue($this->encryptionService->isEncrypted($encrypted));
        $this->assertStringStartsWith('enc:', $encrypted);
        
        // Entschlüsselung
        $decrypted = $this->encryptionService->decrypt($encrypted);
        
        $this->assertEquals($plainPassword, $decrypted);
    }

    public function testEncryptNullPassword(): void
    {
        $encrypted = $this->encryptionService->encrypt(null);
        $this->assertNull($encrypted);
        
        $decrypted = $this->encryptionService->decrypt(null);
        $this->assertNull($decrypted);
    }

    public function testEncryptEmptyPassword(): void
    {
        $encrypted = $this->encryptionService->encrypt('');
        $this->assertNull($encrypted);
        
        $decrypted = $this->encryptionService->decrypt('');
        $this->assertNull($decrypted);
    }

    public function testDecryptPlainTextPassword(): void
    {
        // Test für Rückwärtskompatibilität
        $plainPassword = 'plainTextPassword';
        
        $this->assertFalse($this->encryptionService->isEncrypted($plainPassword));
        
        // Klartext-Passwort sollte unverändert zurückgegeben werden
        $result = $this->encryptionService->decrypt($plainPassword);
        $this->assertEquals($plainPassword, $result);
    }

    public function testIsEncrypted(): void
    {
        $this->assertFalse($this->encryptionService->isEncrypted('plaintext'));
        $this->assertFalse($this->encryptionService->isEncrypted(''));
        $this->assertFalse($this->encryptionService->isEncrypted(null));
        
        $encrypted = $this->encryptionService->encrypt('password');
        $this->assertTrue($this->encryptionService->isEncrypted($encrypted));
    }

    public function testDoubleEncryptionPrevention(): void
    {
        $plainPassword = 'testPassword';
        
        // Erste Verschlüsselung
        $encrypted1 = $this->encryptionService->encrypt($plainPassword);
        
        // Zweite Verschlüsselung sollte das bereits verschlüsselte Passwort unverändert lassen
        $encrypted2 = $this->encryptionService->encrypt($encrypted1);
        
        $this->assertEquals($encrypted1, $encrypted2);
        
        // Entschlüsselung sollte das ursprüngliche Passwort ergeben
        $decrypted = $this->encryptionService->decrypt($encrypted2);
        $this->assertEquals($plainPassword, $decrypted);
    }
}