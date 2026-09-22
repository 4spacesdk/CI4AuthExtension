<?php namespace AuthExtension\Commands;

use AuthExtension\AuthExtension;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * A new signing key for access tokens and id tokens. Run by hand, in one place: the old key is
 * retired, not deleted, so tokens already signed stay valid until they expire, and the key set
 * keeps publishing it until then. Running it again just makes another key.
 *
 * Worth running once after upgrading to v1.3.0: until then the key was stored in the clear, in
 * every dump and backup, and encrypting it does not make a copy already taken useless.
 */
class RotateSigningKey extends BaseCommand {

    public $group = 'AuthExtension';
    public $name = 'auth:rotate-signing-key';
    public $description = 'Replace the signing key; the old one is retired once its tokens expire';

    public function run(array $params) {
        $result = AuthExtension::rotateSigningKey();

        CLI::write("New signing key: {$result['kid']}");
        if ($result['retired'] !== []) {
            CLI::write('Retired: ' . implode(', ', $result['retired']) . ' - published until the tokens it signed expire.');
        }
        return EXIT_SUCCESS;
    }

}
