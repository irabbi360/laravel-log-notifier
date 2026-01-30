<?php

namespace Irabbi360\LaravelLogNotifier\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'log-notifier:generate-vapid';

    protected $description = 'Generate VAPID keys for Web Push notifications';

    public function handle(): int
    {
        $this->info('Generating VAPID keys...');
        $this->newLine();

        // Generate a new ECDSA key pair
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $key = openssl_pkey_new($config);

        if (! $key) {
            $this->error('Failed to generate ECDSA key pair. Make sure OpenSSL is properly configured.');

            return self::FAILURE;
        }

        $details = openssl_pkey_get_details($key);

        // Export private key
        openssl_pkey_export($key, $privateKeyPem);

        // Extract the raw keys in the format needed for VAPID
        $publicKey = $this->base64UrlEncode(
            str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT).
            str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)
        );

        // Add the uncompressed point format prefix (0x04)
        $publicKeyWithPrefix = $this->base64UrlEncode(
            "\x04".
            str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT).
            str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)
        );

        $privateKey = $this->base64UrlEncode(
            str_pad($details['ec']['d'], 32, "\0", STR_PAD_LEFT)
        );

        $this->line('<fg=green>Add these to your .env file:</>');
        $this->newLine();

        $this->line("LOG_NOTIFIER_VAPID_PUBLIC_KEY={$publicKeyWithPrefix}");
        $this->line("LOG_NOTIFIER_VAPID_PRIVATE_KEY={$privateKey}");
        $this->newLine();

        $this->line('<fg=yellow>Important:</> Keep your private key secure!');
        $this->newLine();

        $this->line('<fg=cyan>Public key for JavaScript (applicationServerKey):</>');
        $this->line($publicKeyWithPrefix);

        return self::SUCCESS;
    }

    /**
     * Base64 URL encode.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
