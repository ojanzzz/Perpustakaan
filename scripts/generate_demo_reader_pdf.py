from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import PageBreak, Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "database" / "seeders" / "assets" / "demo-reader.pdf"


def footer(canvas, document):
    canvas.saveState()
    canvas.setStrokeColor(colors.HexColor("#E2E8F0"))
    canvas.line(20 * mm, 16 * mm, 190 * mm, 16 * mm)
    canvas.setFillColor(colors.HexColor("#64748B"))
    canvas.setFont("Helvetica", 8)
    canvas.drawString(20 * mm, 10 * mm, "E-Perpustakaan Digital KPU - Dokumen Demo Original")
    canvas.drawRightString(190 * mm, 10 * mm, f"Halaman {document.page}")
    canvas.restoreState()


def build():
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = SimpleDocTemplate(
        str(OUTPUT), pagesize=A4,
        rightMargin=20 * mm, leftMargin=20 * mm,
        topMargin=22 * mm, bottomMargin=22 * mm,
        title="Panduan Literasi Demokrasi - Dokumen Demo",
        author="Tim Pengembang E-Perpustakaan Digital KPU",
    )
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle(name="CoverTitle", parent=styles["Title"], fontName="Helvetica-Bold", fontSize=28, leading=34, alignment=TA_CENTER, textColor=colors.HexColor("#0F2747"), spaceAfter=14))
    styles.add(ParagraphStyle(name="Section", parent=styles["Heading1"], fontName="Helvetica-Bold", fontSize=21, leading=26, textColor=colors.HexColor("#B91C1C"), spaceAfter=12))
    styles.add(ParagraphStyle(name="BodyDemo", parent=styles["BodyText"], fontName="Helvetica", fontSize=11, leading=18, textColor=colors.HexColor("#334155"), spaceAfter=10))
    styles.add(ParagraphStyle(name="Callout", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=12, leading=18, textColor=colors.white, backColor=colors.HexColor("#0F2747"), borderPadding=12, spaceBefore=8, spaceAfter=16))

    story = [
        Spacer(1, 38 * mm),
        Paragraph("PANDUAN LITERASI<br/>DEMOKRASI DIGITAL", styles["CoverTitle"]),
        Paragraph("Dokumen demo original untuk pengujian pembaca digital", ParagraphStyle(name="Subtitle", parent=styles["BodyDemo"], alignment=TA_CENTER, fontSize=14, leading=20)),
        Spacer(1, 18 * mm),
        Table([["E-PERPUSTAKAAN", "EDISI DEMO 2026"]], colWidths=[85 * mm, 85 * mm], style=TableStyle([
            ("BACKGROUND", (0, 0), (0, 0), colors.HexColor("#B91C1C")),
            ("BACKGROUND", (1, 0), (1, 0), colors.HexColor("#C59A3D")),
            ("TEXTCOLOR", (0, 0), (-1, -1), colors.white),
            ("FONTNAME", (0, 0), (-1, -1), "Helvetica-Bold"),
            ("ALIGN", (0, 0), (-1, -1), "CENTER"),
            ("TOPPADDING", (0, 0), (-1, -1), 12),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 12),
        ])),
        Spacer(1, 35 * mm),
        Paragraph("Materi ini dibuat khusus untuk pengembangan aplikasi. Tidak memuat publikasi pihak lain dan dapat digunakan sebagai data demonstrasi.", ParagraphStyle(name="Disclaimer", parent=styles["BodyDemo"], alignment=TA_CENTER, textColor=colors.HexColor("#64748B"))),
        PageBreak(),
    ]

    chapters = [
        ("Daftar Isi", "Gunakan panel thumbnail atau daftar isi pada pembaca digital untuk berpindah bagian. Dokumen ini sengaja memiliki beberapa halaman agar navigasi, pencarian teks, zoom, dan animasi pergantian halaman dapat diuji."),
        ("1. Memahami Informasi Publik", "Informasi publik yang jelas membantu warga memahami tahapan, layanan, dan keputusan kelembagaan. Literasi dimulai dari kebiasaan memeriksa sumber, tanggal penerbitan, konteks, serta tujuan sebuah dokumen."),
        ("2. Mengenali Sumber Tepercaya", "Periksa alamat situs, identitas penerbit, nomor dokumen, serta konsistensi isi. Gunakan katalog resmi dan hindari menyebarkan potongan informasi yang tidak menyertakan konteks lengkap."),
        ("3. Membaca Data secara Kritis", "Angka perlu dibaca bersama definisi, periode, dan metode pengumpulan. Grafik atau tabel tidak berdiri sendiri. Catat pertanyaan penting dan gunakan bookmark agar bagian tersebut mudah ditemukan kembali."),
        ("4. Partisipasi Warga", "Partisipasi yang bermakna tumbuh dari akses informasi yang setara. Warga dapat membaca, berdiskusi, menyampaikan saran, dan melaporkan dokumen bermasalah melalui saluran yang disediakan."),
        ("5. Etika di Ruang Digital", "Hormati privasi, hindari ujaran merendahkan, dan koreksi informasi secara bertanggung jawab. Jangan mempublikasikan data pribadi yang tidak relevan dengan kepentingan publik."),
        ("6. Aksesibilitas Informasi", "Dokumen digital perlu dapat digunakan dengan keyboard, pembaca layar, perbesaran teks, kontras yang baik, dan alternatif mode scroll ketika animasi tidak nyaman bagi pengguna."),
        ("7. Praktik Membaca Efektif", "Mulai dari ringkasan, gunakan pencarian untuk istilah penting, tandai halaman utama, lalu kembali ke konteks sebelum menarik kesimpulan. Pembaca digital menyimpan halaman terakhir agar sesi dapat dilanjutkan."),
        ("8. Keamanan Dokumen", "Tautan baca memiliki masa berlaku dan file asli disimpan secara privat. Izin unduh dan cetak ditentukan oleh pengelola koleksi, bukan hanya oleh keberadaan sebuah alamat file."),
        ("9. Glosarium Ringkas", "Katalog adalah daftar terstruktur koleksi. Metadata adalah keterangan seperti judul, penulis, kategori, dan tahun. Bookmark adalah penanda halaman. Audit log adalah catatan aktivitas penting yang tidak dapat diedit."),
        ("Penutup", "Literasi demokrasi digital memerlukan rasa ingin tahu, ketelitian, dan tanggung jawab. Gunakan informasi resmi sebagai pijakan, bandingkan konteks, dan manfaatkan kanal umpan balik ketika menemukan masalah."),
    ]

    for index, (title, body) in enumerate(chapters):
        story.append(Paragraph(title, styles["Section"]))
        story.append(Paragraph(body, styles["BodyDemo"]))
        story.append(Paragraph("Kata kunci: demokrasi, informasi publik, literasi digital, partisipasi warga.", styles["Callout"]))
        for paragraph in range(4):
            story.append(Paragraph(f"Catatan pembelajaran {paragraph + 1}. Pembaca dapat menggunakan zoom, mode satu atau dua halaman, pencarian teks, thumbnail, fullscreen, serta mode scroll untuk menyesuaikan pengalaman membaca.", styles["BodyDemo"]))
        if index < len(chapters) - 1:
            story.append(PageBreak())

    doc.build(story, onFirstPage=footer, onLaterPages=footer)
    print(OUTPUT)


if __name__ == "__main__":
    build()
