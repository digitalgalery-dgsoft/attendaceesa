import urllib.request
import urllib.parse
import json
import re
from html.parser import HTMLParser

class SimpleParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.in_label = False
        self.in_title = False
        self.title = ""
        self.labels = []
        self.current_text = []

    def handle_starttag(self, tag, attrs):
        attrs_dict = dict(attrs)
        if tag == 'title':
            self.in_title = True
        cls = attrs_dict.get('class', '')
        if tag in ['label', 'div', 'span', 'h1', 'h2', 'h3', 'legend'] and ('form-label' in cls or 'form-header' in cls or 'question' in cls or 'M7eMe' in cls or 'freebird' in cls):
            self.in_label = True
            self.current_text = []

    def handle_endtag(self, tag):
        if tag == 'title':
            self.in_title = False
        if self.in_label and tag in ['label', 'div', 'span', 'h1', 'h2', 'h3', 'legend']:
            text = " ".join("".join(self.current_text).split())
            if text and len(text) < 150 and text not in self.labels:
                self.labels.append(text)
            self.in_label = False
            self.current_text = []

    def handle_data(self, data):
        if self.in_title:
            self.title += data.strip()
        if self.in_label:
            self.current_text.append(data)

links = [
    ("1. Tinter Report LSO", "https://bit.ly/Stock_Tinter_LSO", "Setiap ada update mengenai tinta wajib input link"),
    ("2. CBP/New Pricing", "https://bit.ly/PriceReport_New", "Maksimal tiap tgl 22, SSO dan LSO"),
    ("3. New Offtake", "https://bit.ly/New_OfftakeReport", "Setiap Hari, SSO dan LSO"),
    ("4. Stock End", "https://form.jotform.me/62692886701466", "Maksimal input tgl 28, akan ditarik mulai tgl 20, SSO dan LSO"),
    ("5. Out of Stock SSO", "http://bit.ly/New_OOS", "Setiap sabtu diinput"),
    ("6. Out of Stock LSO", "https://form.jotform.com/212318226964457", "Setiap libur diiunput seminggu sekali"),
    ("7. Data Pelanggan", "https://form.jotform.me/62721944170454", "Setiap Hari, SSO dan LSO"),
    ("8. Trafik Pembeli", "https://form.jotform.com/203297577230054", "Setiap hari, SSO dan LSO"),
    ("9. New MD", "https://bit.ly/MitraDulux", "Setiap pendaftaran new MD non Incentive, SSO dan LSO"),
    ("10. Daily Maintenance", "https://form.jotform.com/221056886433055", "Setiap Hari, SSO dan LSO"),
]

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

results = []

for title, url, desc in links:
    print(f"\n=======================================================")
    print(f"Fetching: {title} ({url})")
    try:
        req = urllib.request.Request(url, headers=headers)
        response = urllib.request.urlopen(req, timeout=15)
        final_url = response.geturl()
        html = response.read().decode('utf-8', errors='ignore')
        
        parser = SimpleParser()
        parser.feed(html)
        
        # Also regex search for jotform labels or google form questions
        regex_labels = re.findall(r'<label[^>]*class="[^"]*form-label[^"]*"[^>]*>(.*?)</label>', html, re.DOTALL | re.IGNORECASE)
        clean_regex = []
        for rl in regex_labels:
            txt = re.sub(r'<[^>]+>', ' ', rl)
            txt = " ".join(txt.split())
            if txt and txt not in clean_regex:
                clean_regex.append(txt)
                
        all_labels = list(dict.fromkeys(parser.labels + clean_regex))
        
        print(f"Final URL: {final_url}")
        print(f"Page Title: {parser.title}")
        print(f"Extracted ({len(all_labels)} labels):")
        for f in all_labels[:15]:
            print(f"  * {f}")
            
        results.append({
            'name': title,
            'source_url': url,
            'final_url': final_url,
            'description': desc,
            'page_title': parser.title,
            'fields_sample': all_labels[:35],
            'html_snippet': html[:1000]
        })
    except Exception as e:
        print(f"Error fetching {url}: {e}")
        results.append({
            'name': title,
            'source_url': url,
            'error': str(e)
        })

with open('dulux_links_analysis.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, indent=2, ensure_ascii=False)

print("\nAnalysis saved to dulux_links_analysis.json")
