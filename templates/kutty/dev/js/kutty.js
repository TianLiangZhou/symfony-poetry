import "alpinejs/dist/cdn.min"

(function (root) {
  root.Components = {}, root.Components.popover = function ({
                                                              open: e = !1,
                                                              focus: t = !1
                                                            } = {}) {
    const n = ["[contentEditable=true]", "[tabindex]", "a[href]", "area[href]", "button:not([disabled])", "iframe", "input:not([disabled])", "select:not([disabled])", "textarea:not([disabled])"].map((e => `${e}:not([tabindex='-1'])`)).join(",");
    return {
      __type: "popover",
      open: e,
      init() {
        t && this.$watch("open", (e => {
          e && this.$nextTick((() => {
            !function (e) {
              const t = Array.from(e.querySelectorAll(n));
              !function e(n) {
                void 0 !== n && (n.focus({
                  preventScroll: !0
                }), document.activeElement !== n && e(t[t.indexOf(n) + 1]))
              }(t[0])
            }(this.$refs.panel)
          }))
        }));
        let e = n => {
          if (!document.body.contains(this.$el)) return void root.removeEventListener("focus", e, !0);
          let i = t ? this.$refs.panel : this.$el;
          if (this.open && n.target instanceof Element && !i.contains(n.target)) {
            let e = this.$el;
            for (; e.parentNode;)
              if (e = e.parentNode, e.__x instanceof this.constructor) {
                if ("popoverGroup" === e.__x.$data.__type) return;
                if ("popover" === e.__x.$data.__type) break
              }
            this.open = !1
          }
        };
        root.addEventListener("focus", e, !0)
      },
      onEscape() {
        this.open = !1, this.restoreEl && this.restoreEl.focus()
      },
      onClosePopoverGroup(e) {
        e.detail.contains(this.$el) && (this.open = !1)
      },
      toggle(e) {
        this.open = !this.open, this.open ? this.restoreEl = e.currentTarget : this.restoreEl && this.restoreEl.focus()
      }
    }
  }, root.Components.theme = function () {
    let theme = root.Components.popover.apply(this, arguments);
    theme.toggleTheme = function (e) {
      this.toggle();
      Alpine.store('theme').toggle(e.target.innerText);
    };
    return theme;
  }
  ;

  document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
      current: 'System',
      system: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light',
      toggle(theme) {
        this.current = theme;
        if (theme == 'System') {
          this.system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light';
        } else {
          this.system = theme;
        }
      }
    });
  });
})(window);

