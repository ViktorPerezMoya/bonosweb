import sys
import os
import argparse
from pyhanko.sign import signers

def print_pfx_info(pfx_path, pfx_pwd):
    signer = signers.SimpleSigner.load_pkcs12(pfx_path, passphrase=pfx_pwd.encode('utf-8'))
    print("Signer Subject:", signer.signing_cert.subject.human_friendly)
    print("Signer Issuer:", signer.signing_cert.issuer.human_friendly)

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--pfx', required=True)
    parser.add_argument('--pwd', required=True)
    args = parser.parse_args()
    print_pfx_info(args.pfx, args.pwd)
