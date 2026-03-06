  </div><!-- page-body -->

  <!-- ══════════════════════════════════
       SITE FOOTER
  ══════════════════════════════════ -->
  <footer style="
    background: #0f172a;
    border-top: 1px solid rgba(255,255,255,.07);
    padding: 1.75rem 1.5rem 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    flex-shrink: 0;
  ">
    <div style="max-width: 960px; margin: 0 auto;">

      <!-- ROW ATAS: Brand | Kontak | Sosmed -->
      <div style="
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1.5rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid rgba(255,255,255,.07);
      " class="footer-grid">

        <!-- Kolom 1: Brand -->
        <div>
          <div style="display:flex;align-items:center;gap:9px;margin-bottom:.55rem;">
            <div style="width:32px;height:32px;border-radius:8px;
                 background:linear-gradient(135deg,#15803d,#22d3ee);
                 display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi bi-mortarboard-fill" style="color:#fff;font-size:.85rem;"></i>
            </div>
            <div>
              <div style="font-weight:800;color:#fff;font-size:.9rem;line-height:1.1;">SiPrakerin</div>
              <div style="font-size:.62rem;color:rgba(255,255,255,.3);line-height:1.2;">Sistem Informasi Prakerin</div>
            </div>
          </div>
          <p style="font-size:.73rem;color:rgba(255,255,255,.35);line-height:1.65;margin:0;">
            Portal resmi pengelolaan kegiatan Praktik Kerja Industri (Prakerin) siswa SMK Negeri 3 Padang.
          </p>
        </div>

        <!-- Kolom 2: Kontak Sekolah -->
        <div>
          <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
               color:rgba(255,255,255,.35);margin-bottom:.75rem;">
            Kontak Sekolah
          </div>
          <div style="display:flex;flex-direction:column;gap:.5rem;">
            <a href="tel:+62751123456" style="display:flex;align-items:flex-start;gap:8px;text-decoration:none;transition:.15s;"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <div style="width:26px;height:26px;border-radius:6px;background:rgba(13,148,136,.2);
                   display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                <i class="bi bi-telephone-fill" style="color:#15803d;font-size:.7rem;"></i>
              </div>
              <div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.3);line-height:1;">(0751) 123456</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.25);">Telepon Sekolah</div>
              </div>
            </a>
            <a href="https://wa.me/6285274000000" target="_blank" style="display:flex;align-items:flex-start;gap:8px;text-decoration:none;transition:.15s;"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <div style="width:26px;height:26px;border-radius:6px;background:rgba(37,211,102,.15);
                   display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                <i class="bi bi-whatsapp" style="color:#25d366;font-size:.7rem;"></i>
              </div>
              <div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.3);line-height:1;">0852-7400-0000</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.25);">WhatsApp Sekolah</div>
              </div>
            </a>
            <div style="display:flex;align-items:flex-start;gap:8px;">
              <div style="width:26px;height:26px;border-radius:6px;background:rgba(255,255,255,.06);
                   display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                <i class="bi bi-geo-alt-fill" style="color:rgba(255,255,255,.3);font-size:.7rem;"></i>
              </div>
              <div style="font-size:.65rem;color:rgba(255,255,255,.25);line-height:1.6;">
                Jl. Bungo Pasang, Tabing<br>Padang, Sumatera Barat
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom 3: Media Sosial -->
        <div>
          <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
               color:rgba(255,255,255,.35);margin-bottom:.75rem;">
            Media Sosial
          </div>
          <div style="display:flex;flex-direction:column;gap:.35rem;">
            <?php
            $sosmed = [
              ['https://www.instagram.com/smkn3padang', 'bi-instagram',  '#e1306c', 'Instagram',  '@smkn3padang'],
              ['https://www.youtube.com/@smkn3padang',  'bi-youtube',    '#ff0000', 'YouTube',    'SMKN 3 Padang'],
              ['https://www.facebook.com/smkn3padang',  'bi-facebook',   '#1877f2', 'Facebook',   'smkn3padang'],
              ['https://twitter.com/smkn3padang',       'bi-twitter-x',  '#e7e9ea', 'Twitter / X','@smkn3padang'],
              ['https://www.tiktok.com/@smkn3padang',   'bi-tiktok',     '#ee1d52', 'TikTok',     '@smkn3padang'],
            ];
            foreach($sosmed as [$url,$ico,$clr,$name,$handle]):
            ?>
            <a href="<?= $url ?>" target="_blank" style="
              display: flex; align-items: center; gap: 8px;
              padding: .35rem .55rem; border-radius: 8px;
              background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
              text-decoration: none; transition: .15s;
            " onmouseover="this.style.background='rgba(255,255,255,.09)';this.style.borderColor='rgba(255,255,255,.15)'"
               onmouseout="this.style.background='rgba(255,255,255,.04)';this.style.borderColor='rgba(255,255,255,.07)'">
              <i class="bi <?= $ico ?>" style="color:<?= $clr ?>;font-size:.85rem;flex-shrink:0;width:16px;text-align:center;"></i>
              <div style="flex:1;min-width:0;">
                <div style="font-size:.7rem;font-weight:700;color:rgba(255,255,255,.65);line-height:1.1;"><?= $name ?></div>
                <div style="font-size:.62rem;color:rgba(255,255,255,.25);line-height:1.2;"><?= $handle ?></div>
              </div>
              <i class="bi bi-box-arrow-up-right" style="font-size:.6rem;color:rgba(255,255,255,.2);flex-shrink:0;"></i>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

      </div><!-- /footer-grid -->

      <!-- ROW BAWAH: Copyright -->
      <div style="
        display: flex; justify-content: space-between; align-items: center;
        padding: .75rem 0; flex-wrap: wrap; gap: .5rem;
      ">
        <div style="font-size:.7rem;color:rgba(255,255,255,.2);">
          © <?= date('Y') ?> SiPrakerin · SMK Negeri 3 Padang · All rights reserved.
        </div>
        <div style="font-size:.68rem;color:rgba(255,255,255,.12);">v3.0</div>
      </div>

    </div>
  </footer>

  <style>
  /* Responsive footer grid */
  @media (max-width: 860px) {
    .footer-grid {
      grid-template-columns: 1fr 1fr !important;
    }
  }
  @media (max-width: 560px) {
    .footer-grid {
      grid-template-columns: 1fr !important;
    }
  }
  </style>

