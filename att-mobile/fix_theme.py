import os
import re

lib_dir = "lib/screens"

for filename in os.listdir(lib_dir):
    if filename.endswith(".dart"):
        filepath = os.path.join(lib_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Remove hardcoded Scaffold backgrounds
        content = re.sub(r'^\s*backgroundColor:\s*const\s*Color\(0xFFF9F9FF\),\n', '', content, flags=re.MULTILINE)
        content = re.sub(r'^\s*backgroundColor:\s*Colors\.white,\n', '', content, flags=re.MULTILINE)
        
        # Remove hardcoded AppBar colors
        content = re.sub(r'^\s*backgroundColor:\s*primaryColor,\n', '', content, flags=re.MULTILINE)
        content = re.sub(r'^\s*elevation:\s*0,\n', '', content, flags=re.MULTILINE)
        # We also need to remove hardcoded text styles inside AppBar if they match exactly
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)

print("Theme cleanup complete.")
