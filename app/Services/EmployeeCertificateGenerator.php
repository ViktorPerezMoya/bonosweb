<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Servicio de generación de certificados digitales PKCS#12 para empleados.
 */
class EmployeeCertificateGenerator
{
    /**
     * Genera el certificado .pfx para un empleado y lo persiste en el disco
     * privado del tenant.
     *
     * @param  string  $employeeName Nombre completo del empleado
     * @param  string  $cuil         CUIL o DNI del empleado
     * @param  string  $companyName  Nombre de la empresa activa
     * @param  int     $employeeId   ID del perfil del empleado
     *
     * @return array{pfx_path: string, pfx_password: string, expires_at: string}
     */
    public function generate(string $employeeName, string $cuil, string $companyName, int $employeeId, string $email): array
    {
        // ── 1. Par de claves RSA 2048-bit ─────────────────────────────────────
        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privkey === false) {
            throw new RuntimeException(
                'OpenSSL no pudo generar el par de claves RSA. ' .
                'Verifique que la extensión php_openssl esté habilitada y correctamente configurada.'
            );
        }

        // ── 2. Distinguished Name con metadatos del empleado ─────────────────
        $dn = [
            'countryName'            => 'AR',
            'stateOrProvinceName'    => 'Mendoza',
            'localityName'           => 'Mendoza',
            'organizationName'       => 'bonosweb.com.ar',//$companyName,
            'emailAddress'           => $email,
            'commonName'             => $employeeName . ' - ' . $cuil,
        ];

        // ── 3. CSR (Certificate Signing Request) ──────────────────────────────
        $csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);

        if ($csr === false) {
            throw new RuntimeException('No se pudo crear la solicitud de firma (CSR).');
        }

        // ── 3.5. Cargar Certificado y Clave Raíz (Root CA) central ────────────
        $rootCertPath = base_path('storage/app/private/certs/system_cert.crt');
        $rootKeyPath  = base_path('storage/app/private/certs/system_cert.key');

        if (!file_exists($rootCertPath) || !file_exists($rootKeyPath)) {
            throw new RuntimeException('No se encontró el certificado o llave raíz de BonosWeb CA. Ejecute bonosweb:generate-root-ca.');
        }

        $rootCert = file_get_contents($rootCertPath);
        $rootKey  = file_get_contents($rootKeyPath);

        // ── 4. Firmar el certificado usando el Root CA — 5 años = 1825 días ───
        $serialNumber = time() + rand(1, 1000); // To avoid exact duplicate if generated at the same second
        $x509 = openssl_csr_sign($csr, $rootCert, $rootKey, 1825, ['digest_alg' => 'sha256'], $serialNumber);

        if ($x509 === false) {
            throw new RuntimeException('No se pudo firmar el certificado X.509 con el Root CA.');
        }

        // ── 5. Contraseña segura aleatoria de 32 caracteres hexadecimales ─────
        $plainPassword = bin2hex(random_bytes(16));

        // ── 6. Empaquetar cert + clave privada en PKCS#12 (.pfx) ─────────────
        $ok = openssl_pkcs12_export($x509, $pfxContent, $privkey, $plainPassword);

        if (! $ok || empty($pfxContent)) {
            throw new RuntimeException(
                'No se pudo exportar el certificado a formato PKCS#12. ' .
                'Error OpenSSL: ' . openssl_error_string()
            );
        }

        // ── 7. Persistir en el disco privado del tenant ───────────────────────
        $relativePath = sprintf(
            'certs/employees/employee_%d_%s.pfx',
            $employeeId,
            now()->format('Ymd_His')
        );

        Storage::disk('local')->put($relativePath, $pfxContent);

        // Liberar la clave privada de la memoria
        openssl_free_key($privkey);

        return [
            'pfx_path'     => $relativePath,
            'pfx_password' => encrypt($plainPassword),
            'expires_at'   => now()->addYears(5)->toDateString(),
        ];
    }
}
