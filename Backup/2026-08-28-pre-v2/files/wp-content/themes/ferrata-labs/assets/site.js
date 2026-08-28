/**
 * The three interactive pieces of the site, ported from the React components.
 * Vanilla, no dependencies, ~2kb. prefers-reduced-motion is respected — CLAUDE.md §10.
 */
(function () {
  "use strict";

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------------- the rail (Rail.tsx) ---------------- */
  var run = document.querySelector(".rail-run");
  if (run && !reduce) {
    var frame = 0;
    var tick = function () {
      frame = 0;
      var h = document.documentElement.scrollHeight - window.innerHeight;
      var p = h > 0 ? window.scrollY / h : 0;
      run.style.height = Math.min(100, p * 100) + "vh";
    };
    var onScroll = function () {
      if (!frame) frame = requestAnimationFrame(tick);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    tick();
  }

  /* ---------------- solutions mega-menu (Nav.tsx) ----------------
   * openedBy tracks how the menu opened. Without it, hovering the trigger opens the
   * menu and the click that follows immediately toggles it shut — the classic
   * mega-menu annoyance. A click on a hover-opened menu takes ownership instead of
   * closing it; only a click on a click-opened menu closes.
   */
  var wrap = document.querySelector(".mega-wrap");
  var trigger = wrap && wrap.querySelector(".mega-caret");
  if (wrap && trigger) {
    var openedBy = null;

    var setOpen = function (open) {
      wrap.setAttribute("data-open", open ? "true" : "false");
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
      if (!open) openedBy = null;
    };
    var isOpen = function () {
      return wrap.getAttribute("data-open") === "true";
    };
    var canHover = function () {
      return window.matchMedia("(hover: hover) and (pointer: fine)").matches;
    };

    /* The panel is absolutely positioned and clears the nav bar, so a pointer
     * travelling from the trigger into the panel crosses dead space that belongs to
     * neither. Closing on a short grace period keeps the panel reachable; moving
     * back onto the trigger or into the panel cancels it. */
    var closeTimer = null;
    var cancelClose = function () {
      if (closeTimer) {
        clearTimeout(closeTimer);
        closeTimer = null;
      }
    };

    wrap.addEventListener("mouseenter", function () {
      if (canHover()) {
        cancelClose();
        if (!openedBy) openedBy = "hover";
        setOpen(true);
      }
    });
    wrap.addEventListener("mouseleave", function () {
      cancelClose();
      closeTimer = setTimeout(function () {
        setOpen(false);
      }, 260);
    });
    trigger.addEventListener("click", function () {
      cancelClose();
      if (isOpen() && openedBy === "click") {
        setOpen(false);
        return;
      }
      openedBy = "click";
      setOpen(true);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && isOpen()) {
        setOpen(false);
        trigger.focus();
      }
    });
    document.addEventListener("pointerdown", function (e) {
      if (isOpen() && !wrap.contains(e.target)) setOpen(false);
    });
    document.addEventListener("focusin", function (e) {
      if (isOpen() && !wrap.contains(e.target)) setOpen(false);
    });
  }

  /* ---------------- mobile drawer (Nav.tsx) ---------------- */
  var toggle = document.querySelector(".nav-toggle");
  var drawer = document.getElementById("mobile-drawer");
  if (toggle && drawer) {
    toggle.addEventListener("click", function () {
      var open = drawer.getAttribute("data-open") !== "true";
      drawer.setAttribute("data-open", open ? "true" : "false");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.textContent = open ? "Close" : "Menu";
    });
  }

  /* ---------------- console recording (ConsolePreview.tsx) ----------------
   * The server ships the poster frame so a reduced-motion visitor never downloads
   * the video, exactly as the React component does. Upgrade to video only when
   * motion is welcome, and only once the figure is on screen: a dynamically created
   * element does not reliably honour autoplay, and 2.5mb should not be spent on a
   * visitor who never scrolls this far.
   */
  if (!reduce) {
    var upgrade = function (img) {
      // A lazy-loading optimiser may replace src with a placeholder and move the real
      // URL to data-src, so read that first and refuse to build sources from anything
      // that is not the poster itself.
      var poster = img.getAttribute("data-src") || img.getAttribute("src");
      if (!poster || !/\.png$/i.test(poster)) return;
      var base = poster.replace(/\.png$/i, "");
      var v = document.createElement("video");
      v.setAttribute("autoplay", "");
      v.setAttribute("loop", "");
      v.setAttribute("muted", "");
      v.setAttribute("playsinline", "");
      v.muted = true;
      v.setAttribute("poster", poster);
      v.setAttribute("aria-label", img.getAttribute("alt") || "");
      v.setAttribute("width", img.getAttribute("width") || "1728");
      v.setAttribute("height", img.getAttribute("height") || "1080");
      ["webm", "mp4"].forEach(function (ext) {
        var s = document.createElement("source");
        s.setAttribute("src", base + "." + ext);
        s.setAttribute("type", "video/" + ext);
        v.appendChild(s);
      });
      img.parentNode.replaceChild(v, img);
      return v;
    };

    // Autoplay normally starts this by itself; the nudge is only for browsers that
    // decline it for a script-created element.
    var start = function (v) {
      if (!v.paused) return;
      var p = v.play();
      if (p && p.catch) p.catch(function () {});
    };

    var frames = document.querySelectorAll(".console-frame img");
    Array.prototype.forEach.call(frames, function (img) {
      var v = upgrade(img);
      if (!v) return;
      if (!("IntersectionObserver" in window)) {
        start(v);
        return;
      }
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            io.disconnect();
            start(v);
          });
        },
        { rootMargin: "200px" }
      );
      io.observe(v);
    });
  }
})();