</div><!-- main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Logout confirm ──
document.querySelector('.btn-logout-siswa')?.addEventListener('click', e => {
  e.preventDefault(); const h = e.currentTarget.href;
  Swal.fire({
    title: 'Yakin mau keluar?', text: 'Kamu akan logout dari SiPrakerin.',
    icon: 'question', showCancelButton: true,
    confirmButtonColor: '#15803d', cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Keluar', cancelButtonText: 'Batal'
  }).then(r => { if(r.isConfirmed) location.href = h; });
});

// ── Hapus confirm ──
document.querySelectorAll('.btn-hapus').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault(); const href = e.currentTarget.href;
    const nama = e.currentTarget.dataset.nama || 'data ini';
    Swal.fire({
      title: `Hapus ${nama}?`, text: 'Data tidak bisa dipulihkan.',
      icon: 'warning', showCancelButton: true,
      confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
      confirmButtonText: 'Hapus', cancelButtonText: 'Batal'
    }).then(r => { if(r.isConfirmed) location.href = href; });
  });
});

// ── Sidebar event listeners (fungsi ada di header) ──
document.addEventListener('DOMContentLoaded', function(){
  var overlay = document.getElementById('sbOverlay');
  var sidebar = document.getElementById('sidebar');

  if(overlay) overlay.addEventListener('click', closeSidebar);

  if(sidebar) sidebar.querySelectorAll('.sb-link').forEach(function(l){
    l.addEventListener('click', function(){
      if(window.innerWidth < 768) closeSidebar();
    });
  });

  window.addEventListener('resize', function(){
    if(window.innerWidth >= 768) closeSidebar();
  });
});
</script>
</body>
</html>
