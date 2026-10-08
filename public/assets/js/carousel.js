(() => {
  class Carousel {
    constructor(root) {
      this.root = root;
      this.wrapper = root.querySelector('.carousel-wrapper');
      this.track = root.querySelector('.carousel-track');
      this.slides = Array.from(root.querySelectorAll('.carousel-slide'));
      this.previous = root.querySelector('[data-carousel-prev]');
      this.next = root.querySelector('[data-carousel-next]');
      this.controls = root.querySelector('.carousel-controls');
      this.index = 0;
      this.dragStartX = null;
      this.dragStartOffset = 0;
      this.dragged = false;

      if (!this.wrapper || !this.track || this.slides.length === 0) return;

      this.previous?.addEventListener('click', () => this.move(-1));
      this.next?.addEventListener('click', () => this.move(1));
      this.bindSwipe();
      window.addEventListener('resize', () => this.render(false), { passive: true });
      this.render(false);
    }

    // Four cards on desktop, two on tablet and one on a phone.
    itemsPerView() {
      if (window.innerWidth >= 1024) return 4;
      if (window.innerWidth >= 768) return 2;
      return 1;
    }

    maximumIndex() {
      return Math.max(0, this.slides.length - this.itemsPerView());
    }

    offsetFor(index) {
      return (this.slides[index]?.offsetLeft ?? 0) - (this.slides[0]?.offsetLeft ?? 0);
    }

    render(animate = true) {
      this.index = Math.min(Math.max(0, this.index), this.maximumIndex());
      this.track.style.transition = animate ? '' : 'none';
      this.track.style.transform = `translate3d(-${this.offsetFor(this.index)}px, 0, 0)`;

      const canMove = this.slides.length > this.itemsPerView();
      if (this.controls) this.controls.hidden = !canMove;
      if (this.previous) this.previous.disabled = this.index === 0;
      if (this.next) this.next.disabled = this.index >= this.maximumIndex();
    }

    move(direction) {
      this.index += direction * this.itemsPerView();
      this.render();
    }

    bindSwipe() {
      this.wrapper.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        this.dragStartX = event.clientX;
        this.dragStartOffset = this.offsetFor(this.index);
        this.dragged = false;
        this.track.style.transition = 'none';
        this.wrapper.setPointerCapture?.(event.pointerId);
      });

      this.wrapper.addEventListener('pointermove', (event) => {
        if (this.dragStartX === null) return;
        const delta = event.clientX - this.dragStartX;
        if (Math.abs(delta) <= 5) return;
        this.dragged = true;
        const maxOffset = this.offsetFor(this.maximumIndex());
        const offset = Math.min(maxOffset, Math.max(0, this.dragStartOffset - delta));
        this.track.style.transform = `translate3d(-${offset}px, 0, 0)`;
      });

      const endSwipe = (event) => {
        if (this.dragStartX === null) return;
        const delta = event.clientX - this.dragStartX;
        this.dragStartX = null;
        if (Math.abs(delta) > 40) this.index += delta > 0 ? -this.itemsPerView() : this.itemsPerView();
        this.render();
        if (this.dragged) window.setTimeout(() => { this.dragged = false; }, 0);
      };
      this.wrapper.addEventListener('pointerup', endSwipe);
      this.wrapper.addEventListener('pointercancel', () => {
        this.dragStartX = null;
        this.render();
      });
      this.wrapper.addEventListener('click', (event) => {
        if (!this.dragged) return;
        event.preventDefault();
        this.dragged = false;
      }, true);
    }
  }

  const init = () => document.querySelectorAll('[data-carousel]').forEach((root) => new Carousel(root));
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
