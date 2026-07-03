import os
import re

groups = {
    'Companies': 'Master Data',
    'Branches': 'Master Data',
    'Departments': 'Master Data',
    'Positions': 'Master Data',
    'Shifts': 'Master Data',
    'WorkLocations': 'Master Data',
    'Employees': 'Master Data',
    'Users': 'System',
    'Attendances': 'Attendance',
}

base_dir = r"g:\My File\Project APlikasi Absensi\New\att-admin-v12\app\Filament\Resources"

for folder, group in groups.items():
    file_path = os.path.join(base_dir, folder, f"{folder[:-1] if folder.endswith('s') else folder}Resource.php")
    if folder == 'Companies': file_path = os.path.join(base_dir, folder, "CompanyResource.php")
    if folder == 'Branches': file_path = os.path.join(base_dir, folder, "BranchResource.php")
    
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if 'getNavigationGroup' not in content and '$navigationGroup' not in content:
            method_code = f"\n    public static function getNavigationGroup(): ?string\n    {{\n        return '{group}';\n    }}\n"
            content = re.sub(r"}\s*$", method_code + "}\n", content)
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated {file_path}")
    else:
        print(f"File not found: {file_path}")
