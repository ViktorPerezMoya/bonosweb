import sys
from pypdf import PdfReader, PdfWriter

def test():
    reader = PdfReader("C:/Users/Victor/Documents/tests/bonosweb/grifouliere/Recibos muestra.pdf")
    writer = PdfWriter()
    
    page = reader.pages[0]
    
    print("Before rotate:", page.mediabox, page.get("/Rotate", 0))
    page.rotate(90)
    print("After rotate:", page.mediabox, page.get("/Rotate", 0))
    
    page.transfer_rotation_to_content()
    print("After transfer:", page.mediabox, page.get("/Rotate", 0))
    
    writer.add_page(page)
    with open("C:/Users/Victor/Documents/tests/bonosweb/grifouliere/test_fixed_transfer.pdf", "wb") as f:
        writer.write(f)
    print("Done")

test()
