document.addEventListener("DOMContentLoaded", () => {
  // --- THEME SWITCHER LOGIC ---
  const themeSwitchers = document.querySelectorAll(".theme-switcher");
  const html = document.documentElement;

  const setTheme = (theme) => {
    if (!theme) {
      console.warn("Attempted to set an invalid theme.");
      return;
    }
    html.dataset.theme = theme;
    localStorage.setItem("mrpack-depot-theme", theme);
    themeSwitchers.forEach((switcher) => {
      if (switcher.value !== theme) {
        switcher.value = theme;
      }
    });
  };

  const savedTheme = localStorage.getItem("mrpack-depot-theme") || "mocha";
  setTheme(savedTheme);

  themeSwitchers.forEach((switcher) => {
    switcher.addEventListener("change", (event) => {
      setTheme(event.target.value);
    });
  });

  // --- HOMEPAGE LOGIC ---
  const gridContainer = document.getElementById("gridContainer");
  if (gridContainer) {
    // --- Card Click Logic ---
    const clickableCards = gridContainer.querySelectorAll(".card");
    clickableCards.forEach((card) => {
      card.addEventListener("click", (event) => {
        if (event.target.closest(".card-download-btn")) {
          return;
        }
        const url = event.currentTarget.dataset.href;
        if (url) {
          window.location.href = url;
        }
      });
    });

    // --- Filtering Logic ---
    const searchInput = document.getElementById("searchInput");
    const filterButtons = document.querySelectorAll(".filter-btn");
    let activeFilters = { loader: "all", version: "all", search: "" };

    function applyFilters() {
      clickableCards.forEach((card) => {
        const title = card.querySelector("h3").textContent.toLowerCase();
        const loader = card.dataset.loader.toLowerCase(); // Convert card's loader to lowercase
        const version = card.dataset.version.toLowerCase(); // Convert card's version to lowercase

        const searchMatch =
          activeFilters.search === "" || title.includes(activeFilters.search);
        const loaderMatch =
          activeFilters.loader === "all" || loader === activeFilters.loader;
        const versionMatch =
          activeFilters.version === "all" || version === activeFilters.version;

        if (searchMatch && loaderMatch && versionMatch) {
          card.style.display = "flex";
        } else {
          card.style.display = "none";
        }
      });
    }

    if (searchInput) {
      searchInput.addEventListener("input", (e) => {
        activeFilters.search = e.target.value.toLowerCase();
        applyFilters();
      });
    }

    if (filterButtons.length > 0) {
      filterButtons.forEach((button) => {
        button.addEventListener("click", () => {
          const group = button.dataset.filterGroup;

          const filterValue = button.dataset.filter.toLowerCase(); // Also convert the button's filter value to lowercase

          activeFilters[group] = filterValue;
          document
            .querySelectorAll(`.filter-btn[data-filter-group="${group}"]`)
            .forEach((btn) => btn.classList.remove("active"));
          button.classList.add("active");
          applyFilters();
        });
      });
    }
  }
});
