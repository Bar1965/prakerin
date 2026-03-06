  </div><!-- content -->
</div><!-- main -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Sidebar toggle mobile ──
function openSidebar(){
  document.querySelector('.sidebar').classList.add('open');
  document.getElementById('sbOverlay').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeSidebar(){
  document.querySelector('.sidebar').classList.remove('open');
  document.getElementById('sbOverlay').classList.remove('show');
  document.body.style.overflow='';
}
document.getElementById('sbOverlay').addEventListener('click',closeSidebar);
document.querySelectorAll('.sb-item,.sb-logout-btn').forEach(function(l){
  l.addEventListener('click',function(){if(window.innerWidth<768)closeSidebar();});
});
window.addEventListener('resize',function(){if(window.innerWidth>=768)closeSidebar();});

document.querySelector('.btn-logout-instr')?.addEventListener('click',e=>{
  e.preventDefault(); const h=e.currentTarget.href;
  Swal.fire({title:'Yakin logout?',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#475569',confirmButtonText:'Logout',cancelButtonText:'Batal'})
    .then(r=>{if(r.isConfirmed)location.href=h;});
});
document.querySelectorAll('.btn-del').forEach(b=>{
  b.addEventListener('click',e=>{e.preventDefault();const h=e.currentTarget.href;
    Swal.fire({title:'Hapus data ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#475569',confirmButtonText:'Hapus',cancelButtonText:'Batal'})
      .then(r=>{if(r.isConfirmed)location.href=h;});
  });
});
</script>
</body></html>
