// assets/js/main.js
document.addEventListener('DOMContentLoaded', () => {

  // ----- Mega menu: bấm để mở/đóng (dùng cho mobile và bàn phím) -----
  document.querySelectorAll('.has-mega').forEach(item => {
    const btn = item.querySelector('.nav-link');

    btn.addEventListener('click', e => {
      e.stopPropagation();
      const open = item.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open);
    });

    // Desktop: rê chuột thì cập nhật aria cho đúng
    item.addEventListener('mouseenter', () => btn.setAttribute('aria-expanded', 'true'));
    item.addEventListener('mouseleave', () => {
      if (!item.classList.contains('is-open')) btn.setAttribute('aria-expanded', 'false');
    });
  });

  // Bấm ra ngoài hoặc nhấn Esc thì đóng mega menu
  const closeMega = () => {
    document.querySelectorAll('.has-mega.is-open').forEach(item => {
      item.classList.remove('is-open');
      item.querySelector('.nav-link').setAttribute('aria-expanded', 'false');
    });
  };
  document.addEventListener('click', closeMega);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMega(); });

});

// ----- Cập nhật số lượng trên icon giỏ hàng (cart.js sẽ gọi hàm này) -----
function updateCartCount(n) {
  const el = document.querySelector('[data-cart-count]');
  if (!el) return;
  el.textContent = n;
  el.dataset.count = n;
}