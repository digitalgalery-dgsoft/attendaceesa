import urllib.request
import json
import re

links = [
    ("1. Tinter Report LSO", "https://form.jotform.com/251802602821448", "Setiap ada update mengenai tinta wajib input link"),
    ("2. CBP/New Pricing", "https://form.jotform.com/242340725724453", "Maksimal tiap tgl 22, SSO dan LSO"),
    ("3. New Offtake", "https://form.jotform.com/230868137157462", "Setiap Hari, SSO dan LSO"),
    ("4. Stock End", "https://form.jotform.me/62692886701466", "Maksimal input tgl 28, akan ditarik mulai tgl 20, SSO dan LSO"),
    ("5. Out of Stock SSO", "https://form.jotform.com/211162404553445", "Setiap sabtu diinput"),
    ("6. Out of Stock LSO", "https://form.jotform.com/212318226964457", "Setiap libur diiunput seminggu sekali"),
    ("7. Data Pelanggan", "https://form.jotform.me/62721944170454", "Setiap Hari, SSO dan LSO"),
    ("8. Trafik Pembeli", "https://form.jotform.com/203297577230054", "Setiap hari, SSO dan LSO"),
    ("9. New MD", "https://form.jotform.com/AngelinaDitta/form-registrasi-mitra-dulux-", "Setiap pendaftaran new MD non Incentive, SSO dan LSO"),
    ("10. Daily Maintenance", "https://form.jotform.com/221056886433055", "Setiap Hari, SSO dan LSO"),
]

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

detailed_analysis = []

for title, url, desc in links:
    try:
        req = urllib.request.Request(url, headers=headers)
        response = urllib.request.urlopen(req, timeout=15)
        html = response.read().decode('utf-8', errors='ignore')
        
        # Find form questions in JotForm
        # Jotform items are in <li class="form-line" id="id_XX"> ... <label class="form-label"> ... <select>/<input> ...
        form_lines = re.findall(r'<li[^>]*class="[^"]*form-line[^"]*"[^>]*>(.*?)</li>', html, re.DOTALL | re.IGNORECASE)
        
        questions = []
        for fl in form_lines:
            # label
            label_match = re.search(r'<label[^>]*class="[^"]*form-label[^"]*"[^>]*>(.*?)</label>', fl, re.DOTALL | re.IGNORECASE)
            header_match = re.search(r'<(?:h1|h2|h3|div)[^>]*class="[^"]*form-header[^"]*"[^>]*>(.*?)</(?:h1|h2|h3|div)>', fl, re.DOTALL | re.IGNORECASE)
            
            lbl = ""
            if label_match:
                lbl = re.sub(r'<[^>]+>', ' ', label_match.group(1))
            elif header_match:
                lbl = re.sub(r'<[^>]+>', ' ', header_match.group(1))
            lbl = " ".join(lbl.split())
            
            # input type / options
            has_select = '<select' in fl
            has_textarea = '<textarea' in fl
            has_file = 'type="file"' in fl or 'qq-upload' in fl
            has_radio = 'type="radio"' in fl
            has_checkbox = 'type="checkbox"' in fl
            has_number = 'data-type="control_number"' in fl or 'type="number"' in fl
            has_date = 'data-type="control_datetime"' in fl or 'data-type="control_date"' in fl or 'type="date"' in fl
            
            options = []
            if has_select:
                options = re.findall(r'<option[^>]*value="([^"]*)"[^>]*>(.*?)</option>', fl, re.DOTALL | re.IGNORECASE)
                options = [" ".join(re.sub(r'<[^>]+>', '', opt[1]).split()) for opt in options if opt[0] != ""]
            elif has_radio or has_checkbox:
                opt_labels = re.findall(r'<label[^>]*for="[^"]*"[^>]*>(.*?)</label>', fl, re.DOTALL | re.IGNORECASE)
                options = [" ".join(re.sub(r'<[^>]+>', '', o).split()) for o in opt_labels]
                
            input_type = "text"
            if has_date: input_type = "date"
            elif has_file: input_type = "camera_photo"
            elif has_select: input_type = "dropdown"
            elif has_radio: input_type = "radio"
            elif has_checkbox: input_type = "checkbox"
            elif has_textarea: input_type = "textarea"
            elif has_number: input_type = "number"
            
            if lbl:
                questions.append({
                    'label': lbl,
                    'type': input_type,
                    'options_sample': options[:10] if options else []
                })
                
        detailed_analysis.append({
            'title': title,
            'url': url,
            'desc': desc,
            'questions_count': len(questions),
            'questions': questions
        })
    except Exception as e:
        detailed_analysis.append({
            'title': title,
            'url': url,
            'desc': desc,
            'error': str(e)
        })

with open('dulux_detailed_analysis.json', 'w', encoding='utf-8') as f:
    json.dump(detailed_analysis, f, indent=2, ensure_ascii=False)

print("Saved detailed analysis for 10 forms to dulux_detailed_analysis.json")
