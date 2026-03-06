  </div><!-- end page-body -->

  <!-- ══════════════════════════════════
       ADMIN FOOTER
  ══════════════════════════════════ -->
  <footer style="
    background: #0f172a;
    border-top: 1px solid rgba(255,255,255,.07);
    padding: 1.5rem 1.75rem 0;
    flex-shrink: 0;
  ">
    <div style="
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 1.5rem;
      padding-bottom: 1.1rem;
      border-bottom: 1px solid rgba(255,255,255,.07);
    " class="admin-footer-grid">

      <!-- Brand -->
      <div>
        <div style="display:flex;align-items:center;gap:9px;margin-bottom:.5rem;">
          <div style="width:30px;height:30px;border-radius:7px;
               background:linear-gradient(135deg,#15803d,#15803d);
               display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-mortarboard-fill" style="color:#fff;font-size:.78rem;"></i>
          </div>
          <div>
            <div style="font-weight:800;color:#fff;font-size:.85rem;line-height:1.1;">SiPrakerin</div>
            <div style="font-size:.6rem;color:rgba(255,255,255,.25);">Panel Administrator</div>
          </div>
        </div>
        <p style="font-size:.7rem;color:rgba(255,255,255,.3);line-height:1.65;margin:0;">
          Sistem Informasi Praktik Kerja Industri<br>SMK Negeri 3 Padang.
        </p>
      </div>

      <!-- Kontak -->
      <div>
        <div style="font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
             color:rgba(255,255,255,.3);margin-bottom:.65rem;">Kontak Sekolah</div>
        <div style="display:flex;flex-direction:column;gap:.4rem;">
          <a href="tel:+62751123456" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
            <i class="bi bi-telephone-fill" style="color:#15803d;font-size:.75rem;width:14px;text-align:center;"></i>
            <span style="font-size:.68rem;color:rgba(255,255,255,.35);">(0751) 123456</span>
          </a>
          <a href="https://wa.me/6285274000000" target="_blank" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
            <i class="bi bi-whatsapp" style="color:#25d366;font-size:.75rem;width:14px;text-align:center;"></i>
            <span style="font-size:.68rem;color:rgba(255,255,255,.35);">0852-7400-0000</span>
          </a>
          <div style="display:flex;align-items:flex-start;gap:7px;">
            <i class="bi bi-geo-alt-fill" style="color:rgba(255,255,255,.2);font-size:.75rem;width:14px;text-align:center;margin-top:2px;"></i>
            <span style="font-size:.66rem;color:rgba(255,255,255,.25);line-height:1.55;">Jl. Bungo Pasang, Tabing<br>Padang, Sumatera Barat</span>
          </div>
        </div>
      </div>

      <!-- Sosmed -->
      <div>
        <div style="font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
             color:rgba(255,255,255,.3);margin-bottom:.65rem;">Media Sosial</div>
        <div style="display:flex;flex-direction:column;gap:.3rem;">
          <?php
          $sm = [
            ['https://www.instagram.com/smkn3padang','bi-instagram','#e1306c','Instagram','@smkn3padang'],
            ['https://www.youtube.com/@smkn3padang', 'bi-youtube',  '#ff0000','YouTube',  'SMKN 3 Padang'],
            ['https://www.facebook.com/smkn3padang', 'bi-facebook', '#1877f2','Facebook', 'smkn3padang'],
            ['https://twitter.com/smkn3padang',      'bi-twitter-x','#e7e9ea','Twitter/X','@smkn3padang'],
          ];
          foreach($sm as [$url,$ico,$clr,$nm,$hndl]):
          ?>
          <a href="<?= $url ?>" target="_blank" style="
            display:flex;align-items:center;gap:7px;padding:.3rem .5rem;border-radius:7px;
            background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);
            text-decoration:none;transition:.12s;
          " onmouseover="this.style.background='rgba(255,255,255,.09)'" onmouseout="this.style.background='rgba(255,255,255,.04)'">
            <i class="bi <?= $ico ?>" style="color:<?= $clr ?>;font-size:.78rem;width:14px;text-align:center;flex-shrink:0;"></i>
            <span style="font-size:.68rem;font-weight:600;color:rgba(255,255,255,.5);"><?= $nm ?></span>
            <span style="font-size:.62rem;color:rgba(255,255,255,.2);margin-left:auto;"><?= $hndl ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- Copyright bar -->
    <div style="display:flex;justify-content:space-between;align-items:center;
         padding:.65rem 0;flex-wrap:wrap;gap:.4rem;">
      <span style="font-size:.68rem;color:rgba(255,255,255,.18);">
        © <?= date('Y') ?> SiPrakerin · SMK Negeri 3 Padang · All rights reserved.
      </span>
      <span style="font-size:.65rem;color:rgba(255,255,255,.12);">Admin Panel v3.0</span>
    </div>

  </footer>

  <style>
  @media (max-width: 860px) {
    .admin-footer-grid { grid-template-columns: 1fr 1fr !important; }
  }
  @media (max-width: 560px) {
    .admin-footer-grid { grid-template-columns: 1fr !important; }
  }
  </style>

