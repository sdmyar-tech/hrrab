"use strict";

(function () {
  var sidebarStorageKey = "adminHMD.sidebarMini";
  var themeStorageKey = "adminHMD.colorTheme";
  var desktopMedia = "(min-width: 992px)";

  function onReady(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback);
      return;
    }

    callback();
  }

  function isDesktop() {
    return window.matchMedia(desktopMedia).matches;
  }

  function canUseStorage() {
    try {
      var testKey = sidebarStorageKey + ".test";
      window.localStorage.setItem(testKey, "1");
      window.localStorage.removeItem(testKey);
      return true;
    } catch (error) {
      return false;
    }
  }

  function getSavedMiniState(storageAvailable) {
    if (!storageAvailable) {
      return false;
    }

    return window.localStorage.getItem(sidebarStorageKey) === "true";
  }

  function saveMiniState(storageAvailable, isMini) {
    if (storageAvailable) {
      window.localStorage.setItem(sidebarStorageKey, String(isMini));
    }
  }

  function getPreferredTheme(storageAvailable) {
    var savedTheme = storageAvailable ? window.localStorage.getItem(themeStorageKey) : "";

    if (savedTheme === "dark" || savedTheme === "light") {
      return savedTheme;
    }

    if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
      return "dark";
    }

    return "light";
  }

  onReady(function () {
    var body = document.body;
    var sidebarToggle = document.querySelector("[data-sidebar-toggle]");
    var themeToggles = document.querySelectorAll("[data-theme-toggle]");
    var themeIcons = document.querySelectorAll("[data-theme-icon]");
    var closeButtons = document.querySelectorAll("[data-sidebar-close]");
    // Hanya link tujuan yang menutup sidebar (mobile); menu induk pembuka submenu (data-bs-toggle) tidak.
    var sidebarLinks = document.querySelectorAll(".sidebar-nav a.nav-link");
    var mediaQuery = window.matchMedia(desktopMedia);
    var storageAvailable = canUseStorage();

    function initValidation() {
      var forms = document.querySelectorAll(".needs-validation");

      Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener("submit", function (event) {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }

          form.classList.add("was-validated");
        });
      });
    }

    function initTableSearch() {
      var searchInputs = document.querySelectorAll("[data-table-search]");

      Array.prototype.forEach.call(searchInputs, function (input) {
        var tableId = input.getAttribute("data-table-search");
        var table = document.getElementById(tableId);

        if (!table) {
          return;
        }

        input.addEventListener("input", function () {
          var query = input.value.trim().toLowerCase();
          var rows = table.querySelectorAll("tbody tr");

          Array.prototype.forEach.call(rows, function (row) {
            row.hidden = query !== "" && row.textContent.toLowerCase().indexOf(query) === -1;
          });
        });
      });
    }

    function updateThemeControls(theme) {
      var nextTheme = theme === "dark" ? "light" : "dark";
      var label = "Switch to " + nextTheme + " mode";
      var iconClass = theme === "dark" ? "bi bi-sun" : "bi bi-moon-stars";

      Array.prototype.forEach.call(themeToggles, function (button) {
        button.setAttribute("aria-label", label);
        button.setAttribute("title", label);
      });

      Array.prototype.forEach.call(themeIcons, function (icon) {
        icon.className = iconClass;
      });
    }

    function applyTheme(theme) {
      document.documentElement.setAttribute("data-theme", theme);
      document.documentElement.setAttribute("data-bs-theme", theme);

      if (storageAvailable) {
        window.localStorage.setItem(themeStorageKey, theme);
      }

      updateThemeControls(theme);
    }

    function initThemeToggle() {
      applyTheme(getPreferredTheme(storageAvailable));

      Array.prototype.forEach.call(themeToggles, function (button) {
        button.addEventListener("click", function () {
          var currentTheme = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
          applyTheme(currentTheme === "dark" ? "light" : "dark");
        });
      });
    }

    function initResponsiveTables() {
      var tables = document.querySelectorAll(".table-responsive table");

      Array.prototype.forEach.call(tables, function (table) {
        var headers = table.querySelectorAll("thead th");

        if (!headers.length) {
          return;
        }

        var labels = Array.prototype.map.call(headers, function (th) {
          return th.textContent.trim();
        });

        table.classList.add("table-mobile-cards");

        var rows = table.querySelectorAll("tbody tr");

        Array.prototype.forEach.call(rows, function (row) {
          var cells = row.children;

          if (cells.length !== labels.length) {
            return;
          }

          Array.prototype.forEach.call(cells, function (cell, index) {
            if (labels[index]) {
              cell.setAttribute("data-label", labels[index]);
            }
          });
        });
      });
    }

    // Dropdown di dalam tabel memakai posisi fixed agar menu tidak terpotong oleh wadah tabel.
    function initTableDropdowns() {
      var toggles = document.querySelectorAll(".table-responsive [data-bs-toggle='dropdown'], .panel [data-bs-toggle='dropdown']");

      Array.prototype.forEach.call(toggles, function (toggle) {
        if (!toggle.hasAttribute("data-bs-popper-config")) {
          toggle.setAttribute("data-bs-popper-config", '{"strategy":"fixed"}');
        }
      });
    }

    // Kolom lokasi: tampilkan tautan cek ke Google Maps (atau link meeting) sesuai isian.
    function initLokasiMaps() {
      var inputs = document.querySelectorAll("input[name='lokasi']");

      Array.prototype.forEach.call(inputs, function (input) {
        var link = document.createElement("a");
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.className = "small d-inline-block mt-1";
        var jangkar = input.parentElement.classList.contains("input-group") ? input.parentElement : input;
        jangkar.insertAdjacentElement("afterend", link);

        function perbarui() {
          var nilai = input.value.trim();
          var url = /^https?:\/\//i.test(nilai);
          var peta = !url || /google\.[a-z.]+\/maps|maps\.google\.|maps\.app\.goo\.gl|goo\.gl\/maps/i.test(nilai);

          link.hidden = nilai === "";
          link.href = url ? nilai : "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(nilai);
          link.innerHTML = peta
            ? '<i class="bi bi-map me-1"></i>Cek lokasi di Google Maps'
            : '<i class="bi bi-box-arrow-up-right me-1"></i>Buka link meeting';
        }

        input.addEventListener("input", perbarui);
        perbarui();
      });
    }

    // Dropdown submenu sidebar. Grup yang berisi halaman aktif selalu terbuka;
    // grup lain mengikuti pilihan terakhir pengguna (disimpan di localStorage).
    function initNavGrup() {
      var grupStorageKey = "hrrab.navGrupTerbuka";
      var grupList = document.querySelectorAll("[data-nav-grup]");
      var tersimpan = [];

      try {
        tersimpan = JSON.parse(window.localStorage.getItem(grupStorageKey) || "[]");
      } catch (error) {
        tersimpan = [];
      }

      function simpan() {
        var terbuka = Array.prototype.filter.call(grupList, function (g) {
          return g.classList.contains("terbuka");
        }).map(function (g) {
          return g.getAttribute("data-nav-grup");
        });

        try {
          window.localStorage.setItem(grupStorageKey, JSON.stringify(terbuka));
        } catch (error) {
          // Penyimpanan tidak tersedia (mode privat): abaikan.
        }
      }

      function atur(grup, buka) {
        grup.classList.toggle("terbuka", buka);
        grup.querySelector(".nav-grup-toggle").setAttribute("aria-expanded", String(buka));
      }

      Array.prototype.forEach.call(grupList, function (grup) {
        if (tersimpan.indexOf(grup.getAttribute("data-nav-grup")) !== -1) {
          atur(grup, true);
        }

        grup.querySelector(".nav-grup-toggle").addEventListener("click", function () {
          atur(grup, !grup.classList.contains("terbuka"));
          simpan();
        });
      });

      // Animasi baru diaktifkan setelah status awal terpasang agar tidak "berkedip" saat halaman dimuat.
      window.requestAnimationFrame(function () {
        document.documentElement.classList.add("nav-grup-siap");
      });
    }

    initNavGrup();
    initValidation();
    initLokasiMaps();
    initTableDropdowns();
    initTableSearch();
    initResponsiveTables();
    initThemeToggle();

    // Initialize user profile values in UI. Provide a window.adminHMDUser object to override defaults.
    function initUserProfile() {
      var user = window.adminHMDUser || { name: "User", workspace: "Active Workspace", avatar: "../assets/images/avatar/avatar1.jpg" };

      var sidebarNameEl = document.querySelector(".sidebar-user strong");
      var sidebarWorkspaceEl = document.querySelector(".sidebar-user small");
      var sidebarAvatar = document.querySelector(".sidebar-user .avatar-img");
      var profileNameEls = document.querySelectorAll(".profile-name");
      var profileAvatarEls = document.querySelectorAll(".profile-button .avatar-img, .profile-button img");

      if (sidebarNameEl) sidebarNameEl.textContent = user.name;
      if (sidebarWorkspaceEl) sidebarWorkspaceEl.textContent = user.workspace;
      if (sidebarAvatar && user.avatar) { sidebarAvatar.src = user.avatar; sidebarAvatar.alt = user.name; }

      Array.prototype.forEach.call(profileNameEls, function (el) { el.textContent = user.name; });
      Array.prototype.forEach.call(profileAvatarEls, function (img) { if (user.avatar) img.src = user.avatar; if (user.name) img.alt = user.name; });
    }

    initUserProfile();

    if (!sidebarToggle) {
      return;
    }

    function setClass(element, className, enabled) {
      if (enabled) {
        element.classList.add(className);
      } else {
        element.classList.remove(className);
      }
    }

    function setToggleExpanded() {
      var expanded = isDesktop()
        ? !body.classList.contains("sidebar-mini")
        : body.classList.contains("sidebar-open");

      sidebarToggle.setAttribute("aria-expanded", String(expanded));
    }

    function closeMobileSidebar() {
      body.classList.remove("sidebar-open");
      setToggleExpanded();
    }

    function toggleSidebar() {
      if (isDesktop()) {
        body.classList.toggle("sidebar-mini");
        saveMiniState(storageAvailable, body.classList.contains("sidebar-mini"));
      } else {
        body.classList.toggle("sidebar-open");
      }

      setToggleExpanded();
    }

    function addCloseHandlers(items) {
      Array.prototype.forEach.call(items, function (item) {
        item.addEventListener("click", function () {
          if (!isDesktop()) {
            closeMobileSidebar();
          }
        });
      });
    }

    if (getSavedMiniState(storageAvailable) && isDesktop()) {
      body.classList.add("sidebar-mini");
    }

    sidebarToggle.addEventListener("click", toggleSidebar);
    addCloseHandlers(closeButtons);
    addCloseHandlers(sidebarLinks);
    setToggleExpanded();

    function handleBreakpointChange() {
      if (isDesktop()) {
        body.classList.remove("sidebar-open");
        setClass(body, "sidebar-mini", getSavedMiniState(storageAvailable));
      } else {
        body.classList.remove("sidebar-mini");
      }

      setToggleExpanded();
    }

    function initSweetAlert() {
      var confirmButtons = document.querySelectorAll("[data-confirm='true']");

      Array.prototype.forEach.call(confirmButtons, function (button) {
        button.addEventListener("click", function (event) {
          var form = button.closest("form");

          if (!form) {
            return;
          }

          event.preventDefault();

          Swal.fire({
            title: button.getAttribute("data-title") || "Konfirmasi",
            text: button.getAttribute("data-text") || "Apakah Anda yakin ingin melanjutkan?",
            icon: button.getAttribute("data-icon") || "warning",
            showCancelButton: true,
            confirmButtonColor: button.getAttribute("data-confirm-color") || "#880f12",
            cancelButtonColor: button.getAttribute("data-cancel-color") || "#6c757d",
            confirmButtonText: button.getAttribute("data-confirm-text") || "Ya, lanjutkan",
            cancelButtonText: button.getAttribute("data-cancel-text") || "Batal"
          }).then(function (result) {
            if (result.isConfirmed) {
              form.submit();
            }
          });
        });
      });
    }

    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener("change", handleBreakpointChange);
    } else if (mediaQuery.addListener) {
      mediaQuery.addListener(handleBreakpointChange);
    }

    initSweetAlert();
  });
})();
