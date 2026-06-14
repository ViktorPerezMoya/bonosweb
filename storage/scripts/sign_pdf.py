import sys
import argparse
import warnings

# Suprimir warnings de obsolescencia de cryptography
try:
    from cryptography.utils import CryptographyDeprecationWarning
    warnings.filterwarnings("ignore", category=CryptographyDeprecationWarning)
except ImportError:
    pass

# Importaciones de PyHanko
from pyhanko.sign import signers
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
from pyhanko_certvalidator.registry import SimpleCertificateStore

# Importaciones criptográficas (cryptography y asn1crypto)
from cryptography.hazmat.primitives.serialization import pkcs12
from cryptography.hazmat.primitives import serialization
from asn1crypto import x509, keys

def get_matching_cert_and_key(pfx_bytes, password):
    """
    Extrae y empareja matemáticamente la llave privada con su certificado exacto,
    evitando el bug de orden de exportación de PHP/OpenSSL.
    Retorna la llave, el certificado hoja y los certificados adicionales en formato 'cryptography'.
    """
    private_key, main_cert, additional_certs = pkcs12.load_key_and_certificates(
        pfx_bytes, 
        password.encode('utf-8')
    )

    leaf_cert = None
    priv_pub_numbers = private_key.public_key().public_numbers()

    # Buscar cuál certificado hace match con la llave privada
    if main_cert and main_cert.public_key().public_numbers() == priv_pub_numbers:
        leaf_cert = main_cert
    elif additional_certs:
        for extra in additional_certs:
            if extra.public_key().public_numbers() == priv_pub_numbers:
                leaf_cert = extra
                break
                
    if leaf_cert is None:
        raise ValueError("No se encontró un certificado coincidente con la llave privada en el PFX.")
        
    return private_key, leaf_cert, additional_certs

def convert_to_asn1crypto(private_key, leaf_cert, additional_certs):
    """
    Convierte los objetos de 'cryptography' al formato 'asn1crypto' 
    que requiere PyHanko internamente para armar el almacén y firmar.
    """
    # 1. Convertir Llave Privada
    priv_bytes = private_key.private_bytes(
        encoding=serialization.Encoding.DER,
        format=serialization.PrivateFormat.PKCS8,
        encryption_algorithm=serialization.NoEncryption()
    )
    priv_key_asn1 = keys.PrivateKeyInfo.load(priv_bytes)

    # 2. Convertir Certificado Hoja
    leaf_cert_asn1 = x509.Certificate.load(leaf_cert.public_bytes(serialization.Encoding.DER))

    # 3. Crear almacén SOLO con el certificado del empleado (Ignoramos additional_certs)
    cert_store = SimpleCertificateStore()
    cert_store.register(leaf_cert_asn1)

    return priv_key_asn1, leaf_cert_asn1, cert_store

def sign_pdf_incremental(in_path, out_path, pfx_path, pfx_pwd, reason, location, name):
    try:
        with open(pfx_path, "rb") as f:
            pfx_bytes = f.read()

        # Paso A: Extraer de forma segura
        private_key, leaf_cert, additional_certs = get_matching_cert_and_key(pfx_bytes, pfx_pwd)

        # Paso B: Traducir formatos
        priv_key_asn1, leaf_cert_asn1, cert_store = convert_to_asn1crypto(private_key, leaf_cert, additional_certs)

        # Paso C: Inicializar firmador de PyHanko
        signer = signers.SimpleSigner(
            signing_cert=leaf_cert_asn1, 
            signing_key=priv_key_asn1, 
            cert_registry=cert_store
        )
        
        # Paso D: Escribir firma incremental
        with open(in_path, 'rb') as doc:
            writer = IncrementalPdfFileWriter(doc)
            
            num_sigs = len(writer.prev.embedded_signatures) if writer.prev.embedded_signatures else 0
            field_name = f"Signature{num_sigs + 1}"
            
            with open(out_path, 'wb') as outf:
                signers.sign_pdf(
                    writer,
                    signature_meta=signers.PdfSignatureMetadata(
                        field_name=field_name,
                        reason=reason,
                        location=location,
                        name=name,
                    ),
                    signer=signer,
                    in_place=False,
                    output=outf
                )
        print("SUCCESS")
        
    except Exception as e:
        import traceback
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="Incremental PDF Signer")
    parser.add_argument('--in', dest='in_path', required=True)
    parser.add_argument('--out', dest='out_path', required=True)
    parser.add_argument('--pfx', dest='pfx_path', required=True)
    parser.add_argument('--pwd', dest='pfx_pwd', required=True)
    parser.add_argument('--reason', dest='reason', required=True)
    parser.add_argument('--location', dest='location', required=True)
    parser.add_argument('--name', dest='name', required=True)
    
    args = parser.parse_args()
    
    sign_pdf_incremental(
        args.in_path,
        args.out_path,
        args.pfx_path,
        args.pfx_pwd,
        args.reason,
        args.location,
        args.name
    )