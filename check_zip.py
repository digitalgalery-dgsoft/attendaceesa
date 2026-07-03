import zipfile
import json

with zipfile.ZipFile("vuexy-laravel-full-version.zip", "r") as z:
    for name in z.namelist():
        if "composer.json" in name:
            print(f"Found {name}")
            content = z.read(name)
            data = json.loads(content)
            print(f"Laravel Version: {data.get('require', {}).get('laravel/framework', 'N/A')}")
            break
