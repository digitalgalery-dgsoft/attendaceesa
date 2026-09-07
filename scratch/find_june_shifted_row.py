import openpyxl
import sys

sys.stdout.reconfigure(encoding='utf-8')

f2026 = r"C:\Users\jamil\Downloads\Data Dulux\Daily Maintenance\All POST Daily Maintenance Jan-Jul 2026.xlsx"
wb = openpyxl.load_workbook(f2026, read_only=True, data_only=True)
ws = wb['Juni']

print("Checking rows in Sheet [Juni] for embedded headers or shifted columns...")
row_num = 0
for row in ws.iter_rows(values_only=True):
    row_num += 1
    # Check if this row is a header row or has 'Submission Date' or 'Nama Toko'
    vals = [str(c).strip() if c is not None else '' for c in row]
    if any('Submission Date' in v or 'Nama Toko' in v for v in vals[:5]):
        print(f"Header found at row {row_num}: {vals[:12]}")
    # Also check rows where row has 'Perdana - 226441' or 'Jaenudin'
    if any('Perdana - 226441' in v or 'Jaenudin' in v for v in vals):
        print(f"\nFound target row at Excel row {row_num}:")
        for i, val in enumerate(vals[:20]):
            if val:
                print(f"  Col {i:2d}: {val}")
