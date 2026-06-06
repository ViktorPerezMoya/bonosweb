<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateRootCA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonosweb:generate-root-ca';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the Root CA certificate for BonosWeb';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating BonosWeb Root CA...');

        // 1. Generate 4096-bit RSA Private Key
        $privkey = openssl_pkey_new([
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privkey === false) {
            $this->error('Failed to generate private key. Check OpenSSL configuration.');
            return 1;
        }

        // 2. Define Certificate Subject (DN)
        $dn = [
            'countryName'            => 'AR',
            'stateOrProvinceName'    => 'Mendoza',
            'localityName'           => 'Mendoza',
            'organizationName'       => 'bonosweb.com.ar',
            'commonName'             => 'bonosweb.com.ar - Certificado Raiz',
        ];

        // 3. Create CSR (Certificate Signing Request)
        $csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);

        if ($csr === false) {
            $this->error('Failed to create CSR.');
            return 1;
        }

        // 4. Create and Sign the X.509 Root Certificate
        // We need to pass extensions to make basicConstraints = CA:TRUE
        // In PHP, openssl_csr_sign doesn't take raw ext data easily unless using an openssl.cnf file.
        // Wait, PHP's openssl_csr_sign can accept an array of options with 'x509_extensions' mapping to a section
        // in openssl.cnf. Alternatively, we can create a temporary conf file.
        // Let's create a temporary config file for the CA extensions.
        $configContent = <<<EOT
[ req ]
distinguished_name = req_distinguished_name
x509_extensions = v3_ca

[ req_distinguished_name ]

[ v3_ca ]
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer
basicConstraints = critical, CA:true
keyUsage = critical, digitalSignature, cRLSign, keyCertSign
EOT;

        $tempConf = tempnam(sys_get_temp_dir(), 'openssl_conf');
        file_put_contents($tempConf, $configContent);

        // Sign it for 20 years (7300 days)
        $x509 = openssl_csr_sign(
            $csr,
            null,
            $privkey,
            7300,
            [
                'digest_alg'      => 'sha256',
                'config'          => $tempConf,
                'x509_extensions' => 'v3_ca',
            ]
        );

        unlink($tempConf);

        if ($x509 === false) {
            $this->error('Failed to sign the Root CA certificate: ' . openssl_error_string());
            return 1;
        }

        // 5. Export Key and Cert
        openssl_pkey_export($privkey, $pkeyout);
        openssl_x509_export($x509, $certout);

        // 6. Save to non-tenant local disk
        Storage::disk('local')->put('certs/system_cert.key', $pkeyout);
        Storage::disk('local')->put('certs/system_cert.crt', $certout);

        $this->info('Root CA generated successfully in local disk (certs/system_cert.key and certs/system_cert.crt).');

        openssl_free_key($privkey);

        return 0;
    }
}
