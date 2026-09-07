import sqlite3

conn = sqlite3.connect('storage/app/dulux_data/stock_2026.sqlite')
cursor = conn.cursor()
cursor.execute("SELECT month, COUNT(*), SUM(volume_liter) FROM stock_raw GROUP BY month ORDER BY month;")
rows = cursor.fetchall()
print('Months in stock_raw:')
for r in rows:
    print(f'  Month {r[0]}: {r[1]} records, Total Vol: {r[2]:,.2f} L')

cursor.execute("SELECT DISTINCT brand FROM stock_raw;")
print('\nBrands:', cursor.fetchall())

cursor.execute("SELECT DISTINCT region FROM stock_raw;")
print('\nRegions:', cursor.fetchall())
