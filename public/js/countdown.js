document.addEventListener('DOMContentLoaded', function() {
    const timerEl = document.getElementById('flashTimer');
    if (!timerEl) return;

    const endTime = parseInt(timerEl.dataset.end) * 1000;

    function updateTimer() {
        const diff = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;

        document.getElementById('hours').textContent = String(h).padStart(2, '0');
        document.getElementById('minutes').textContent = String(m).padStart(2, '0');
        document.getElementById('seconds').textContent = String(s).padStart(2, '0');

        if (diff <= 0) clearInterval(interval);
    }

    updateTimer();
    const interval = setInterval(updateTimer, 1000);
});