</div><!-- end main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
  list.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.85rem;">Memuat...</div>';
  try {
    const r = await fetch('../../api/notifikasi.php?action=list');
    const d = await r.json();
    if (!d.ok || !d.notifs.length) {
      list.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.85rem;">Tidak ada notifikasi</div>';
      return;
    }
    list.innerHTML = d.notifs.map(n => `
      <div onclick="bacaNotif(${n.id},'${n.link||''}')"
           style="padding:.75rem 1rem;border-bottom:1px solid #f8fafc;cursor:pointer;display:flex;gap:10px;align-items:flex-start;background:${n.dibaca?'#fff':'#f8faff'};transition:.15s;"
           onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='${n.dibaca?'#fff':'#f8faff'}'">
        <div style="width:34px;height:34px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;">${notifIcons[n.tipe]||'🔔'}</div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:${n.dibaca?'500':'700'};font-size:.83rem;color:#0f172a;">${n.judul}</div>
          <div style="font-size:.78rem;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${n.pesan}</div>
          <div style="font-size:.7rem;color:#94a3b8;margin-top:3px;">${n.waktu}</div>
        </div>
        ${!n.dibaca?'<div style="width:8px;height:8px;border-radius:50%;background:#15803d;flex-shrink:0;margin-top:4px;"></div>':''}
      </div>`).join('');
    updateBadge(d.unread);
  } catch(e) {
    list.innerHTML = '<div style="padding:1rem;text-align:center;color:#ef4444;font-size:.82rem;">Gagal memuat notifikasi</div>';
  }
}
async function bacaNotif(id, link) {
  await fetch('../../api/notifikasi.php?action=read', {method:'POST',body:new URLSearchParams({id})});
  if (link) window.location.href = link;
  else loadNotif();
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
// Cek notif saat load & tiap 60 detik
async function cekUnread(){
  try {
    const r = await fetch('../../api/notifikasi.php?action=list');
    const d = await r.json();
    if (d.ok) updateBadge(d.unread);
  } catch(e){}
}
cekUnread();
setInterval(cekUnread, 60000);
// Tutup dropdown saat klik di luar
document.addEventListener('click', e => {
  if (!document.getElementById('notifWrap')?.contains(e.target))
    document.getElementById('notifDropdown').style.display='none';
});

(function(){
  var sidebar  = document.querySelector('.sidebar');
  var overlay  = document.getElementById('sbOverlay');
  var burger   = document.querySelector('.btn-burger');

  function openSidebar(){
    if(sidebar)  sidebar.classList.add('open');
    if(overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar(){
    if(sidebar)  sidebar.classList.remove('open');
    if(overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
  }

  if(burger)  burger.addEventListener('click', openSidebar);
  if(overlay) overlay.addEventListener('click', closeSidebar);

  // Tutup sidebar saat link diklik (mobile)
  document.querySelectorAll('.sidebar-link, .sidebar-logout').forEach(function(l){
    l.addEventListener('click', function(){
      if(window.innerWidth < 992) closeSidebar();
    });
  });

  // Reset saat resize ke desktop
  window.addEventListener('resize', function(){
    if(window.innerWidth >= 992) closeSidebar();
  });
})();

document.querySelector('.btn-logout-admin')?.addEventListener('click', e => {
  e.preventDefault(); const href = e.currentTarget.href;
  Swal.fire({ title:'Yakin logout?', icon:'warning', showCancelButton:true,
    confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b',
    confirmButtonText:'Logout', cancelButtonText:'Batal'
  }).then(r => { if(r.isConfirmed) location.href = href; });
});
document.querySelectorAll('.btn-hapus').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault(); const href = e.currentTarget.href;
    const nama = e.currentTarget.dataset.nama || 'data ini';
    Swal.fire({ title:`Hapus ${nama}?`, text:'Data tidak bisa dipulihkan.', icon:'warning',
      showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b',
      confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => { if(r.isConfirmed) location.href = href; });
  });
});
</script>
</body>
</html>
