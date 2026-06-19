import sys
from pypdf import PdfReader

def debug_pdf(input_path):
    try:
        reader = PdfReader(input_path)
        print(f"--- DEBUGGING PDF: {input_path} ---")
        print(f"Total Pages: {len(reader.pages)}")
        
        for i, page in enumerate(reader.pages):
            mb = page.mediabox
            width = float(mb.width)
            height = float(mb.height)
            
            # Obtener el atributo de rotación original (puede ser nulo o ausente)
            rotate = page.get('/Rotate', 0)
            
            # Calcular las dimensiones visuales reales
            # Si está rotado 90 o 270 grados, el ancho y alto visuales se invierten
            if rotate % 180 == 90:
                visual_width = height
                visual_height = width
            else:
                visual_width = width
                visual_height = height
                
            print(f"\n[Página {i+1}]")
            print(f"  MediaBox (Físico): Width = {width:.2f}, Height = {height:.2f}")
            print(f"  Etiqueta /Rotate : {rotate} grados")
            print(f"  Dimensión Visual : Width = {visual_width:.2f}, Height = {visual_height:.2f}")
            print(f"  Formato Visual   : {'PORTRAIT (Vertical)' if visual_height > visual_width else 'LANDSCAPE (Apaisado)'}")

    except Exception as e:
        print(f"ERROR: {str(e)}", file=sys.stderr)

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python debug_pdf.py <input_pdf>")
        sys.exit(1)
    debug_pdf(sys.argv[1])
