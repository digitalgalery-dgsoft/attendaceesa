import sqlite3

stock_2026 = 'storage/app/dulux_data/stock_2026.sqlite'
stock_2025 = 'storage/app/dulux_data/stock_2025.sqlite'
offtake_2026 = 'storage/app/dulux_data/offtake_2026.sqlite'

conn = sqlite3.connect(stock_2026)
cursor = conn.cursor()

# Test Pivotable query (Group by SAP, store_name, region, area)
print("=== PIVOTABLE TEST ===")
cursor.execute("""
SELECT 
    sap, store_name, region, area,
    SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_vol,
    SUM(CASE WHEN brand = 'Catylac Smart Choice' THEN volume_liter ELSE 0 END) as catylac_sc_vol,
    SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as catylac_vol,
    SUM(volume_liter) as total_vol
FROM stock_raw
WHERE month = 7
GROUP BY sap, store_name, region, area
ORDER BY total_vol DESC
LIMIT 5;
""")
rows = cursor.fetchall()
for r in rows:
    print(f"SAP: {r[0]} | Store: {r[1]} | Reg: {r[2]} | Area: {r[3]} | Dulux: {r[4]:,.2f} | Catylac SC: {r[5]:,.2f} | Catylac: {r[6]:,.2f} | Total: {r[7]:,.2f}")

# Test YTD YoY query (Attach stock_2025)
print("\n=== YTD YOY TEST ===")
cursor.execute(f"ATTACH DATABASE '{stock_2025}' AS py_db;")

# Product YoY
cursor.execute("""
SELECT 
    b.brand,
    COALESCE(cy.total_vol, 0) as cy_vol,
    COALESCE(py.total_vol, 0) as py_vol
FROM (
    SELECT DISTINCT brand FROM stock_raw
    UNION SELECT DISTINCT brand FROM py_db.stock_raw
) b
LEFT JOIN (
    SELECT brand, SUM(volume_liter) as total_vol
    FROM stock_raw
    WHERE month <= 7
    GROUP BY brand
) cy ON b.brand = cy.brand
LEFT JOIN (
    SELECT brand, SUM(volume_liter) as total_vol
    FROM py_db.stock_raw
    WHERE month <= 7
    GROUP BY brand
) py ON b.brand = py.brand
ORDER BY cy_vol DESC;
""")
p_rows = cursor.fetchall()
for r in p_rows:
    brand, cy, py = r
    growth = ((cy - py) / py * 100) if py > 0 else 0
    print(f"Brand: {brand} | CY 2026: {cy:,.2f} L | PY 2025: {py:,.2f} L | Growth: {growth:+.1f}%")

# Store YoY
cursor.execute("""
SELECT 
    COALESCE(cy.sap, py.sap) as sap,
    COALESCE(cy.store_name, py.store_name) as store_name,
    COALESCE(cy.region, py.region) as region,
    COALESCE(cy.area, py.area) as area,
    COALESCE(cy.total_vol, 0) as cy_vol,
    COALESCE(py.total_vol, 0) as py_vol
FROM (
    SELECT sap, store_name, region, area, SUM(volume_liter) as total_vol
    FROM stock_raw
    WHERE month <= 7
    GROUP BY sap, store_name, region, area
) cy
LEFT JOIN (
    SELECT sap, store_name, region, area, SUM(volume_liter) as total_vol
    FROM py_db.stock_raw
    WHERE month <= 7
    GROUP BY sap, store_name, region, area
) py ON (cy.sap = py.sap AND cy.sap != '') OR (cy.store_name = py.store_name)
ORDER BY cy_vol DESC
LIMIT 5;
""")
s_rows = cursor.fetchall()
print("\nTop 5 Stores YTD:")
for r in s_rows:
    sap, name, reg, area, cy, py = r
    growth = ((cy - py) / py * 100) if py > 0 else 0
    print(f"SAP: {sap} | Store: {name} | CY 2026: {cy:,.2f} | PY 2025: {py:,.2f} | Growth: {growth:+.1f}%")

conn.close()
