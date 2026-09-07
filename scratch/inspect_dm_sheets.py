import zipfile
import xml.etree.ElementTree as ET

files = [
    r"C:\Users\jamil\Downloads\Data Dulux\Daily Maintenance\All POST Daily Maintenance Jan-Des 2025.xlsx",
    r"C:\Users\jamil\Downloads\Data Dulux\Daily Maintenance\All POST Daily Maintenance Jan-Jul 2026.xlsx"
]

for fpath in files:
    print("=" * 60)
    print("Inspecting:", fpath)
    with zipfile.ZipFile(fpath, 'r') as z:
        # Read workbook.xml to list sheets
        wb_xml = z.read('xl/workbook.xml')
        root = ET.fromstring(wb_xml)
        sheets = root.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')
        print(f"Sheet count: {len(sheets)}")
        for s in sheets:
            print(" - Sheet:", s.attrib.get('name'), "id:", s.attrib.get('sheetId'))
