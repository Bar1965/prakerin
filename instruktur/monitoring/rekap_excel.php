<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';

// Ambil guru_id
$user_id = $_SESSION['user_id'];
$stmtG = mysqli_prepare($conn, "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?");
mysqli_stmt_bind_param($stmtG, "i", $user_id);
mysqli_stmt_execute($stmtG);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtG));
$guru_id   = $guru['id']   ?? 0;
$nama_guru = $guru['nama'] ?? 'Guru';

// Opsional filter per siswa
$siswa_filter = (int)($_GET['siswa_id'] ?? 0);

// Ambil data siswa
$where_siswa = $siswa_filter ? "AND s.id = $siswa_filter" : "";
$stmtS = mysqli_prepare($conn, "
    SELECT s.id, s.nis, s.kelas, s.jurusan, u.nama,
           tp.nama_tempat
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
    WHERE s.instruktur_id = ? $where_siswa
    ORDER BY s.kelas, u.nama
");
mysqli_stmt_bind_param($stmtS, "i", $guru_id);
mysqli_stmt_execute($stmtS);
$siswa_list = mysqli_stmt_get_result($stmtS);
$siswa_rows = [];
while ($r = mysqli_fetch_assoc($siswa_list)) $siswa_rows[] = $r;

if (empty($siswa_rows)) {
    die("Tidak ada data siswa bimbingan.");
}

// Ambil semua jurnal untuk semua siswa sekaligus
$siswa_ids = implode(',', array_column($siswa_rows, 'id'));
$jurnal_all = mysqli_query($conn, "
    SELECT * FROM jurnal WHERE siswa_id IN ($siswa_ids) ORDER BY siswa_id, tanggal
");
$jurnal_by_siswa = [];
while ($j = mysqli_fetch_assoc($jurnal_all)) {
    $jurnal_by_siswa[$j['siswa_id']][] = $j;
}

// ===== BUAT EXCEL PAKAI PYTHON =====
$data_json = json_encode([
    'guru'   => $nama_guru,
    'siswa'  => $siswa_rows,
    'jurnal' => $jurnal_by_siswa,
    'tanggal_cetak' => date('d F Y'),
]);

// Tulis data ke file temp
$tmp_json = tempnam(sys_get_temp_dir(), 'rekap_') . '.json';
$tmp_xlsx = tempnam(sys_get_temp_dir(), 'rekap_') . '.xlsx';
file_put_contents($tmp_json, $data_json);

$python_script = <<<PYEOF
import json, sys
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

with open(sys.argv[1], encoding='utf-8') as f:
    data = json.load(f)

wb = Workbook()
wb.remove(wb.active)  # hapus sheet default

hari_indo = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']

def border_thin():
    s = Side(style='thin', color='CCCCCC')
    return Border(left=s, right=s, top=s, bottom=s)

def header_style(cell, bg='4F46E5', fg='FFFFFF', bold=True, size=11, wrap=False):
    cell.font = Font(name='Arial', bold=bold, color=fg, size=size)
    cell.fill = PatternFill('solid', start_color=bg)
    cell.alignment = Alignment(horizontal='center', vertical='center', wrap_text=wrap)
    cell.border = border_thin()

def data_style(cell, bold=False, color='000000', align='left', wrap=False, bg=None):
    cell.font = Font(name='Arial', bold=bold, color=color, size=10)
    cell.alignment = Alignment(horizontal=align, vertical='center', wrap_text=wrap)
    cell.border = border_thin()
    if bg:
        cell.fill = PatternFill('solid', start_color=bg)

# =====================================
# SHEET 1: REKAP RINGKASAN
# =====================================
ws = wb.create_sheet('Rekap Ringkasan')
ws.sheet_view.showGridLines = False

# Header utama
ws.merge_cells('A1:I1')
ws['A1'] = 'REKAP MONITORING JURNAL PKL'
ws['A1'].font = Font(name='Arial', bold=True, size=14, color='FFFFFF')
ws['A1'].fill = PatternFill('solid', start_color='4F46E5')
ws['A1'].alignment = Alignment(horizontal='center', vertical='center')

ws.merge_cells('A2:I2')
ws['A2'] = f"Guru Pembimbing: {data['guru']}   |   Tanggal Cetak: {data['tanggal_cetak']}"
ws['A2'].font = Font(name='Arial', size=10, color='64748B')
ws['A2'].fill = PatternFill('solid', start_color='EFF6FF')
ws['A2'].alignment = Alignment(horizontal='center', vertical='center')
ws.row_dimensions[1].height = 35
ws.row_dimensions[2].height = 25

# Header tabel
headers = ['No','Nama Siswa','NIS','Kelas','Tempat PKL','Total Jurnal','Disetujui','Ditolak/Pending','% Approval']
for col, h in enumerate(headers, 1):
    c = ws.cell(row=4, column=col, value=h)
    header_style(c, bg='1E3A8A', size=10, wrap=True)
ws.row_dimensions[4].height = 30

# Data ringkasan
for i, s in enumerate(data['siswa']):
    row = 5 + i
    jurnals = data['jurnal'].get(str(s['id']), [])
    total = len(jurnals)
    ok    = sum(1 for j in jurnals if j['status']=='disetujui')
    tolak = sum(1 for j in jurnals if j['status'] in ['ditolak','pending'])
    pct   = f"=G{row}/F{row}" if total > 0 else 0

    vals = [i+1, s['nama'], s['nis'], s['kelas'], s['nama_tempat'] or '-', total, ok, tolak, pct]
    bg = 'FFFFFF' if i%2==0 else 'F8FAFC'
    for col, v in enumerate(vals, 1):
        c = ws.cell(row=row, column=col, value=v)
        align = 'center' if col in [1,3,6,7,8,9] else 'left'
        data_style(c, align=align, bg=bg)
        if col == 9 and isinstance(v, str) and v.startswith('='):
            c.number_format = '0%'
            c.value = v

ws.row_dimensions[row+1 if data['siswa'] else 5].height = 20

# Kolom width
col_widths = [5, 28, 14, 12, 32, 14, 12, 18, 14]
for i, w in enumerate(col_widths, 1):
    ws.column_dimensions[get_column_letter(i)].width = w

# =====================================
# SHEET 2+: JURNAL PER SISWA
# =====================================
status_colors = {'disetujui':'D1FAE5','pending':'FEF9C3','ditolak':'FEE2E2'}
status_icons  = {'disetujui':'✅ Disetujui','pending':'⏳ Pending','ditolak':'❌ Ditolak'}

for s in data['siswa']:
    name_short = s['nama'][:28]
    ws2 = wb.create_sheet(name_short)
    ws2.sheet_view.showGridLines = False

    # Header profil siswa
    ws2.merge_cells('A1:F1')
    ws2['A1'] = f"JURNAL PKL - {s['nama'].upper()}"
    header_style(ws2['A1'], bg='4F46E5', size=13)
    ws2.row_dimensions[1].height = 32

    info = [
        ('NIS', s['nis']),
        ('Kelas', s['kelas']),
        ('Jurusan', s['jurusan'] or '-'),
        ('Tempat PKL', s['nama_tempat'] or 'Belum ditempatkan'),
        ('Instruktur DU/DI', data['guru']),
        ('Tanggal Cetak', data['tanggal_cetak']),
    ]
    for r, (lbl, val) in enumerate(info, 2):
        ws2.merge_cells(f'A{r}:B{r}')
        c_lbl = ws2.cell(row=r, column=1, value=lbl)
        c_lbl.font = Font(name='Arial', bold=True, size=10, color='374151')
        c_lbl.fill = PatternFill('solid', start_color='EFF6FF')
        c_lbl.alignment = Alignment(horizontal='left', vertical='center', indent=1)
        ws2.merge_cells(f'C{r}:F{r}')
        c_val = ws2.cell(row=r, column=3, value=val)
        c_val.font = Font(name='Arial', size=10)
        c_val.alignment = Alignment(horizontal='left', vertical='center', indent=1)
        c_val.fill = PatternFill('solid', start_color='F8FAFC')
        ws2.row_dimensions[r].height = 20

    # Statistik singkat
    jurnals = data['jurnal'].get(str(s['id']), [])
    total = len(jurnals)
    ok    = sum(1 for j in jurnals if j['status']=='disetujui')
    pct_v = f"{round(ok/total*100)}%" if total > 0 else "0%"

    stat_row = len(info) + 2 + 1
    ws2.merge_cells(f'A{stat_row}:F{stat_row}')
    c = ws2.cell(row=stat_row, column=1,
        value=f"Total: {total} jurnal  |  Disetujui: {ok}  |  Approval: {pct_v}")
    c.font = Font(name='Arial', bold=True, size=10, color='1E3A8A')
    c.fill = PatternFill('solid', start_color='DBEAFE')
    c.alignment = Alignment(horizontal='center', vertical='center')
    ws2.row_dimensions[stat_row].height = 22

    # Header tabel jurnal
    h_row = stat_row + 1
    jheaders = ['No','Tanggal','Hari','Kegiatan','Status','Catatan Guru']
    for col, h in enumerate(jheaders, 1):
        c = ws2.cell(row=h_row, column=col, value=h)
        header_style(c, bg='1E3A8A', size=10, wrap=True)
    ws2.row_dimensions[h_row].height = 28

    # Data jurnal
    if not jurnals:
        ws2.merge_cells(f'A{h_row+1}:F{h_row+1}')
        c = ws2.cell(row=h_row+1, column=1, value='Belum ada data jurnal')
        c.font = Font(name='Arial', color='94A3B8', italic=True, size=10)
        c.alignment = Alignment(horizontal='center', vertical='center')
    else:
        import datetime
        for ni, j in enumerate(jurnals):
            dr = h_row + 1 + ni
            try:
                tgl = datetime.datetime.strptime(j['tanggal'], '%Y-%m-%d')
                tgl_str = tgl.strftime('%d %B %Y')
                hari    = hari_indo[tgl.weekday()+1 if tgl.weekday()<6 else 0]
            except:
                tgl_str = j['tanggal']
                hari    = '-'

            status = j['status']
            bg = status_colors.get(status, 'FFFFFF')

            vals = [ni+1, tgl_str, hari, j['kegiatan'], status_icons.get(status, status), j['catatan_guru'] or '-']
            for col, v in enumerate(vals, 1):
                c = ws2.cell(row=dr, column=col, value=v)
                align = 'center' if col in [1,2,3,5] else 'left'
                data_style(c, align=align, wrap=(col==4), bg=bg)
            ws2.row_dimensions[dr].height = 55 if len(j['kegiatan']) > 100 else 35

    # Kolom width per sheet jurnal
    for col, w in enumerate([5, 16, 10, 60, 16, 35], 1):
        ws2.column_dimensions[get_column_letter(col)].width = w

wb.save(sys.argv[2])
PYEOF;

$py_file = tempnam(sys_get_temp_dir(), 'gen_') . '.py';
file_put_contents($py_file, $python_script);

exec("python3 $py_file $tmp_json $tmp_xlsx 2>&1", $out, $ret);

if ($ret !== 0) {
    echo "Error membuat Excel: " . implode("\n", $out);
    exit;
}

// Nama file download
$filename = 'Rekap_DuDi_' . preg_replace('/\s+/', '_', $nama_guru) . '_' . date('Ymd') . '.xlsx';

// Kirim sebagai download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmp_xlsx));
readfile($tmp_xlsx);

// Bersihkan file temp
unlink($tmp_json);
unlink($tmp_xlsx);
unlink($py_file);
exit;
