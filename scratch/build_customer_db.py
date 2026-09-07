import os
import gzip
import shutil
import sqlite3
import datetime
import openpyxl

excel_file = r"C:\Users\jamil\Downloads\Data Dulux\DC_-_Database_Pelanggan_Report 2025 - 2026.xlsx"
out_dir = r"g:\My File\Project APlikasi Absensi\New\att-admin-v12\storage\app\dulux_data"
os.makedirs(out_dir, exist_ok=True)

db_path = os.path.join(out_dir, "customer_db.sqlite")
gz_path = os.path.join(out_dir, "customer_db.sqlite.gz")

if os.path.exists(db_path):
    try:
        os.remove(db_path)
    except:
        pass

conn = sqlite3.connect(db_path)
cur = conn.cursor()

cur.execute("PRAGMA journal_mode = WAL;")
cur.execute("PRAGMA synchronous = NORMAL;")

cur.execute("""
CREATE TABLE cust_raw (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    year INTEGER,
    month INTEGER,
    submission_date TEXT,
    tanggal TEXT,
    region TEXT,
    area TEXT,
    rsm_area TEXT,
    store_name TEXT,
    sap_code TEXT,
    sap_gab TEXT,
    nama_pelanggan TEXT,
    alamat TEXT,
    no_hp TEXT,
    tipe_pelanggan TEXT,
    painter_info TEXT,
    tujuan_ke_toko TEXT,
    brand_dicari TEXT,
    brand_dibeli TEXT,
    alasan TEXT,
    tipe_pengecatan TEXT,
    memerlukan_preview TEXT,
    value_pembelian REAL,
    nama_dc TEXT,
    keterangan TEXT,
    foto_1 TEXT,
    foto_2 TEXT,
    foto_3 TEXT,
    is_switched INTEGER DEFAULT 0,
    is_dulux_bought INTEGER DEFAULT 0
)
""")

cur.execute("CREATE INDEX idx_cust_year_month ON cust_raw(year, month);")
cur.execute("CREATE INDEX idx_cust_rsm ON cust_raw(rsm_area);")
cur.execute("CREATE INDEX idx_cust_area ON cust_raw(area);")
cur.execute("CREATE INDEX idx_cust_store ON cust_raw(store_name);")
cur.execute("CREATE INDEX idx_cust_sap ON cust_raw(sap_code);")
cur.execute("CREATE INDEX idx_cust_tipe ON cust_raw(tipe_pelanggan);")
cur.execute("CREATE INDEX idx_cust_alasan ON cust_raw(alasan);")
cur.execute("CREATE INDEX idx_cust_switched ON cust_raw(is_switched);")
cur.execute("CREATE INDEX idx_cust_dulux_bought ON cust_raw(is_dulux_bought);")

conn.commit()

def clean_val(v):
    if v is None:
        return ''
    return str(v).strip()

def parse_num(v):
    if v is None:
        return 0.0
    s = str(v).replace('Rp', '').replace('.', '').replace(',', '.').strip()
    try:
        return float(s)
    except:
        return 0.0

def is_dulux(s):
    if not s: return False
    s_low = str(s).lower()
    return any(b in s_low for b in ['dulux', 'catylac', 'aquashield', 'pentalite', 'weathershield', 'easyclean', 'ambiance', 'hammerite'])

def is_competitor(s):
    if not s: return False
    s_low = str(s).lower()
    return any(b in s_low for b in ['jotun', 'nippon', 'vinilex', 'avian', 'avitex', 'no drop', 'mowilex', 'propan', 'lenkote', 'danapaint', 'kansai', 'kemtone', 'belazo', 'aries', 'paragon', 'decolith'])

def standardize_rsm(rsm_raw):
    r = clean_val(rsm_raw)
    if not r:
        return 'Other'
    r_up = r.upper()
    if 'GREATER JAKARTA' in r_up:
        return 'Greater Jakarta'
    elif 'WEST JAVA' in r_up:
        return 'West Java'
    elif 'NORTH CENTRAL JAVA' in r_up:
        return 'North Central Java'
    elif 'SOUTH CENTRAL JAVA' in r_up:
        return 'South Central Java'
    elif 'EAST JAVA' in r_up:
        return 'East Java'
    elif 'SULAWESI' in r_up:
        return 'Sulawesi'
    elif 'BALI' in r_up or 'NUSRA' in r_up:
        return 'Bali Nusra'
    elif 'KALIMANTAN' in r_up:
        return 'Kalimantan'
    elif 'SOUTH SUMATRA' in r_up or 'SUMATERA SELATAN' in r_up:
        return 'South Sumatera'
    elif 'CENTRAL SUMATRA' in r_up or 'SUMATERA TENGAH' in r_up:
        return 'Central Sumatera'
    elif 'NORTH SUMATRA' in r_up or 'SUMATERA UTARA' in r_up:
        return 'North Sumatera'
    return r

print(f"Loading {excel_file}...")
wb = openpyxl.load_workbook(excel_file, read_only=True)
sheet = wb['Sheet1']

rows = list(sheet.iter_rows(values_only=True))
data_rows = rows[1:]

batch = []
total_count = 0

