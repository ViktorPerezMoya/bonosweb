import sys
from pypdf import PdfReader, PdfWriter

def normalize_pdf(input_path, output_path, rotation_angle):
    try:
        rotation_angle = int(rotation_angle)
        
        if rotation_angle == 0:
            print("SUCCESS: Angle is 0, no rotation needed")
            sys.exit(0)

        reader = PdfReader(input_path)
        writer = PdfWriter()
        modified = False

        for page in reader.pages:
            # Apply fixed rotation from configuration
            page.rotate(rotation_angle)
            
            # IMPORTANTÍSIMO: FPDI (usado luego en el backend) ignora el flag /Rotate.
            # Debemos transferir la rotación al contenido y actualizar el MediaBox físicamente.
            page.transfer_rotation_to_content()
            modified = True
            
            writer.add_page(page)

        if modified:
            with open(output_path, "wb") as f:
                writer.write(f)
            print(f"SUCCESS: PDF rotated by {rotation_angle} degrees")

    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python normalize_rotation.py <input_pdf> <output_pdf> <rotation_angle>")
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]
    angle = sys.argv[3]
    normalize_pdf(input_pdf, output_pdf, angle)
