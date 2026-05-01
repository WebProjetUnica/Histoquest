function createTimer({ duration, textEl, fillEl, zoneEl, onEnd }) {

  let secondsLeft = duration;
  let intervalId  = null;

  function updateDisplay() {
    if (textEl) textEl.textContent = secondsLeft;

    if (fillEl) {
      fillEl.style.width = (secondsLeft / duration) * 100 + '%';
    }

    if (zoneEl) {
      zoneEl.classList.remove('timer--warning', 'timer--danger');
      if (secondsLeft <= 5)       zoneEl.classList.add('timer--danger');
      else if (secondsLeft <= 10) zoneEl.classList.add('timer--warning');
    }
  }

  function start() {
    if (intervalId !== null) stop();
    secondsLeft = duration;
    updateDisplay();

    intervalId = setInterval(() => {
      secondsLeft--;
      updateDisplay();
      if (secondsLeft <= 0) {
        stop();
        if (typeof onEnd === 'function') onEnd();
      }
    }, 1000);
  }

  function stop() {
    clearInterval(intervalId);
    intervalId = null;
  }

  function reset() {
    stop();
    secondsLeft = duration;
    updateDisplay();
    if (zoneEl) zoneEl.classList.remove('timer--warning', 'timer--danger');
  }

  return { start, stop, reset };
}