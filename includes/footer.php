</main>
<footer class="footer">© <?= date('Y') ?> <?= e(SITE_NAME) ?></footer>
<script>
const cd = document.getElementById('countdown');
if (cd) {
  const end = +cd.dataset.end;
  const pad = n => String(n).padStart(2, '0');
  const tick = () => {
    const s = Math.max(0, Math.floor((end - Date.now()) / 1000));
    cd.textContent = `${Math.floor(s / 86400)} روز ${pad(Math.floor(s % 86400 / 3600))}:${pad(Math.floor(s % 3600 / 60))}:${pad(s % 60)}`;
  };
  tick(); setInterval(tick, 1000);
}
</script>
</body>
</html>
