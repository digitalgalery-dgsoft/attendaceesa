import sqlite3

sqlitePath = r"G:\My File\Project APlikasi Absensi\New\att-admin-v12\storage\app\dulux_data\daily_maintenance.sqlite"
conn = sqlite3.connect(sqlitePath)
cur = conn.cursor()

def normalize_machine(mtype, mno):
    combined = (str(mtype) + " " + str(mno)).upper()
    if 'DISCOVERY' in combined or 'DISCV' in combined:
        return 'Discovery'
    elif 'X-SMART' in combined or 'XSMART' in combined or 'X SMART' in combined or '670000001' in combined:
        return 'X-Smart'
    elif 'XPROTINT' in combined or 'X-PROTINT' in combined or '720000010' in combined or 'PROTINT' in combined:
        return 'Xprotint'
    elif 'D200' in combined or 'D10B' in combined or 'D14B' in combined or 'D11B' in combined or 'D8B' in combined:
        return 'D200'
    elif 'MANUAL' in combined:
        return 'Manual'
    elif 'FAST' in combined or 'FLUID' in combined:
        return 'Fast & Fluid'
    elif str(mtype).strip():
        # Keep clean mtype if short, else Other
        raw = str(mtype).strip()
        if len(raw) < 15 and not any(c.isdigit() for c in raw):
            return raw
        return 'Other'
    return 'Other'

cur.execute("SELECT machine_type, machine_no, COUNT(*) FROM dm_raw GROUP BY machine_type, machine_no")
rows = cur.fetchall()

norm_counts = {}
for mtype, mno, cnt in rows:
    n = normalize_machine(mtype, mno)
    norm_counts[n] = norm_counts.get(n, 0) + cnt

print("Normalized Machine Counts:")
for k, v in sorted(norm_counts.items(), key=lambda x: x[1], reverse=True):
    print(f" - {k}: {v:,d} ({v/103458*100:.1f}%)")

conn.close()
