(() => {
  const start = () => {
    const title = document.querySelector('[data-typewriter]');
    const textNode = document.querySelector('#typewriter-text');
    if (!title || !textNode) return;

    const text = title.dataset.typewriter || textNode.textContent || '';
    if (!text || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      textNode.textContent = text;
      return;
    }

    const characters = Array.from(text);
    const typeDelay = 82;
    const deleteDelay = 42;
    const finishedPause = 3800;
    const restartPause = 650;
    let index = 0;
    let deleting = false;

    const animate = () => {
      if (document.hidden) {
        window.setTimeout(animate, 350);
        return;
      }
      textNode.textContent = characters.slice(0, index).join('');
      if (!deleting && index < characters.length) {
        index += 1;
        window.setTimeout(animate, typeDelay);
      } else if (!deleting) {
        deleting = true;
        window.setTimeout(animate, finishedPause);
      } else if (index > 0) {
        index -= 1;
        window.setTimeout(animate, deleteDelay);
      } else {
        deleting = false;
        window.setTimeout(animate, restartPause);
      }
    };

    textNode.textContent = '';
    animate();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})();
