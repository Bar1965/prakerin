    <footer style="margin-top:auto;padding:1.25rem;color:#94a3b8;font-size:0.82rem;text-align:center;border-top:1px solid #e2e8f0;margin-top:2rem;">
        &copy; <?= date('Y') ?> <strong>SiPrakerin</strong> SMK Negeri 3 Padang
    </footer>
</div><!-- end main-content -->
</div><!-- end wrapper -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Sidebar toggle mobile ──
function openSidebar(){
    document.querySelector('.sidebar').classList.add('open');
    document.getElementById('sbOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar(){
    document.querySelector('.sidebar').classList.remove('open');
    document.getElementById('sbOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
document.getElementById('sbOverlay').addEventListener('click', closeSidebar);
document.querySelectorAll('.menu-item').forEach(function(l){
    l.addEventListener('click', function(){ if(window.innerWidth < 768) closeSidebar(); });
});
window.addEventListener('resize', function(){ if(window.innerWidth >= 768) closeSidebar(); });

// ── Notifikasi Bell ──
const notifIcons = {'jurnal':'📝','absensi':'📋','konfirmasi':'✅','sistem':'🔔'};
function toggleNotif(){
  const d = document.getElementById('notifDropdown');
  const open = d.style.display==='block';
  d.style.display = open ? 'none' : 'block';
  if (!open) loadNotif();
}
async function loadNotif(){
  const list = document.getElementById('notifList');
  list.innerHTML = '<div style="padding:1.25rem;text-align:center;color:#94a3b8;font-size:.82rem;">Memuat...</div>';
  try {
    const r = await fetch('../../api/notifikasi.php?action=list');
    const d = await r.json();
    if (!d.ok || !d.notifs.length) {
      list.innerHTML = '<div style="padding:1.25rem;text-align:center;color:#94a3b8;font-size:.82rem;">Tidak ada notifikasi</div>';
      return;
    }
    list.innerHTML = d.notifs.map(n => `
      <div onclick="bacaNotif(${n.id},'${n.link||''}')"
           style="padding:.7rem 1rem;border-bottom:1px solid #f8fafc;cursor:pointer;display:flex;gap:9px;align-items:flex-start;background:${n.dibaca?'#fff':'#f8faff'};"
           onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='${n.dibaca?'#fff':'#f8faff'}'">
        <div style="width:32px;height:32px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem;">${notifIcons[n.tipe]||'🔔'}</div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:${n.dibaca?'500':'700'};font-size:.81rem;color:#0f172a;">${n.judul}</div>
          <div style="font-size:.75rem;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${n.pesan}</div>
          <div style="font-size:.68rem;color:#94a3b8;margin-top:2px;">${n.waktu}</div>
        </div>
        ${!n.dibaca?'<div style="width:7px;height:7px;border-radius:50%;background:#15803d;flex-shrink:0;margin-top:4px;"></div>':''}
      </div>`).join('');
    updateBadge(d.unread);
  } catch(e) {}
}
async function bacaNotif(id, link) {
  await fetch('../../api/notifikasi.php?action=read', {method:'POST',body:new URLSearchParams({id})});
  if (link) window.location.href = link; else loadNotif();
}
async function bacaSemua() {
  await fetch('../../api/notifikasi.php?action=read', {method:'POST',body:new URLSearchParams({})});
  loadNotif();
}
function updateBadge(count) {
  const b = document.getElementById('notifBadge');
  if (count > 0) { b.style.display='block'; b.textContent = count > 9 ? '9+' : count; }
  else b.style.display = 'none';
}
async function cekUnread(){
  try { const r = await fetch('../../api/notifikasi.php?action=list'); const d = await r.json(); if(d.ok) updateBadge(d.unread); } catch(e){}
}
cekUnread(); setInterval(cekUnread, 60000);
document.addEventListener('click', e => {
  if (!document.getElementById('notifWrap')?.contains(e.target))
    document.getElementById('notifDropdown').style.display='none';
});

const btnLogout = document.querySelector('.btn-logout');
if (btnLogout) {
    btnLogout.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        Swal.fire({
            title: 'Yakin ingin keluar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) window.location.href = href; });
    });
}
</script>
</body>
</html>
