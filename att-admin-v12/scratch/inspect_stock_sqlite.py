import sqlite3

conn = sqlite3.connect('storage/app/dulux_data/stock_2026.sqlite')
cursor = conn.cursor()
cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
tables = cursor.fetchall()
print('Tables in stock_2026.sqlite:', tables)

for t in tables:
    t_name = t[0]
    cursor.execute(f"PRAGMA table_info({t_name});")
    cols = cursor.fetchall()
    print(f'\nTable {t_name} columns:')
    for c in cols:
        print(f'  {c[1]} ({c[2]})')
    
    cursor.execute(f"SELECT COUNT(*) FROM {t_name};")
    cnt = cursor.fetchone()[0]
    print(f'  Count: {cnt}')
    
    cursor.execute(f"SELECT * FROM {t_name} LIMIT 3;")
    rows = cursor.fetchall()
    print('  Sample rows:', rows)
