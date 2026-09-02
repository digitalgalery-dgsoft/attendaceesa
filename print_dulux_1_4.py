import json

with open('dulux_detailed_analysis.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

for item in data[:4]:
    print(f"\n=======================================================")
    print(f"FORM: {item['title']}")
    print(f"URL: {item['url']}")
    print(f"Jadwal/Aturan: {item['desc']}")
    print(f"Total Fields: {item.get('questions_count', 0)}")
    print("Questions:")
    for q in item.get('questions', []):
        opts = f" (Opsi: {', '.join(q['options_sample'][:4])}...)" if q.get('options_sample') else ""
        print(f"  - [{q['type']}] {q['label']}{opts}")
