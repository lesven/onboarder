<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test für die Validierungslogik die in den Role-Controllern verwendet wird
 */
class RoleValidationTest extends TestCase
{
    public function testEmailValidation(): void
    {
        // Gültige E-Mail-Adressen
        $this->assertTrue(filter_var('test@example.com', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertTrue(filter_var('user.name@domain.org', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertTrue(filter_var('admin+test@company.de', FILTER_VALIDATE_EMAIL) !== false);

        // Ungültige E-Mail-Adressen
        $this->assertFalse(filter_var('invalid-email', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('@example.com', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('test@', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('test@.com', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('', FILTER_VALIDATE_EMAIL));
    }

    public function testInputSanitization(): void
    {
        // Whitespace sollte entfernt werden
        $this->assertEquals('test', trim('  test  '));
        $this->assertEquals('role@example.com', trim('  role@example.com  '));
        $this->assertEquals('Description text', trim("\t Description text \n"));

        // Leere Strings nach dem Trimmen
        $this->assertEquals('', trim('   '));
        $this->assertEquals('', trim("\t\n\r"));
    }

    public function testLengthValidation(): void
    {
        // Strings innerhalb der Längenbeschränkungen
        $shortName = 'Role Name';
        $this->assertTrue(strlen($shortName) <= 255);

        $longName = str_repeat('a', 255);
        $this->assertTrue(strlen($longName) <= 255);

        // Strings über der Längenbeschränkung
        $tooLongName = str_repeat('a', 256);
        $this->assertFalse(strlen($tooLongName) <= 255);
    }

    public function testEmptyFieldValidation(): void
    {
        // Leere Felder sollten als ungültig erkannt werden
        $this->assertTrue(empty(''));
        $this->assertTrue(empty(trim('   '))); // Nach trim wäre das leer
        $this->assertTrue(empty(null));

        // Nicht-leere Felder sollten als gültig erkannt werden
        $this->assertFalse(empty('Valid Name'));
        $this->assertTrue(empty('0')); // String '0' ist in PHP empty
    }

    /**
     * Testfunktion die die komplette Validierungslogik simuliert
     */
    public function testCompleteValidationLogic(): void
    {
        // Simuliere die Validierungslogik aus dem Controller
        $testCases = [
            // [name, email, description, expected_valid]
            ['Valid Role', 'valid@example.com', 'Description', true],
            ['', 'valid@example.com', 'Description', false], // Leerer Name
            ['Valid Role', '', 'Description', false], // Leere E-Mail
            ['Valid Role', 'invalid-email', 'Description', false], // Ungültige E-Mail
            [str_repeat('a', 256), 'valid@example.com', 'Description', false], // Name zu lang
            ['Valid Role', str_repeat('a', 250) . '@example.com', 'Description', false], // E-Mail zu lang
            ['  Valid Role  ', '  valid@example.com  ', '  Description  ', true], // Mit Whitespace
        ];

        foreach ($testCases as [$name, $email, $description, $expectedValid]) {
            $isValid = $this->validateRoleInput($name, $email, $description);
            
            if ($expectedValid) {
                $this->assertTrue($isValid, "Validation should pass for: name='$name', email='$email'");
            } else {
                $this->assertFalse($isValid, "Validation should fail for: name='$name', email='$email'");
            }
        }
    }

    /**
     * Simuliert die Validierungslogik aus dem editRole Controller
     */
    private function validateRoleInput(string $name, string $email, string $description): bool
    {
        // Eingabedaten trimmen
        $name = trim($name);
        $email = trim($email);
        $description = trim($description);

        // Erforderliche Felder prüfen
        if (empty($name) || empty($email)) {
            return false;
        }

        // E-Mail-Format validieren
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Längenbeschränkungen prüfen
        if (strlen($name) > 255 || strlen($email) > 255) {
            return false;
        }

        return true;
    }
}