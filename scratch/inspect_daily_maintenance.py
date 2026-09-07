import os
import glob

folder = r"C:\Users\jamil\Downloads\Data Dulux\Daily Maintenance"
print("Checking folder:", folder)
if os.path.exists(folder):
    files = glob.glob(os.path.join(folder, "**/*"), recursive=True)
    print(f"Found {len(files)} items:")
    for f in files:
        print(" -", f, f"({os.path.getsize(f)} bytes)" if os.path.isfile(f) else "[DIR]")
else:
    print("Folder does not exist. Checking C:\\Users\\jamil\\Downloads\\Data Dulux:")
    parent = r"C:\Users\jamil\Downloads\Data Dulux"
    if os.path.exists(parent):
        print(os.listdir(parent))
