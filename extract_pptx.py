import zipfile
import xml.etree.ElementTree as ET
import sys
import os

def extract_text_from_pptx(filepath):
    text_runs = []
    try:
        with zipfile.ZipFile(filepath, 'r') as z:
            for filename in z.namelist():
                if filename.startswith('ppt/slides/') and filename.endswith('.xml'):
                    slide_data = z.read(filename)
                    root = ET.fromstring(slide_data)
                    # XML namespaces usually look like {http://schemas.openxmlformats.org/drawingml/2006/main}t
                    for elem in root.iter():
                        if elem.tag.endswith('}t') and elem.text:
                            text_runs.append(elem.text)
                    text_runs.append('\n--- SLIDE BREAK ---\n')
    except Exception as e:
        print(f"Error: {e}")
    return '\n'.join(text_runs)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python extract_pptx.py <path_to_pptx>")
        sys.exit(1)
    
    file_path = sys.argv[1]
    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        sys.exit(1)
        
    print(extract_text_from_pptx(file_path))
