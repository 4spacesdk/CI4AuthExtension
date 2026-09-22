<?php namespace AuthExtension\OAuth2;

use CodeIgniter\Config\Services;
use CodeIgniter\Encryption\Exceptions\EncryptionException;

/**
 * The signing key, encrypted with the application's encrypter (`Config\Encryption`).
 *
 * With the private key in the clear, a database dump was enough to sign access tokens for any
 * user. Encrypted, the dump also needs the application's encryption key.
 *
 * A value starts with `enc:v1:` and is base64 after that. A value without the marker is a key
 * stored before this version and is handed back as it is, so an installation that has not
 * migrated yet keeps signing. An application without an encryption key keeps storing the key
 * in the clear, with a warning, rather than losing sign-in.
 *
 * Rotating the encryption key: put the old one in `previousKeys` - CodeIgniter falls back to it
 * when decrypting. That needs CodeIgniter 4.7 or later; an older version ignores it.
 */
class KeyEncryption {

    public const MARKER = 'enc:v1:';

    public static function encrypt(?string $value): ?string {
        if ($value === null || $value === '' || self::isEncrypted($value)) {
            return $value;
        }

        try {
            $encrypter = Services::encrypter();
        } catch (EncryptionException $e) {
            log_message('warning', 'AuthExtension: the signing key is stored unencrypted, as there is no encryption key: ' . $e->getMessage());
            return $value;
        }

        return self::MARKER . base64_encode($encrypter->encrypt($value));
    }

    /**
     * @throws EncryptionException when the value is encrypted but no key can read it
     */
    public static function decrypt(?string $value): ?string {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        $raw = base64_decode(substr($value, strlen(self::MARKER)), true);
        if ($raw === false) {
            throw new EncryptionException('Encrypted signing key is not base64');
        }

        return Services::encrypter()->decrypt($raw);
    }

    public static function isEncrypted(?string $value): bool {
        return is_string($value) && str_starts_with($value, self::MARKER);
    }

}
