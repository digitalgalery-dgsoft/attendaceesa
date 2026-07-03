import os
import re

base_dir = r"g:\My File\Project APlikasi Absensi\New\att-admin-v12\app\Filament\Resources"

for root, dirs, files in os.walk(base_dir):
    for file in files:
        if file.endswith("Resource.php"):
            file_path = os.path.join(root, file)
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Find the injected property and remove it
            match = re.search(r"protected static \?string \$navigationGroup = '(.*?)';", content)
            if match:
                group = match.group(1)
                content = content.replace(match.group(0), f"protected static string | \BackEnum | null $navigationGroup = '{group}';") # wait, the error said UnitEnum
                
                # let's just use the method instead
                method_code = f"\n    public static function getNavigationGroup(): ?string\n    {{\n        return '{group}';\n    }}\n"
                content = content.replace(match.group(0), "")
                
                # insert method at the end of class before last }
                content = re.sub(r"}\s*$", method_code + "}\n", content)
                
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Fixed {file_path}")