for row in data_rows:
    sub_date = clean_val(row[0])
    tgl_raw = row[1]
    
    # Format Tanggal
    tgl_str = ''
    m_int = 1
    y_int = 2025
    
    if isinstance(tgl_raw, (datetime.datetime, datetime.date)):
        tgl_str = tgl_raw.strftime('%d/%m/%Y')
        m_int = tgl_raw.month
        y_int = tgl_raw.year
    elif tgl_raw:
        s = clean_val(tgl_raw)
        tgl_str = s.split(' ')[0]
        try:
            parts = tgl_str.replace('-', '/').split('/')
            if len(parts) == 3:
                if len(parts[0]) == 4: # YYYY/MM/DD
                    y_int = int(parts[0])
                    m_int = int(parts[1])
                    tgl_str = f"{parts[2]}/{parts[1]}/{parts[0]}"
                else: # DD/MM/YYYY or MM/DD/YYYY
                    y_int = int(parts[2])
                    m_int = int(parts[1])
        except:
            pass
            
    # Explicit Year from Col 2 if valid
    col2_y = row[2]
    if col2_y and str(col2_y).isdigit():
        y_int = int(col2_y)
        
    region = clean_val(row[3])
    area = clean_val(row[4])
    rsm_area = standardize_rsm(row[5])
    store_name = clean_val(row[6])
    sap_code = clean_val(row[7])
    sap_gab = clean_val(row[8])
    
    # Normalize SAP
    if not sap_code and '-' in store_name:
        parts = store_name.split('-')
        last_part = parts[-1].strip()
        if last_part.isdigit():
            sap_code = last_part
            
    nama_pelanggan = clean_val(row[9])
    alamat = clean_val(row[10])
    no_hp = clean_val(row[11])
    tipe_pelanggan = clean_val(row[12])
    painter_info = clean_val(row[13])
    tujuan_ke_toko = clean_val(row[14])
    brand_dicari = clean_val(row[15])
    brand_dibeli = clean_val(row[16])
    alasan = clean_val(row[17])
    tipe_pengecatan = clean_val(row[18])
    memerlukan_preview = clean_val(row[19])
    value_pembelian = parse_num(row[20])
    nama_dc = clean_val(row[21])
    keterangan = clean_val(row[22])
    foto_1 = clean_val(row[23]) if len(row) > 23 else ''
    foto_2 = clean_val(row[24]) if len(row) > 24 else ''
    foto_3 = clean_val(row[25]) if len(row) > 25 else ''
    
    # Calculate Switch and Dulux Bought
    dicari_comp = is_competitor(brand_dicari) and not is_dulux(brand_dicari)
    dibeli_dulux = is_dulux(brand_dibeli)
    
    is_switched = 1 if (dicari_comp and dibeli_dulux) else 0
    is_dulux_bought = 1 if dibeli_dulux else 0
    
    batch.append((
        y_int, m_int, sub_date, tgl_str, region, area, rsm_area, store_name, sap_code, sap_gab,
        nama_pelanggan, alamat, no_hp, tipe_pelanggan, painter_info, tujuan_ke_toko,
        brand_dicari, brand_dibeli, alasan, tipe_pengecatan, memerlukan_preview,
        value_pembelian, nama_dc, keterangan, foto_1, foto_2, foto_3,
        is_switched, is_dulux_bought
    ))
    
    if len(batch) >= 1000:
        cur.executemany("""
        INSERT INTO cust_raw (
            year, month, submission_date, tanggal, region, area, rsm_area, store_name, sap_code, sap_gab,
            nama_pelanggan, alamat, no_hp, tipe_pelanggan, painter_info, tujuan_ke_toko,
            brand_dicari, brand_dibeli, alasan, tipe_pengecatan, memerlukan_preview,
            value_pembelian, nama_dc, keterangan, foto_1, foto_2, foto_3,
            is_switched, is_dulux_bought
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, batch)
        conn.commit()
        total_count += len(batch)
        batch = []

if batch:
    cur.executemany("""
    INSERT INTO cust_raw (
        year, month, submission_date, tanggal, region, area, rsm_area, store_name, sap_code, sap_gab,
        nama_pelanggan, alamat, no_hp, tipe_pelanggan, painter_info, tujuan_ke_toko,
        brand_dicari, brand_dibeli, alasan, tipe_pengecatan, memerlukan_preview,
        value_pembelian, nama_dc, keterangan, foto_1, foto_2, foto_3,
        is_switched, is_dulux_bought
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, batch)
    conn.commit()
    total_count += len(batch)

print(f"Total inserted rows: {total_count}")

# Check verification query
cur.execute("SELECT COUNT(*), SUM(value_pembelian), COUNT(DISTINCT store_name), COUNT(DISTINCT nama_dc), SUM(is_switched), SUM(is_dulux_bought) FROM cust_raw")
row_res = cur.fetchone()
print(f"Verification: Count={row_res[0]}, Total Value=Rp {row_res[1]:,.0f}, Stores={row_res[2]}, DCs={row_res[3]}, Switched={row_res[4]}, Dulux Bought={row_res[5]}")

conn.close()

# Create GZ file
print(f"Compressing {db_path} to {gz_path}...")
with open(db_path, 'rb') as f_in:
    with gzip.open(gz_path, 'wb', compresslevel=9) as f_out:
        shutil.copyfileobj(f_in, f_out)

db_sz = os.path.getsize(db_path) / (1024 * 1024)
gz_sz = os.path.getsize(gz_path) / (1024 * 1024)
print(f"Done! DB Size: {db_sz:.2f} MB, GZ Size: {gz_sz:.2f} MB")
