import sqlite3

stock_2026 = 'storage/app/dulux_data/stock_2026.sqlite'
offtake_2026 = 'storage/app/dulux_data/offtake_2026.sqlite'

conn = sqlite3.connect(stock_2026)
cursor = conn.cursor()
cursor.execute(f"ATTACH DATABASE '{offtake_2026}' AS offtake_db;")

cursor.execute("""
SELECT 
    st.sap, st.store_name, st.region, st.area,
    st.dulux_stock, st.catylac_stock, st.total_stock,
    COALESCE(ot.dulux_offtake, 0) as dulux_offtake,
    COALESCE(ot.catylac_offtake, 0) as catylac_offtake,
    COALESCE(ot.total_offtake, 0) as total_offtake
FROM (
    SELECT 
        sap, store_name, region, area,
        SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_stock,
        SUM(CASE WHEN brand IN ('Catylac', 'Catylac Smart Choice') THEN volume_liter ELSE 0 END) as catylac_stock,
        SUM(volume_liter) as total_stock
    FROM stock_raw
    WHERE month = 7
    GROUP BY sap, store_name, region, area
) st
LEFT JOIN (
    SELECT 
        sap, name_store,
        SUM(CASE WHEN brand = 'Dulux' THEN volume_liter ELSE 0 END) as dulux_offtake,
        SUM(CASE WHEN brand = 'Catylac' THEN volume_liter ELSE 0 END) as catylac_offtake,
        SUM(volume_liter) as total_offtake
    FROM offtake_db.offtake_raw
    WHERE month = 7
    GROUP BY sap, name_store
) ot ON (st.sap = ot.sap AND st.sap != '') OR (st.store_name = ot.name_store)
ORDER BY st.total_stock DESC
LIMIT 5;
""")
rows = cursor.fetchall()
print("=== SCM SUMMARY SAMPLE (JULI 2026) ===")
for r in rows:
    sap, name, reg, area, d_stk, c_stk, t_stk, d_off, c_off, t_off = r
    scm_d = (d_stk / d_off) if d_off > 0 else 0
    scm_c = (c_stk / c_off) if c_off > 0 else 0
    scm_tot = (t_stk / t_off) if t_off > 0 else 0
    print(f"Store: {name} | Total Stock: {t_stk:,.2f} L | Total Offtake: {t_off:,.2f} L | Total SCM: {scm_tot:.2f} Bulan")

conn.close()
