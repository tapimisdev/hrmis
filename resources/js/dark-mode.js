const storageKey = "theme-preference";

const onClick = () => {
    theme.value = theme.value === "light" ? "dark" : "light";

    // Apply theme class to both <html> and <body>
    $("html, body").removeClass("light dark").addClass(theme.value);

    setPreference();
};

const getColorPreference = () => {
    if (localStorage.getItem(storageKey)) {
        return localStorage.getItem(storageKey);
    } else {
        return window.matchMedia("(prefers-color-scheme: dark)").matches
            ? "dark"
            : "light";
    }
};

const setPreference = () => {
    localStorage.setItem(storageKey, theme.value);
    reflectPreference();
};

const reflectPreference = () => {
    // Set Bootstrap theme
    document.documentElement.setAttribute("data-bs-theme", theme.value);

    // Update button label
    document
        .querySelector("#theme-toggle")
        ?.setAttribute("aria-label", theme.value);

    // Apply classes to HTML + body on initial load
    document.documentElement.classList.remove("light", "dark");
    document.body.classList.remove("light", "dark");

    document.documentElement.classList.add(theme.value);
    document.body.classList.add(theme.value);
};

const theme = {
    value: getColorPreference(),
};

// Early apply to prevent FOUC (Flash Of Unstyled Content)
reflectPreference();

document.addEventListener("DOMContentLoaded", () => {
    reflectPreference();

    const toggleBtn = document.querySelector("#theme-toggle");

    if (toggleBtn) {
        toggleBtn.addEventListener("click", onClick);
    }
});


// Listen to system theme changes
window
    .matchMedia("(prefers-color-scheme: dark)")
    .addEventListener("change", ({ matches: isDark }) => {
        theme.value = isDark ? "dark" : "light";
        setPreference();
    });
