import json

# Check the migration or model for RPT-DULUX-STOCK-END
# Let's see Submissions sheet headers:
# 1. Submission Date
# 2. Tanggal Pencatatan Stok
# 3. Region
# 4. Area
# 5. SAP
# 6. Nama Toko
# 7. Keterangan
# 8. Brand
# 9. Produk
# 10. Warna
# 11. Kemasan Galon
# 12. Kuantiti Galon
# 13. Kemasan Pail
# 14. Kuantiti Pail
# 15. Vol (Liter)
# 16. conf

sub_headers = [
    'Submission Date', 'Tanggal Pencatatan Stok', 'Region', 'Area', 'SAP',
    'Nama Toko', 'Keterangan', 'Brand', 'Produk', 'Warna', 'Kemasan Galon',
    'Kuantiti Galon', 'Kemasan Pail', 'Kuantiti Pail', 'Vol (Liter)', 'conf'
]
print('Submissions headers:', sub_headers)
