import sqlite3
import gzip
import shutil
import os

sqlite_path = 'storage/app/dulux_data/stock_2026.sqlite'
gz_path = 'storage/app/dulux_data/stock_2026.sqlite.gz'

conn = sqlite3.connect(sqlite_path)
cursor = conn.cursor()

cursor.execute("PRAGMA index_list('stock_raw');")
indexes = cursor.fetchall()
print('Indexes in stock_2026.sqlite:', indexes)

cursor.execute("CREATE INDEX IF NOT EXISTS idx_stock_perf ON stock_raw (month, region, area, sap, store_name, brand, volume_liter);")
cursor.execute("CREATE INDEX IF NOT EXISTS idx_stock_store ON stock_raw (sap, store_name);")
cursor.execute("CREATE INDEX IF NOT EXISTS idx_stock_brand ON stock_raw (brand, volume_liter);")
cursor.execute("CREATE INDEX IF NOT EXISTS idx_stock_month ON stock_raw (month);")

conn.commit()
conn.close()

print(f"Compressed stock_2026 size: {os.path.getsize(sqlite_path):,} bytes")
with open(sqlite_path, 'rb') as f_in, gzip.open(gz_path, 'wb') as f_out:
    shutil.copyfileobj(f_in, f_out)
print(f"GZ size: {os.path.getsize(gz_path):,} bytes")
