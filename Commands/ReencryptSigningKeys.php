<?php namespace AuthExtension\Commands;

use AuthExtension\AuthExtension;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * The signing keys written again with the current encryption key - the last step of rotating it,
 * for an application without a re-encryption command of its own. One that has one should call
 * `AuthExtension::reencryptSigningKeys()` from it instead, so a single command answers whether
 * the old key can go.
 *
 * Run once, by hand, where both keys are configured. Running it again is harmless.
 */
class ReencryptSigningKeys extends BaseCommand {

    public $group = 'AuthExtension';
    public $name = 'auth:reencrypt-signing-keys';
    public $description = 'Write the signing keys again with the current encryption key';

    public function run(array $params) {
        $result = AuthExtension::reencryptSigningKeys();

        CLI::write("Rewrote {$result['rewritten']} signing key(s) with the current encryption key.");
        if ($result['unreadable'] !== []) {
            CLI::error(count($result['unreadable']) . ' could not be read with any key, and were left as they are:');
            foreach ($result['unreadable'] as $key) {
                CLI::write("  {$key}");
            }
            CLI::error('Keep the old key in previousKeys until these are sorted out.');
            return EXIT_ERROR;
        }
        CLI::write('The signing keys no longer need the old key. Anything else the application encrypted with it');
        CLI::write('must be written again too before it is taken out of previousKeys.');

        return EXIT_SUCCESS;
    }

}
