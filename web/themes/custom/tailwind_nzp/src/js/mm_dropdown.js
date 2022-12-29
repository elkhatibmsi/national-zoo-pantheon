document.addEventListener('alpine:init', () => {
    Alpine.data('mmDropdown', () => ({
        isOpen: false,
        isMobile: false,
        checkIsMobile: function() {
          this.isMobile = (window.outerWidth < 960);
        },
        clickToOpen: function() {
            this.isOpen = !this.isOpen;
          },
        isTouchEnabled: function() {
          return ('ontouchstart' in window) ||
            (navigator.maxTouchPoints > 0) ||
            (navigator.msMaxTouchPoints > 0);
        },       
    })
    );
});