<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Servicio de generación de certificados digitales PKCS#12 para empresas.
 *
 * Responsabilidad única: dado el nombre, CUIT e ID de una Company, genera un
 * par de claves RSA 2048-bit, construye un certificado X.509 autofirmado con
 * SHA-256 y lo empaqueta en formato .pfx protegido por una contraseña segura.
 *
 * DEBE invocarse dentro del contexto del tenant (dentro de $tenant->run() o
 * desde una petición de tenant), ya que Storage::disk('local') apuntará al
 * directorio privado del tenant: storage/tenant{id}/app/
 */
class CompanyCertificateGenerator
{
    /**
     * Genera el certificado .pfx para una empresa y lo persiste en el disco
     * privado del tenant.
     *
     * @param  string  $companyName  Razón social de la empresa
     * @param  string  $cuit         CUIT sin guiones (11 dígitos)
     * @param  int     $companyId    ID de la empresa en la BD del tenant
     *
     * @return array{pfx_path: string, pfx_password: string, expires_at: string}
     *   - pfx_path:     Ruta relativa dentro del disco 'local' del tenant
     *   - pfx_password: Contraseña cifrada con encrypt() lista para la BD
     *   - expires_at:   Fecha de vencimiento en formato Y-m-d (5 años)
     *
     * @throws RuntimeException Si OpenSSL no puede generar o exportar el certificado
     */
    public function generate(string $companyName, string $cuit, int $companyId): array
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

        // ── 2. Distinguished Name con metadatos de la empresa ─────────────────
        // El campo serialNumber almacena el CUIT como identificador único legal.
        $dn = [
            'countryName'            => 'AR',
            'stateOrProvinceName'    => 'Mendoza',
            'localityName'           => 'Mendoza',
            'organizationName'       => 'bonosweb.com.ar',
            //'organizationalUnitName' => 'Recursos Humanos',
            'commonName'             => $companyName,
            'serialNumber'           => $cuit,
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
        $x509 = openssl_csr_sign($csr, $rootCert, $rootKey, 1825, ['digest_alg' => 'sha256']);

        if ($x509 === false) {
            throw new RuntimeException('No se pudo firmar el certificado X.509 con el Root CA.');
        }

        // ── 5. Contraseña segura aleatoria de 32 caracteres hexadecimales ─────
        // random_bytes() usa una fuente criptográficamente segura (CSPRNG).
        $plainPassword = bin2hex(random_bytes(16));

        // ── 6. Empaquetar cert + clave privada en PKCS#12 (.pfx) ─────────────
        // Incluimos el Root CA en 'extracerts' para adjuntar la cadena de confianza
        $ok = openssl_pkcs12_export($x509, $pfxContent, $privkey, $plainPassword, ['extracerts' => $rootCert]);

        if (! $ok || empty($pfxContent)) {
            throw new RuntimeException(
                'No se pudo exportar el certificado a formato PKCS#12. ' .
                'Error OpenSSL: ' . openssl_error_string()
            );
        }

        // ── 6. Persistir en el disco privado del tenant ───────────────────────
        // En contexto tenant: Storage::disk('local') → storage/tenant{id}/app/
        // La subcarpeta 'certs/' NO es pública (no tiene symlink).
        $relativePath = sprintf(
            'certs/company_%d_%s.pfx',
            $companyId,
            now()->format('Ymd_His')
        );

        Storage::disk('local')->put($relativePath, $pfxContent);

        // Liberar la clave privada de la memoria
        openssl_free_key($privkey);

        return [
            'pfx_path'     => $relativePath,
            // encrypt() usa la APP_KEY para cifrado AES-256-CBC: seguro para BD
            'pfx_password' => encrypt($plainPassword),
            'expires_at'   => now()->addYears(5)->toDateString(),
        ];
    }
}
