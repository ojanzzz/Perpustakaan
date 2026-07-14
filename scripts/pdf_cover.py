import fitz
import sys
import os

pdf_path = sys.argv[1]
output_path = sys.argv[2]
zoom = int(sys.argv[3]) if len(sys.argv) > 3 else 2

doc = fitz.open(pdf_path)
page = doc[0]
mat = fitz.Matrix(zoom, zoom)
pix = page.get_pixmap(matrix=mat)
pix.save(output_path)
doc.close()
